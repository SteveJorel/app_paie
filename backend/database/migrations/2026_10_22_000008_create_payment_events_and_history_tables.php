<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payment_events', function (Blueprint $table) {
            $table->id();
            $table->string('provider', 30);
            $table->string('event_id', 100);
            $table->string('event_type', 60);
            $table->foreignId('payment_id')->nullable()->constrained('payments')->restrictOnDelete();
            $table->string('status', 20);
            $table->jsonb('payload');
            $table->timestamp('processed_at', 6)->nullable();
            $table->timestamps();

            // Un même événement du prestataire ne peut être enregistré qu'une fois.
            $table->unique(['provider', 'event_id']);
            $table->index('payment_id');
        });

        Schema::create('transaction_history', function (Blueprint $table) {
            $table->id();
            $table->foreignId('payment_id')->constrained('payments')->restrictOnDelete();
            $table->string('previous_status', 30);
            $table->string('new_status', 30);
            $table->string('event_type', 60);
            $table->string('event_reference', 100)->nullable();
            $table->string('actor_type', 20);
            $table->unsignedBigInteger('actor_id')->nullable();
            $table->timestamp('created_at', 6)->useCurrent();

            $table->index(['payment_id', 'id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('transaction_history');
        Schema::dropIfExists('payment_events');
    }
};
