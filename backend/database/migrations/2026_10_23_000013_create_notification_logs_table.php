<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('notification_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('payment_id')->nullable()->constrained('payments')->nullOnDelete();
            $table->string('type', 60);
            $table->string('channel', 20)->default('in_app');
            $table->jsonb('payload')->nullable();
            $table->string('status', 20)->default('queued');
            $table->timestamp('created_at', 6)->useCurrent();

            $table->index(['user_id', 'created_at']);
            $table->index('payment_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('notification_logs');
    }
};
