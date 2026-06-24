<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Job Screening Questions Table
 * 
 * Stores custom screening questions that employers add to job postings.
 * Candidates must answer these questions when applying.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('job_screening_questions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('job_posting_id')->constrained('job_postings')->onDelete('cascade');
            $table->text('question_text');
            $table->enum('question_type', ['text', 'yes_no', 'multiple_choice'])->default('text');
            $table->json('options')->nullable(); // For multiple choice options
            $table->boolean('is_required')->default(true);
            $table->integer('sort_order')->default(0);
            $table->timestamps();

            $table->index(['job_posting_id', 'sort_order']);
        });

        // Store candidate answers to screening questions
        Schema::create('screening_question_answers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('job_application_id')->constrained('job_applications')->onDelete('cascade');
            $table->foreignId('screening_question_id')->constrained('job_screening_questions')->onDelete('cascade');
            $table->text('answer');
            $table->timestamps();

            $table->unique(['job_application_id', 'screening_question_id'], 'unique_application_question');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('screening_question_answers');
        Schema::dropIfExists('job_screening_questions');
    }
};
