<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('suppliers', function (Blueprint $table) {
            $table->id();
            $table->string('business_name');
            $table->string('country_code', 2);
            $table->string('category');
            $table->string('settlement_currency', 3);
            $table->string('contact_email')->nullable();
            $table->string('contact_phone')->nullable();
            $table->foreignId('owner_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('status')->default('pending');
            $table->timestamps();

            $table->index(['status', 'country_code']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('suppliers');
    }
};