<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('receipts', function (Blueprint $table) {
            $table->id();
            $table->string('receipt_number', 40)->unique();
            $table->foreignId('payment_id')->constrained('payments')->restrictOnDelete();
            $table->foreignId('user_id')->constrained('users')->restrictOnDelete();
            $table->string('receipt_type', 20);
            $table->string('status', 20);
            $table->jsonb('snapshot');
            $table->timestamp('issued_at', 6);
            $table->timestamp('created_at', 6)->useCurrent();

            // Un seul reçu de paiement par paiement.
            $table->unique(['payment_id', 'receipt_type']);
            $table->index(['user_id', 'issued_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('receipts');
    }
};
