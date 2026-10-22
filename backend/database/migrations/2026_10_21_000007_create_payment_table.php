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
            $table->string('reference', 40)->unique();
            $table->foreignId('user_id')->constrained('users')->restrictOnDelete();
            $table->foreignId('supplier_id')->constrained('suppliers')->restrictOnDelete();
            $table->string('description');
            $table->unsignedBigInteger('amount_minor');
            $table->string('currency', 3);
            $table->string('status', 30)->default('created');
            $table->timestamps();

            $table->index(['user_id', 'status']);
            $table->index('supplier_id');
        });

        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->string('reference', 40)->unique();
            $table->foreignId('order_id')->constrained('orders')->restrictOnDelete();
            $table->foreignId('user_id')->constrained('users')->restrictOnDelete();
            $table->string('provider', 30);
            $table->string('provider_reference')->nullable();
            $table->unsignedBigInteger('amount_minor');
            $table->string('currency', 3);
            $table->string('payment_method', 30);
            $table->string('status', 30)->default('created');
            $table->string('idempotency_key', 100);
            $table->timestamp('confirmed_at')->nullable();
            $table->timestamps();

            $table->unique(['user_id', 'idempotency_key']);
            $table->unique(['provider', 'provider_reference']);
            $table->index(['order_id', 'status']);
        });

        Schema::create('allocations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('payment_id')->constrained('payments')->restrictOnDelete();
            $table->string('beneficiary_type', 20);
            $table->unsignedBigInteger('beneficiary_id')->nullable();
            $table->unsignedBigInteger('gross_amount_minor');
            $table->unsignedBigInteger('fees_minor')->default(0);
            $table->unsignedBigInteger('net_amount_minor');
            $table->string('currency', 3);
            $table->string('status', 20)->default('pending');
            $table->foreignId('commission_rule_id')->nullable()->constrained('commission_rules')->restrictOnDelete();
            $table->unsignedInteger('commission_rule_version')->nullable();
            $table->timestamps();

            $table->index('payment_id');
            $table->index(['beneficiary_type', 'beneficiary_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('allocations');
        Schema::dropIfExists('payments');
        Schema::dropIfExists('orders');
    }
};
