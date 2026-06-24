<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Migration: Create interviews table
 * 
 * Purpose: Interview scheduling and tracking for the hiring pipeline.
 * Supports phone, video, and in-person interviews with feedback collection.
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('interviews', function (Blueprint $table) {
            $table->id();
            
            // Relationships
            $table->foreignId('job_application_id')
                ->constrained('job_applications')
                ->cascadeOnDelete()
                ->comment('Related job application');
            $table->foreignId('employer_id')
                ->constrained('employers')
                ->cascadeOnDelete()
                ->comment('Employer (denormalized for quick queries)');
            $table->foreignId('seeker_id')
                ->constrained('seekers')
                ->cascadeOnDelete()
                ->comment('Candidate being interviewed');
            $table->foreignId('scheduled_by')
                ->constrained('users')
                ->cascadeOnDelete()
                ->comment('User who scheduled the interview');
            
            // Interview Details
            $table->enum('interview_type', ['phone', 'video', 'in_person'])
                ->default('video')
                ->comment('Type of interview');
            $table->string('title')
                ->nullable()
                ->comment('Interview title/round name');
            $table->unsignedTinyInteger('round_number')
                ->default(1)
                ->comment('Interview round (1st, 2nd, etc.)');
            
            // Scheduling
            $table->dateTime('scheduled_at')
                ->comment('Scheduled date and time');
            $table->unsignedSmallInteger('duration_minutes')
                ->default(30)
                ->comment('Expected duration in minutes');
            $table->string('timezone', 50)
                ->default('UTC')
                ->comment('Timezone for the interview');
            
            // Location/Meeting Info
            $table->string('location')
                ->nullable()
                ->comment('Physical location for in-person');
            $table->string('meeting_link')
                ->nullable()
                ->comment('Video call link (Zoom, Teams, etc.)');
            $table->string('meeting_id')
                ->nullable()
                ->comment('Meeting ID if applicable');
            $table->string('meeting_password')
                ->nullable()
                ->comment('Meeting password if applicable');
            $table->text('instructions')
                ->nullable()
                ->comment('Instructions for the candidate');
            
            // Interviewers
            $table->json('interviewers')
                ->nullable()
                ->comment('Array of interviewer user_ids');
            
            // Status
            $table->enum('status', ['scheduled', 'confirmed', 'in_progress', 'completed', 'cancelled', 'rescheduled', 'no_show'])
                ->default('scheduled')
                ->comment('Interview status');
            $table->timestamp('candidate_confirmed_at')
                ->nullable()
                ->comment('When candidate confirmed');
            $table->string('cancellation_reason')
                ->nullable()
                ->comment('Reason if cancelled');
            $table->foreignId('cancelled_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete()
                ->comment('Who cancelled');
            
            // Pre-Interview
            $table->text('notes')
                ->nullable()
                ->comment('Pre-interview notes/agenda');
            $table->json('questions')
                ->nullable()
                ->comment('Prepared interview questions');
            
            // Post-Interview Feedback
            $table->json('feedback')
                ->nullable()
                ->comment('Structured feedback from interviewers');
            $table->text('feedback_summary')
                ->nullable()
                ->comment('Summary of interview feedback');
            $table->unsignedTinyInteger('rating')
                ->nullable()
                ->comment('Overall rating 1-5');
            $table->enum('outcome', ['pass', 'fail', 'pending', 'on_hold'])
                ->nullable()
                ->comment('Interview outcome');
            $table->text('recommendation')
                ->nullable()
                ->comment('Interviewer recommendation');
            
            // Reminders
            $table->boolean('reminder_sent')
                ->default(false)
                ->comment('Whether reminder was sent');
            $table->timestamp('reminder_sent_at')
                ->nullable()
                ->comment('When reminder was sent');
            
            // Timestamps
            $table->timestamps();
            
            // Indexes
            $table->index('job_application_id', 'idx_interviews_application');
            $table->index('employer_id', 'idx_interviews_employer');
            $table->index('seeker_id', 'idx_interviews_seeker');
            $table->index('scheduled_at', 'idx_interviews_scheduled');
            $table->index('status', 'idx_interviews_status');
            $table->index(['employer_id', 'scheduled_at'], 'idx_interviews_employer_scheduled');
            $table->index(['seeker_id', 'scheduled_at'], 'idx_interviews_seeker_scheduled');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('interviews');
    }
};
