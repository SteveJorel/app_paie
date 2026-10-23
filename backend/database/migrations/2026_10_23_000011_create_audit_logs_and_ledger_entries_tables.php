<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('audit_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('actor_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('actor_role', 20)->nullable();
            $table->string('action', 100);
            $table->string('auditable_type', 150);
            $table->unsignedBigInteger('auditable_id')->nullable();
            $table->jsonb('changes')->nullable();
            $table->timestamp('created_at', 6)->useCurrent();

            $table->index(['auditable_type', 'auditable_id']);
        });

        Schema::create('ledger_entries', function (Blueprint $table) {
            $table->id();
            $table->string('entry_type', 30);
            $table->foreignId('payment_id')->nullable()->constrained('payments')->restrictOnDelete();
            $table->string('beneficiary_type', 20);
            $table->unsignedBigInteger('beneficiary_id')->nullable();
            $table->bigInteger('amount_minor');
            $table->string('currency', 3);
            $table->timestamp('created_at', 6)->useCurrent();

            $table->index(['beneficiary_type', 'beneficiary_id']);
            $table->index('payment_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ledger_entries');
        Schema::dropIfExists('audit_logs');
    }
};
