<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('billing_plans', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('type')->default('time'); // time, data, unlimited
            $table->integer('time_limit')->nullable(); // in minutes
            $table->bigInteger('data_limit')->nullable(); // in bytes
            $table->string('rate_limit')->nullable(); // e.g., "2M/1M"
            $table->decimal('price', 10, 2);
            $table->string('validity_period')->default('1d'); // 1h, 1d, 7d, 30d
            $table->boolean('is_active')->default(true);
            $table->text('description')->nullable();
            $table->json('additional_settings')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('billing_plans');
    }
};