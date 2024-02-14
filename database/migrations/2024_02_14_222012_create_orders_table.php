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
            $table->date('deadline');
            $table->string('status');
            $table->string('init_folder_path');
            $table->foreignId('user_id')->constrained(); // Clé étrangère faisant référence à users.id
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
