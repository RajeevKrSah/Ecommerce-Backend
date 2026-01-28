<?php

namespace App\Services;

use App\Models\Order;
use App\Models\PaymentTransaction;
use Stripe\Stripe;
use Stripe\PaymentIntent;
use Stripe\Refund;
use Stripe\Webhook;
use Stripe\Exception\SignatureVerificationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;

class PaymentService
{
    private const PAYMENT_TIMEOUT_MINUTES = 30;
    private const MAX_RETRY_ATTEMPTS = 3;

    public function __construct()
    {
        Stripe::setApiKey(config('services.stripe.secret'));
    }

    /**
     * Create a payment intent for an order with idempotency
     */
    public function createPaymentIntent(Order $order): PaymentIntent
    {
        // Check if payment intent already exists
        if ($order->payment_intent_id) {
            try {
                $existingIntent = PaymentIntent::retrieve($order->payment_intent_id);
                if (in_array($existingIntent->status, ['requires_payment_method', 'requires_confirmation', 'requires_action'])) {
                    return $existingIntent;
                }
            } catch (\Exception $e) {
                Log::warning('Failed to retrieve existing payment intent', [
                    'order_id' => $order->id,
                    'payment_intent_id' => $order->payment_intent_id,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        // Generate idempotency key
        $idempotencyKey = $this->generateIdempotencyKey($order);

        try {
            $paymentIntent = PaymentIntent::create([
                'amount' => $this->convertToStripeAmount($order->total),
                'currency' => $order->currency ?? 'usd',
                'metadata' => [
                    'order_id' => $order->id,
                    'order_number' => $order->order_number,
                    'user_id' => $order->user_id,
                ],
                'automatic_payment_methods' => [
                    'enabled' => true,
                ],
                'description' => "Order {$order->order_number}",
            ], [
                'idempotency_key' => $idempotencyKey,
            ]);

            // Update order with payment details
            $order->update([
                'payment_intent_id' => $paymentIntent->id,
                'payment_status' => 'pending',
                'payment_expires_at' => now()->addMinutes(self::PAYMENT_TIMEOUT_MINUTES),
            ]);

            // Log transaction
            PaymentTransaction::log(
                orderId: $order->id,
                type: 'charge',
                stripeId: $paymentIntent->id,
                amount: $order->total,
                status: 'pending',
                metadata: [
                    'idempotency_key' => $idempotencyKey,
                    'expires_at' => $order->payment_expires_at,
                ]
            );

            Log::info('Payment intent created', [
                'order_id' => $order->id,
                'payment_intent_id' => $paymentIntent->id,
                'amount' => $order->total,
            ]);

            return $paymentIntent;
        } catch (\Exception $e) {
            Log::error('Failed to create payment intent', [
                'order_id' => $order->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            throw $e;
        }
    }

    /**
     * Verify webhook signature and return event
     */
    public function verifyWebhook(string $payload, string $signature): \Stripe\Event
    {
        $webhookSecret = config('services.stripe.webhook_secret');

        if (empty($webhookSecret)) {
            Log::error('Webhook secret not configured');
            throw new \Exception('Webhook secret not configured');
        }

        try {
            return Webhook::constructEvent($payload, $signature, $webhookSecret);
        } catch (SignatureVerificationException $e) {
            Log::error('Webhook signature verification failed', [
                'error' => $e->getMessage(),
            ]);
            throw new \Exception('Invalid webhook signature');
        }
    }

    /**
     * Handle successful payment with transaction safety
     */
    public function handlePaymentSuccess(PaymentIntent $paymentIntent): void
    {
        $orderId = $paymentIntent->metadata->order_id ?? null;

        if (!$orderId) {
            Log::error('Payment intent missing order_id', [
                'payment_intent' => $paymentIntent->id
            ]);
            return;
        }

        // Use cache lock to prevent duplicate processing
        $lockKey = "payment_processing_{$paymentIntent->id}";
        $lock = Cache::lock($lockKey, 60);

        if (!$lock->get()) {
            Log::warning('Payment already being processed', [
                'payment_intent' => $paymentIntent->id,
                'order_id' => $orderId,
            ]);
            return;
        }

        try {
            DB::beginTransaction();

            $order = Order::lockForUpdate()->find($orderId);

            if (!$order) {
                Log::error('Order not found for payment intent', [
                    'order_id' => $orderId,
                    'payment_intent' => $paymentIntent->id
                ]);
                DB::rollBack();
                return;
            }

            // Check if already processed
            if ($order->payment_status === 'paid') {
                Log::info('Payment already processed', [
                    'order_id' => $order->id,
                    'payment_intent' => $paymentIntent->id,
                ]);
                DB::rollBack();
                return;
            }

            // Reduce stock for each order item with lock
            foreach ($order->items as $item) {
                $product = $item->product;
                if ($product) {
                    $product->lockForUpdate();
                    
                    if ($product->stock_quantity < $item->quantity) {
                        Log::error('Insufficient stock after payment', [
                            'order_id' => $order->id,
                            'product_id' => $product->id,
                            'required' => $item->quantity,
                            'available' => $product->stock_quantity,
                        ]);
                        // Continue anyway as payment succeeded
                    }
                    
                    $product->decrement('stock_quantity', $item->quantity);
                }
            }

            // Update order status
            $order->update([
                'payment_status' => 'paid',
                'payment_method' => $paymentIntent->payment_method_types[0] ?? 'card',
                'paid_at' => now(),
                'status' => 'processing',
            ]);

            // Log successful transaction
            PaymentTransaction::log(
                orderId: $order->id,
                type: 'charge',
                stripeId: $paymentIntent->id,
                amount: $order->total,
                status: 'succeeded',
                paymentMethod: $paymentIntent->payment_method_types[0] ?? 'card',
                metadata: [
                    'charge_id' => $paymentIntent->latest_charge ?? null,
                    'receipt_url' => $paymentIntent->charges->data[0]->receipt_url ?? null,
                ]
            );

            DB::commit();

            Log::info('Payment successful', [
                'order_id' => $order->id,
                'order_number' => $order->order_number,
                'amount' => $order->total,
                'payment_intent' => $paymentIntent->id,
            ]);

            // TODO: Dispatch email notification job
            // dispatch(new SendOrderConfirmationEmail($order));

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to process successful payment', [
                'order_id' => $orderId,
                'payment_intent' => $paymentIntent->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            throw $e;
        } finally {
            $lock->release();
        }
    }

    /**
     * Handle failed payment
     */
    public function handlePaymentFailed(PaymentIntent $paymentIntent): void
    {
        $orderId = $paymentIntent->metadata->order_id ?? null;

        if (!$orderId) {
            return;
        }

        try {
            DB::beginTransaction();

            $order = Order::lockForUpdate()->find($orderId);

            if (!$order) {
                DB::rollBack();
                return;
            }

            $order->update([
                'payment_status' => 'failed',
            ]);

            // Log failed transaction
            PaymentTransaction::log(
                orderId: $order->id,
                type: 'charge',
                stripeId: $paymentIntent->id,
                amount: $order->total,
                status: 'failed',
                failureReason: $paymentIntent->last_payment_error->message ?? 'Unknown error',
                metadata: [
                    'error_code' => $paymentIntent->last_payment_error->code ?? null,
                    'error_type' => $paymentIntent->last_payment_error->type ?? null,
                ]
            );

            DB::commit();

            Log::warning('Payment failed', [
                'order_id' => $order->id,
                'order_number' => $order->order_number,
                'payment_intent' => $paymentIntent->id,
                'reason' => $paymentIntent->last_payment_error->message ?? 'Unknown',
            ]);

            // TODO: Send payment failed email
            // dispatch(new SendPaymentFailedEmail($order));

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to process payment failure', [
                'order_id' => $orderId,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Process full refund
     */
    public function refundPayment(Order $order, ?string $reason = null): Refund
    {
        return $this->processRefund($order, $order->total, $reason);
    }

    /**
     * Process partial refund
     */
    public function partialRefund(Order $order, float $amount, ?string $reason = null): Refund
    {
        if ($amount > $order->getRemainingRefundableAmount()) {
            throw new \Exception('Refund amount exceeds remaining refundable amount');
        }

        return $this->processRefund($order, $amount, $reason);
    }

    /**
     * Process refund (full or partial)
     */
    private function processRefund(Order $order, float $amount, ?string $reason = null): Refund
    {
        if (!$order->isRefundable()) {
            throw new \Exception('Order is not refundable');
        }

        if (!$order->payment_intent_id) {
            throw new \Exception('No payment intent found for order');
        }

        try {
            DB::beginTransaction();

            // Create refund in Stripe
            $refund = Refund::create([
                'payment_intent' => $order->payment_intent_id,
                'amount' => $this->convertToStripeAmount($amount),
                'reason' => $this->mapRefundReason($reason),
                'metadata' => [
                    'order_id' => $order->id,
                    'order_number' => $order->order_number,
                    'refund_reason' => $reason,
                ],
            ], [
                'idempotency_key' => "refund_{$order->id}_" . now()->timestamp,
            ]);

            // Update order
            $isFullRefund = $amount >= $order->getRemainingRefundableAmount();
            
            $order->update([
                'refunded_amount' => $order->refunded_amount + $amount,
                'refunded_at' => now(),
                'refund_reason' => $reason,
                'payment_status' => $isFullRefund ? 'refunded' : 'partially_refunded',
                'status' => $isFullRefund ? 'cancelled' : $order->status,
            ]);

            // Restore stock
            foreach ($order->items as $item) {
                $product = $item->product;
                if ($product) {
                    $refundQuantity = $isFullRefund ? $item->quantity : 
                        (int) ceil(($amount / $order->total) * $item->quantity);
                    
                    $product->increment('stock_quantity', $refundQuantity);
                }
            }

            // Log refund transaction
            PaymentTransaction::log(
                orderId: $order->id,
                type: 'refund',
                stripeId: $refund->id,
                amount: $amount,
                status: 'succeeded',
                metadata: [
                    'reason' => $reason,
                    'is_full_refund' => $isFullRefund,
                    'refund_status' => $refund->status,
                ]
            );

            DB::commit();

            Log::info('Refund processed', [
                'order_id' => $order->id,
                'refund_id' => $refund->id,
                'amount' => $amount,
                'is_full_refund' => $isFullRefund,
            ]);

            return $refund;

        } catch (\Exception $e) {
            DB::rollBack();
            
            Log::error('Refund failed', [
                'order_id' => $order->id,
                'amount' => $amount,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    /**
     * Cancel expired payments
     */
    public function cancelExpiredPayments(): int
    {
        $expiredOrders = Order::where('payment_status', 'pending')
            ->where('payment_expires_at', '<', now())
            ->get();

        $cancelledCount = 0;

        foreach ($expiredOrders as $order) {
            try {
                DB::beginTransaction();

                $order->update([
                    'payment_status' => 'expired',
                    'status' => 'cancelled',
                ]);

                // Cancel payment intent in Stripe
                if ($order->payment_intent_id) {
                    try {
                        $paymentIntent = PaymentIntent::retrieve($order->payment_intent_id);
                        if ($paymentIntent->status !== 'succeeded') {
                            $paymentIntent->cancel();
                        }
                    } catch (\Exception $e) {
                        Log::warning('Failed to cancel payment intent', [
                            'order_id' => $order->id,
                            'payment_intent_id' => $order->payment_intent_id,
                            'error' => $e->getMessage(),
                        ]);
                    }
                }

                // Log cancellation
                PaymentTransaction::log(
                    orderId: $order->id,
                    type: 'cancelled',
                    stripeId: $order->payment_intent_id ?? 'N/A',
                    amount: $order->total,
                    status: 'cancelled',
                    metadata: ['reason' => 'Payment timeout']
                );

                DB::commit();
                $cancelledCount++;

                Log::info('Expired payment cancelled', [
                    'order_id' => $order->id,
                    'order_number' => $order->order_number,
                ]);

            } catch (\Exception $e) {
                DB::rollBack();
                Log::error('Failed to cancel expired payment', [
                    'order_id' => $order->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        return $cancelledCount;
    }

    /**
     * Retrieve payment intent
     */
    public function retrievePaymentIntent(string $paymentIntentId): PaymentIntent
    {
        return PaymentIntent::retrieve($paymentIntentId);
    }

    /**
     * Get payment analytics
     */
    public function getPaymentAnalytics(?\DateTime $startDate = null, ?\DateTime $endDate = null): array
    {
        $query = PaymentTransaction::query();

        if ($startDate) {
            $query->where('created_at', '>=', $startDate);
        }

        if ($endDate) {
            $query->where('created_at', '<=', $endDate);
        }

        $transactions = $query->get();

        return [
            'total_transactions' => $transactions->count(),
            'successful_payments' => $transactions->where('transaction_type', 'charge')
                ->where('status', 'succeeded')->count(),
            'failed_payments' => $transactions->where('transaction_type', 'charge')
                ->where('status', 'failed')->count(),
            'total_refunds' => $transactions->where('transaction_type', 'refund')->count(),
            'total_revenue' => $transactions->where('transaction_type', 'charge')
                ->where('status', 'succeeded')->sum('amount'),
            'total_refunded' => $transactions->where('transaction_type', 'refund')
                ->where('status', 'succeeded')->sum('amount'),
            'success_rate' => $this->calculateSuccessRate($transactions),
        ];
    }

    /**
     * Generate idempotency key for payment intent
     */
    private function generateIdempotencyKey(Order $order): string
    {
        return 'order_' . $order->id . '_' . $order->created_at->timestamp;
    }

    /**
     * Convert amount to Stripe format (cents)
     */
    private function convertToStripeAmount(float $amount): int
    {
        return (int) round($amount * 100);
    }

    /**
     * Map refund reason to Stripe format
     */
    private function mapRefundReason(?string $reason): string
    {
        if (!$reason) {
            return 'requested_by_customer';
        }

        $mapping = [
            'duplicate' => 'duplicate',
            'fraudulent' => 'fraudulent',
            'customer_request' => 'requested_by_customer',
        ];

        return $mapping[$reason] ?? 'requested_by_customer';
    }

    /**
     * Calculate payment success rate
     */
    private function calculateSuccessRate($transactions): float
    {
        $charges = $transactions->where('transaction_type', 'charge');
        
        if ($charges->isEmpty()) {
            return 0;
        }

        $successful = $charges->where('status', 'succeeded')->count();
        $total = $charges->count();

        return round(($successful / $total) * 100, 2);
    }
}
