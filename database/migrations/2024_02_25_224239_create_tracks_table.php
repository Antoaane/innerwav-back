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
        Schema::create('tracks', function (Blueprint $table) {
            $table->id();
            $table->string('user_name');
            $table->string('artists')->nullable();
            $table->string('name');
            $table->string('spec_ref')->nullable();
            $table->string('file_type');
            $table->uuid('order_id');
            $table->foreign('order_id')->references('order_id')->on('orders');
            $table->uuid('user_id');
            $table->foreign('user_id')->references('user_id')->on('users');
            $table->uuid('track_id')->unique();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tracks');
    }
};
