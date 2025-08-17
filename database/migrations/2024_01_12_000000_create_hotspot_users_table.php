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
        Schema::create('hotspot_users', function (Blueprint $table) {
            $table->id();
            $table->foreignId('router_id')->constrained()->onDelete('cascade');
            $table->foreignId('billing_plan_id')->constrained()->onDelete('cascade');
            $table->string('username')->unique();
            $table->string('password');
            $table->string('profile');
            $table->string('status')->default('active'); // active, disabled, expired
            $table->timestamp('expires_at')->nullable();
            $table->integer('time_used')->default(0); // in seconds
            $table->bigInteger('data_used')->default(0); // in bytes
            $table->timestamp('first_login_at')->nullable();
            $table->timestamp('last_login_at')->nullable();
            $table->string('last_ip')->nullable();
            $table->string('last_mac')->nullable();
            $table->text('comment')->nullable();
            $table->decimal('balance', 10, 2)->default(0);
            $table->boolean('is_voucher')->default(false);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('hotspot_users');
    }
};