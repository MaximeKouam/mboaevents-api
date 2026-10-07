<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->string('reference')->unique();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('event_id')->constrained()->cascadeOnDelete();

            $table->enum('status', [
                'pending', 'paid', 'cancelled', 'refunded', 'partially_refunded',
            ])->default('pending');

            $table->unsignedInteger('total_amount');
            $table->string('currency', 3)->default('XAF');
            $table->string('payment_method')->nullable();

            $table->timestamp('cancelled_at')->nullable();
            $table->unsignedInteger('refund_amount')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('orders');
    }
};
