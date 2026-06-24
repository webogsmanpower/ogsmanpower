<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Assessments System Tables
 * 
 * Supports two types of assessments:
 * 1. admin_standard - Created by admins, available to all employers (may be paid)
 * 2. employer_custom - Created by employers for their own jobs
 */
return new class extends Migration
{
    public function up(): void
    {
        // Main assessments table
        Schema::create('assessments', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->text('description')->nullable();
            $table->enum('type', ['admin_standard', 'employer_custom'])->default('employer_custom');
            $table->foreignId('creator_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('employer_id')->nullable()->constrained('employers')->onDelete('cascade');
            $table->decimal('price', 10, 2)->default(0.00); // For admin paid tests
            $table->integer('time_limit_minutes')->default(30);
            $table->integer('passing_score')->default(70); // Percentage required to pass
            $table->boolean('is_active')->default(true);
            $table->boolean('shuffle_questions')->default(false);
            $table->boolean('show_results')->default(true); // Show results to candidate after completion
            $table->string('category')->nullable(); // e.g., "Language", "Technical", "Safety"
            $table->json('settings')->nullable(); // Additional configuration
            $table->timestamps();
            $table->softDeletes();

            $table->index(['type', 'is_active']);
            $table->index('employer_id');
            $table->index('category');
        });

        // Assessment questions
        Schema::create('assessment_questions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('assessment_id')->constrained('assessments')->onDelete('cascade');
            $table->text('question_text');
            $table->enum('question_type', ['multiple_choice', 'true_false', 'text'])->default('multiple_choice');
            $table->json('options'); // Array of options for multiple choice
            $table->string('correct_answer'); // The correct option or answer
            $table->integer('points')->default(1);
            $table->integer('sort_order')->default(0);
            $table->text('explanation')->nullable(); // Explanation shown after answering
            $table->timestamps();

            $table->index(['assessment_id', 'sort_order']);
        });

        // Pivot table: Jobs <-> Assessments
        Schema::create('job_assessments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('job_posting_id')->constrained('job_postings')->onDelete('cascade');
            $table->foreignId('assessment_id')->constrained('assessments')->onDelete('cascade');
            $table->boolean('is_mandatory')->default(true); // Must complete before application is considered
            $table->decimal('price_paid', 10, 2)->default(0.00); // Price paid for this attachment
            $table->timestamps();

            $table->unique(['job_posting_id', 'assessment_id']);
        });

        // Assessment attempts (results)
        Schema::create('assessment_attempts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('assessment_id')->constrained('assessments')->onDelete('cascade');
            $table->foreignId('seeker_id')->constrained('seekers')->onDelete('cascade');
            $table->foreignId('job_application_id')->nullable()->constrained('job_applications')->onDelete('set null');
            $table->integer('score')->default(0);
            $table->integer('total_points')->default(0);
            $table->decimal('percentage', 5, 2)->default(0.00);
            $table->enum('status', ['in_progress', 'completed', 'passed', 'failed', 'expired'])->default('in_progress');
            $table->json('answers')->nullable(); // Store all answers for review
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->integer('time_spent_seconds')->nullable();
            $table->timestamps();

            $table->index(['assessment_id', 'seeker_id']);
            $table->index(['job_application_id']);
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('assessment_attempts');
        Schema::dropIfExists('job_assessments');
        Schema::dropIfExists('assessment_questions');
        Schema::dropIfExists('assessments');
    }
};
