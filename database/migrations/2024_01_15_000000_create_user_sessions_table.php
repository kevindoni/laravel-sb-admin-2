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
        Schema::create('user_sessions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('router_id')->constrained()->onDelete('cascade');
            $table->foreignId('hotspot_user_id')->constrained()->onDelete('cascade');
            $table->string('session_id');
            $table->string('username');
            $table->string('nas_ip');
            $table->string('framed_ip');
            $table->string('calling_station_id')->nullable(); // MAC address
            $table->timestamp('started_at');
            $table->timestamp('last_update')->nullable();
            $table->integer('session_time')->default(0); // in seconds
            $table->bigInteger('upload_bytes')->default(0);
            $table->bigInteger('download_bytes')->default(0);
            $table->string('terminate_cause')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            
            $table->index(['router_id', 'username']);
            $table->index(['session_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('user_sessions');
    }
};