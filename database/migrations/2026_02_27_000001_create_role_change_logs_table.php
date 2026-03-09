<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('role_change_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('performed_by')->constrained('users')->onDelete('cascade');
            $table->foreignId('target_user_id')->constrained('users')->onDelete('cascade');
            $table->string('old_role');
            $table->string('new_role');
            $table->string('ip_address', 45);
            $table->text('user_agent')->nullable();
            $table->text('reason')->nullable();
            $table->timestamps();

            $table->index(['target_user_id', 'created_at']);
            $table->index(['performed_by', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('role_change_logs');
    }
};
