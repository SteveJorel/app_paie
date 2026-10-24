<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('countries', function (Blueprint $table) {
            $table->string('code', 2)->primary();
            $table->string('name', 100);
            $table->string('status', 20)->default('active');
            $table->timestamps();
        });

        Schema::create('currencies', function (Blueprint $table) {
            $table->string('code', 3)->primary();
            $table->string('name', 100);
            $table->unsignedSmallInteger('minor_unit_factor')->default(1);
            $table->string('status', 20)->default('active');
            $table->timestamps();
        });

        Schema::create('payment_methods', function (Blueprint $table) {
            $table->string('code', 30)->primary();
            $table->string('name', 100);
            $table->string('status', 20)->default('active');
            $table->timestamps();
        });

        Schema::create('country_payment_methods', function (Blueprint $table) {
            $table->id();
            $table->string('country_code', 2);
            $table->string('payment_method_code', 30);
            $table->string('status', 20)->default('pending');
            $table->timestamps();

            $table->foreign('country_code')->references('code')->on('countries')->restrictOnDelete();
            $table->foreign('payment_method_code')->references('code')->on('payment_methods')->restrictOnDelete();
            $table->unique(['country_code', 'payment_method_code']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('country_payment_methods');
        Schema::dropIfExists('payment_methods');
        Schema::dropIfExists('currencies');
        Schema::dropIfExists('countries');
    }
};
