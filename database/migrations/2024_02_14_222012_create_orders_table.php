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
        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->date('date');
            $table->string('project_type');
            $table->string('file_type');
            $table->string('support');
            $table->date('deadline');
            $table->string('status');
            $table->uuid('user_id');
            $table->foreign('user_id')->references('user_id')->on('users'); // Clé étrangère faisant référence à users.id
            $table->uuid('order_id')->unique();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('orders');
    }
};
