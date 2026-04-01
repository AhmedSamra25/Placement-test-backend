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
        Schema::create('test_submissions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_id')->constrained('students')->cascadeOnDelete();

            // Objective / structured answers stored as JSONB
            $table->jsonb('answers')->nullable();

            // Media paths
            $table->string('writing_essay_path')->nullable();
            $table->jsonb('speaking_audio_paths')->nullable();
            $table->string('pronunciation_audio_path')->nullable();

            // Skill scores (0-100, 2 decimal places)
            $table->decimal('reading_score', 5, 2)->nullable();
            $table->decimal('writing_score', 5, 2)->nullable();
            $table->decimal('speaking_score', 5, 2)->nullable();
            $table->decimal('listening_score', 5, 2)->nullable();
            $table->decimal('vocabulary_score', 5, 2)->nullable();
            $table->decimal('grammar_score', 5, 2)->nullable();
            $table->decimal('pronunciation_score', 5, 2)->nullable();

            // AI analysis status
            $table->enum('ai_status', ['pending', 'processing', 'completed', 'failed'])->default('pending');
            $table->text('ai_feedback')->nullable();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('test_submissions');
    }
};
