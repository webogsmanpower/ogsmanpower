<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Migration: Create job_postings table
 * 
 * Purpose: Store employer job listings. Named 'job_postings' to avoid conflict
 * with Laravel's default 'jobs' queue table.
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('job_postings', function (Blueprint $table) {
            $table->id();
            
            // Relationships
            $table->foreignId('employer_id')
                ->constrained('employers')
                ->cascadeOnDelete()
                ->comment('Employer posting this job');
            $table->foreignId('created_by')
                ->constrained('users')
                ->cascadeOnDelete()
                ->comment('User who created this posting');
            
            // Basic Information
            $table->string('title')
                ->comment('Job title');
            $table->string('title_ar')
                ->nullable()
                ->comment('Arabic job title');
            $table->string('slug')
                ->unique()
                ->comment('URL-friendly slug');
            $table->text('description')
                ->comment('Full job description');
            $table->text('description_ar')
                ->nullable()
                ->comment('Arabic job description');
            
            // Requirements & Responsibilities
            $table->json('requirements')
                ->nullable()
                ->comment('Array of job requirements');
            $table->json('responsibilities')
                ->nullable()
                ->comment('Array of job responsibilities');
            
            // Job Type & Level
            $table->enum('job_type', ['full_time', 'part_time', 'contract', 'temporary', 'internship'])
                ->default('full_time')
                ->comment('Employment type');
            $table->enum('experience_level', ['entry', 'junior', 'mid', 'senior', 'executive'])
                ->default('mid')
                ->comment('Required experience level');
            $table->unsignedTinyInteger('experience_years_min')
                ->nullable()
                ->comment('Minimum years of experience');
            $table->unsignedTinyInteger('experience_years_max')
                ->nullable()
                ->comment('Maximum years of experience');
            
            // Salary Information
            $table->decimal('salary_min', 12, 2)
                ->nullable()
                ->comment('Minimum salary');
            $table->decimal('salary_max', 12, 2)
                ->nullable()
                ->comment('Maximum salary');
            $table->string('salary_currency', 3)
                ->default('USD')
                ->comment('Salary currency code');
            $table->enum('salary_period', ['hourly', 'daily', 'weekly', 'monthly', 'yearly'])
                ->default('monthly')
                ->comment('Salary payment period');
            $table->boolean('is_salary_visible')
                ->default(true)
                ->comment('Show salary in public listing');
            
            // Location
            $table->string('location_country', 3)
                ->comment('Work country code');
            $table->string('location_city')
                ->nullable()
                ->comment('Work city');
            $table->string('location_address')
                ->nullable()
                ->comment('Specific work address');
            $table->boolean('is_remote')
                ->default(false)
                ->comment('Remote work allowed');
            
            // Skills & Benefits
            $table->json('skills_required')
                ->nullable()
                ->comment('Required skills array');
            $table->json('skills_preferred')
                ->nullable()
                ->comment('Preferred/nice-to-have skills');
            $table->json('benefits')
                ->nullable()
                ->comment('Job benefits array');
            $table->json('languages_required')
                ->nullable()
                ->comment('Required language proficiencies');
            
            // Vacancies & Deadline
            $table->unsignedInteger('vacancies')
                ->default(1)
                ->comment('Number of open positions');
            $table->date('application_deadline')
                ->nullable()
                ->comment('Application deadline');
            
            // Status & Visibility
            $table->enum('status', ['draft', 'published', 'paused', 'closed', 'filled'])
                ->default('draft')
                ->comment('Job posting status');
            $table->timestamp('published_at')
                ->nullable()
                ->comment('When job was published');
            $table->timestamp('closed_at')
                ->nullable()
                ->comment('When job was closed');
            
            // Analytics
            $table->unsignedInteger('views_count')
                ->default(0)
                ->comment('Number of views');
            $table->unsignedInteger('applications_count')
                ->default(0)
                ->comment('Number of applications');
            
            // Additional Fields
            $table->json('extra')
                ->nullable()
                ->comment('Additional custom fields');
            
            // Timestamps
            $table->timestamps();
            $table->softDeletes();
            
            // Indexes
            $table->index('employer_id', 'idx_job_postings_employer');
            $table->index('status', 'idx_job_postings_status');
            $table->index('job_type', 'idx_job_postings_type');
            $table->index('location_country', 'idx_job_postings_country');
            $table->index('published_at', 'idx_job_postings_published');
            $table->index(['status', 'published_at'], 'idx_job_postings_status_published');
            $table->index(['employer_id', 'status'], 'idx_job_postings_employer_status');
            $table->fullText(['title', 'description'], 'idx_job_postings_fulltext');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('job_postings');
    }
};
