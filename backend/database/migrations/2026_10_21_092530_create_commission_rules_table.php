<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('commission_rules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('supplier_id')->nullable()->constrained('suppliers')->restrictOnDelete();
            $table->string('currency', 3);
            $table->string('type', 20);
            $table->unsignedInteger('percentage_bps')->default(0);
            $table->unsignedBigInteger('fixed_minor')->default(0);
            $table->timestamp('valid_from', 6);
            $table->timestamp('valid_until', 6)->nullable();
            $table->unsignedInteger('version');
            $table->string('status', 20)->default('active');
            $table->timestamps();

            $table->unique(['supplier_id', 'currency', 'version']);
            $table->index(['currency', 'status', 'valid_from']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('commission_rules');
    }
};
