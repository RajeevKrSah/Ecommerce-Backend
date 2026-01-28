<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->string('currency', 3)->default('usd')->after('total');
            $table->timestamp('payment_expires_at')->nullable()->after('paid_at');
            $table->decimal('refunded_amount', 10, 2)->default(0)->after('total');
            $table->timestamp('refunded_at')->nullable()->after('paid_at');
            $table->string('refund_reason')->nullable()->after('refunded_at');
            
            $table->index('payment_expires_at');
            $table->index(['payment_status', 'payment_expires_at']);
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn([
                'currency',
                'payment_expires_at',
                'refunded_amount',
                'refunded_at',
                'refund_reason'
            ]);
        });
    }
};
