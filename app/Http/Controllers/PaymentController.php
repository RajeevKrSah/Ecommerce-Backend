<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Services\PaymentService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class PaymentController extends Controller
{
    protected $paymentService;

    public function __construct(PaymentService $paymentService)
    {
        $this->paymentService = $paymentService;
    }

    /**
     * Create payment intent for an order
     */
    public function createPaymentIntent(Request $request, Order $order)
    {
        // Verify order belongs to user
        if ($order->user_id !== $request->user()->id) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        // Check if order is already paid
        if ($order->payment_status === 'paid') {
            return response()->json(['message' => 'Order already paid'], 422);
        }

        // Check if payment has expired
        if ($order->payment_status === 'expired') {
            return response()->json(['message' => 'Payment has expired. Please create a new order.'], 422);
        }

        try {
            $paymentIntent = $this->paymentService->createPaymentIntent($order);

            return response()->json([
                'clientSecret' => $paymentIntent->client_secret,
                'paymentIntentId' => $paymentIntent->id,
                'expiresAt' => $order->payment_expires_at,
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to create payment intent', [
                'order_id' => $order->id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'message' => 'Failed to initialize payment',
                'error' => config('app.debug') ? $e->getMessage() : 'An error occurred',
            ], 500);
        }
    }

    /**
     * Stripe webhook handler
     */
    public function webhook(Request $request)
    {
        $payload = $request->getContent();
        $signature = $request->header('Stripe-Signature');

        try {
            $event = $this->paymentService->verifyWebhook($payload, $signature);
        } catch (\Exception $e) {
            Log::error('Webhook signature verification failed', ['error' => $e->getMessage()]);
            return response()->json(['error' => 'Invalid signature'], 400);
        }

        // Handle the event
        try {
            switch ($event->type) {
                case 'payment_intent.succeeded':
                    $this->paymentService->handlePaymentSuccess($event->data->object);
                    break;

                case 'payment_intent.payment_failed':
                    $this->paymentService->handlePaymentFailed($event->data->object);
                    break;

                case 'charge.refunded':
                    Log::info('Refund webhook received', [
                        'charge_id' => $event->data->object->id,
                    ]);
                    break;

                default:
                    Log::info('Unhandled webhook event', ['type' => $event->type]);
            }

            return response()->json(['status' => 'success']);
        } catch (\Exception $e) {
            Log::error('Webhook processing failed', [
                'event_type' => $event->type,
                'error' => $e->getMessage(),
            ]);
            
            // Return 200 to prevent Stripe from retrying
            return response()->json(['status' => 'error', 'message' => 'Processing failed'], 200);
        }
    }

    /**
     * Get payment status for an order
     */
    public function getPaymentStatus(Request $request, Order $order)
    {
        if ($order->user_id !== $request->user()->id) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        return response()->json([
            'payment_status' => $order->payment_status,
            'payment_intent_id' => $order->payment_intent_id,
            'paid_at' => $order->paid_at,
            'payment_expires_at' => $order->payment_expires_at,
            'refunded_amount' => $order->refunded_amount,
            'refunded_at' => $order->refunded_at,
        ]);
    }

    /**
     * Confirm payment status by checking with Stripe
     * This is used as a fallback when webhooks aren't received
     */
    public function confirmPayment(Request $request, Order $order)
    {
        Log::info('Payment confirmation requested', [
            'order_id' => $order->id,
            'current_payment_status' => $order->payment_status,
            'payment_intent_id' => $order->payment_intent_id,
        ]);

        if ($order->user_id !== $request->user()->id) {
            Log::warning('Unauthorized payment confirmation attempt', [
                'order_id' => $order->id,
                'user_id' => $request->user()->id,
                'order_user_id' => $order->user_id,
            ]);
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        if (!$order->payment_intent_id) {
            Log::error('No payment intent found for order', [
                'order_id' => $order->id,
            ]);
            return response()->json(['message' => 'No payment intent found'], 422);
        }

        if ($order->payment_status === 'paid') {
            Log::info('Payment already confirmed', [
                'order_id' => $order->id,
            ]);
            return response()->json([
                'message' => 'Payment already confirmed',
                'payment_status' => 'paid',
            ]);
        }

        try {
            // Retrieve payment intent from Stripe
            $paymentIntent = $this->paymentService->retrievePaymentIntent($order->payment_intent_id);
            
            Log::info('Retrieved payment intent from Stripe', [
                'order_id' => $order->id,
                'payment_intent_id' => $paymentIntent->id,
                'status' => $paymentIntent->status,
                'amount' => $paymentIntent->amount,
            ]);

            // If payment succeeded, process it
            if ($paymentIntent->status === 'succeeded') {
                Log::info('Payment succeeded, processing...', [
                    'order_id' => $order->id,
                ]);
                
                $this->paymentService->handlePaymentSuccess($paymentIntent);
                
                Log::info('Payment processed successfully', [
                    'order_id' => $order->id,
                    'new_payment_status' => $order->fresh()->payment_status,
                ]);
                
                return response()->json([
                    'message' => 'Payment confirmed successfully',
                    'payment_status' => 'paid',
                ]);
            }

            // Return current status
            Log::info('Payment not yet succeeded', [
                'order_id' => $order->id,
                'stripe_status' => $paymentIntent->status,
            ]);
            
            return response()->json([
                'message' => 'Payment not yet completed',
                'payment_status' => $order->fresh()->payment_status,
                'stripe_status' => $paymentIntent->status,
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to confirm payment', [
                'order_id' => $order->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'message' => 'Failed to confirm payment',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Process full refund (Admin only)
     */
    public function refundOrder(Request $request, Order $order)
    {
        $validated = $request->validate([
            'reason' => 'nullable|string|max:500',
        ]);

        try {
            $refund = $this->paymentService->refundPayment($order, $validated['reason'] ?? null);

            return response()->json([
                'message' => 'Refund processed successfully',
                'refund' => [
                    'id' => $refund->id,
                    'amount' => $refund->amount / 100,
                    'status' => $refund->status,
                ],
                'order' => [
                    'payment_status' => $order->fresh()->payment_status,
                    'refunded_amount' => $order->fresh()->refunded_amount,
                ],
            ]);
        } catch (\Exception $e) {
            Log::error('Refund failed', [
                'order_id' => $order->id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'message' => 'Refund failed',
                'error' => $e->getMessage(),
            ], 422);
        }
    }

    /**
     * Process partial refund (Admin only)
     */
    public function partialRefund(Request $request, Order $order)
    {
        $validated = $request->validate([
            'amount' => 'required|numeric|min:0.01',
            'reason' => 'nullable|string|max:500',
        ]);

        try {
            $refund = $this->paymentService->partialRefund(
                $order,
                $validated['amount'],
                $validated['reason'] ?? null
            );

            return response()->json([
                'message' => 'Partial refund processed successfully',
                'refund' => [
                    'id' => $refund->id,
                    'amount' => $refund->amount / 100,
                    'status' => $refund->status,
                ],
                'order' => [
                    'payment_status' => $order->fresh()->payment_status,
                    'refunded_amount' => $order->fresh()->refunded_amount,
                    'remaining_refundable' => $order->fresh()->getRemainingRefundableAmount(),
                ],
            ]);
        } catch (\Exception $e) {
            Log::error('Partial refund failed', [
                'order_id' => $order->id,
                'amount' => $validated['amount'],
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'message' => 'Partial refund failed',
                'error' => $e->getMessage(),
            ], 422);
        }
    }

    /**
     * Get payment analytics (Admin only)
     */
    public function analytics(Request $request)
    {
        $validated = $request->validate([
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
        ]);

        $startDate = isset($validated['start_date']) ? new \DateTime($validated['start_date']) : null;
        $endDate = isset($validated['end_date']) ? new \DateTime($validated['end_date']) : null;

        $analytics = $this->paymentService->getPaymentAnalytics($startDate, $endDate);

        return response()->json($analytics);
    }

    /**
     * Get payment transactions for an order
     */
    public function getTransactions(Request $request, Order $order)
    {
        // Allow user to see their own order transactions, or admin to see any
        if ($order->user_id !== $request->user()->id && $request->user()->role !== 'admin') {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $transactions = $order->transactions()
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json([
            'transactions' => $transactions,
        ]);
    }
}
