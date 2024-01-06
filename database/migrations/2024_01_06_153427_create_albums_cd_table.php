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
        Schema::create('albums_cd', function (Blueprint $table) {
            $table->id();
            $table->string('titre_id')->unique();
            $table->string('project_id');
            $table->string('title');
            $table->string('artist');
            $table->text('referance');
            $table->string('song_path_1');
            $table->string('song_path_2');
            $table->string('genre');
            $table->timestamps();

            $table->foreign('project_id')->references('project_id')->on('projects')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('albums_cd');
    }
};
