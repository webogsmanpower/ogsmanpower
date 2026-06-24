<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Migration: Create job_applications table
 * 
 * Purpose: Track seeker applications with pipeline status.
 * Supports the full hiring workflow: Applied -> Shortlisted -> Interview -> Contract -> Hired/Rejected
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('job_applications', function (Blueprint $table) {
            $table->id();
            
            // Core Relationships
            $table->foreignId('job_posting_id')
                ->constrained('job_postings')
                ->cascadeOnDelete()
                ->comment('The job being applied to');
            $table->foreignId('seeker_id')
                ->constrained('seekers')
                ->cascadeOnDelete()
                ->comment('The applicant');
            $table->foreignId('employer_id')
                ->constrained('employers')
                ->cascadeOnDelete()
                ->comment('Employer (denormalized for quick queries)');
            
            // Application Content
            $table->json('resume_snapshot')
                ->nullable()
                ->comment('Snapshot of resume at application time');
            $table->text('cover_letter')
                ->nullable()
                ->comment('Cover letter text');
            $table->json('answers')
                ->nullable()
                ->comment('Answers to screening questions');
            
            // Pipeline Status
            $table->enum('status', [
                'applied',
                'reviewed',
                'shortlisted',
                'interview_scheduled',
                'interviewed',
                'contract_sent',
                'hired',
                'rejected',
                'withdrawn'
            ])->default('applied')
                ->comment('Current application status');
            $table->timestamp('status_changed_at')
                ->nullable()
                ->comment('When status last changed');
            $table->foreignId('status_changed_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete()
                ->comment('Who changed the status');
            
            // Rejection Details
            $table->string('rejection_reason')
                ->nullable()
                ->comment('Reason if rejected');
            $table->text('rejection_feedback')
                ->nullable()
                ->comment('Detailed feedback for rejection');
            
            // Internal Notes & Rating
            $table->text('notes')
                ->nullable()
                ->comment('Internal notes from employer');
            $table->unsignedTinyInteger('rating')
                ->nullable()
                ->comment('1-5 star rating');
            $table->json('tags')
                ->nullable()
                ->comment('Custom tags for categorization');
            
            // Source Tracking (for future Agent/Agency integration)
            $table->enum('source', ['direct', 'agent', 'agency', 'referral', 'imported'])
                ->default('direct')
                ->comment('Application source');
            $table->unsignedBigInteger('agent_id')
                ->nullable()
                ->comment('Future: Agent who referred');
            $table->unsignedBigInteger('agency_id')
                ->nullable()
                ->comment('Future: Agency who referred');
            $table->string('referral_code')
                ->nullable()
                ->comment('Referral tracking code');
            
            // Flags
            $table->boolean('is_favorite')
                ->default(false)
                ->comment('Marked as favorite by employer');
            $table->boolean('is_viewed')
                ->default(false)
                ->comment('Has employer viewed this application');
            $table->timestamp('viewed_at')
                ->nullable()
                ->comment('When first viewed');
            
            // Timestamps
            $table->timestamps();
            
            // Indexes
            $table->index('job_posting_id', 'idx_applications_job');
            $table->index('seeker_id', 'idx_applications_seeker');
            $table->index('employer_id', 'idx_applications_employer');
            $table->index('status', 'idx_applications_status');
            $table->index('source', 'idx_applications_source');
            $table->index(['employer_id', 'status'], 'idx_applications_employer_status');
            $table->index(['job_posting_id', 'status'], 'idx_applications_job_status');
            $table->unique(['job_posting_id', 'seeker_id'], 'idx_applications_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('job_applications');
    }
};
