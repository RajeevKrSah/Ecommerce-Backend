<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PaymentTransaction extends Model
{
    protected $fillable = [
        'order_id',
        'transaction_type',
        'stripe_id',
        'amount',
        'currency',
        'status',
        'payment_method',
        'failure_reason',
        'metadata',
        'processed_at',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'metadata' => 'array',
        'processed_at' => 'datetime',
    ];

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    /**
     * Create a transaction log entry (or update if exists)
     */
    public static function log(
        int $orderId,
        string $type,
        string $stripeId,
        float $amount,
        string $status,
        ?string $paymentMethod = null,
        ?string $failureReason = null,
        ?array $metadata = null
    ): self {
        return self::updateOrCreate(
            [
                'stripe_id' => $stripeId,
                'transaction_type' => $type,
            ],
            [
                'order_id' => $orderId,
                'amount' => $amount,
                'status' => $status,
                'payment_method' => $paymentMethod,
                'failure_reason' => $failureReason,
                'metadata' => $metadata,
                'processed_at' => now(),
            ]
        );
    }
}
