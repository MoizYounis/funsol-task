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
        Schema::create('videos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('video_project_id')->constrained()->onDelete('cascade');
            $table->string('original_filename');
            $table->string('file_path');
            $table->string('file_size');
            $table->string('mime_type');
            $table->integer('duration')->nullable(); // Duration in seconds
            $table->integer('width')->nullable();
            $table->integer('height')->nullable();
            $table->integer('start_time')->default(0); // Trim start time in seconds
            $table->integer('end_time')->nullable(); // Trim end time in seconds
            $table->integer('order')->default(0); // Order in the final video
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('videos');
    }
};
