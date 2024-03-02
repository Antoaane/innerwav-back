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
        Schema::create('feedbacks', function (Blueprint $table) {
            $table->id();
            $table->date('date');
            $table->text('client_message')->nullable();
            $table->text('seller_message')->nullable();
            $table->string('status');
            $table->string('folder_path');
            $table->uuid('order_id');
            $table->foreign('order_id')->references('order_id')->on('orders'); // Clé étrangère faisant référence à orders.id
            $table->uuid('feedback_id')->unique();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('feedbacks');
    }
};
