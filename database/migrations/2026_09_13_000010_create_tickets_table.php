<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tickets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_item_id')->constrained()->cascadeOnDelete();
            $table->foreignId('ticket_type_id')->constrained();
            $table->foreignId('event_id')->constrained();
            $table->foreignId('user_id')->constrained();

            // Identifiant public du billet (affiché/scanné), distinct de l'id auto-incrémenté
            $table->uuid('code')->unique();
            // Token signé (HMAC + expiration) encodé dans le QR, jamais l'id ou le code seul
            $table->text('qr_token');

            $table->enum('status', ['valid', 'used', 'cancelled', 'refunded'])->default('valid');
            $table->timestamp('used_at')->nullable();
            $table->foreignId('checked_in_by')->nullable()->constrained('users')->nullOnDelete();

            $table->timestamps();

            $table->index(['event_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tickets');
    }
};
