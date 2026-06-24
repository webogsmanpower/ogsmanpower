<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

/**
 * Database Integrity Migration
 * 
 * This migration ensures:
 * 1. All critical fields have sensible defaults (no null math errors)
 * 2. Foreign keys are indexed for performance
 * 3. Existing null values are updated to defaults
 * 
 * Part of the Service-Repository Architecture implementation.
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // ============ SEEKERS TABLE ============
        Schema::table('seekers', function (Blueprint $table) {
            // Ensure defaults for numeric fields
            $table->integer('profile_completion')->default(0)->change();
            $table->integer('profile_views')->default(0)->change();
            $table->integer('experience_years')->default(0)->change();
            $table->boolean('is_profile_complete')->default(false)->change();
            
            // Ensure defaults for boolean fields
            $table->boolean('has_clean_driving_record')->default(false)->change();
            $table->boolean('skill_washing')->default(false)->change();
            $table->boolean('skill_cooking')->default(false)->change();
            $table->boolean('skill_babysitting')->default(false)->change();
            $table->boolean('skill_cleaning')->default(false)->change();
            
            // Add index on user_id if not exists (for eager loading)
            if (!$this->hasIndex('seekers', 'seekers_user_id_index')) {
                $table->index('user_id', 'seekers_user_id_index');
            }
        });

        // Update existing null values to defaults
        DB::table('seekers')->whereNull('profile_completion')->update(['profile_completion' => 0]);
        DB::table('seekers')->whereNull('profile_views')->update(['profile_views' => 0]);
        DB::table('seekers')->whereNull('experience_years')->update(['experience_years' => 0]);
        DB::table('seekers')->whereNull('is_profile_complete')->update(['is_profile_complete' => false]);
        DB::table('seekers')->whereNull('has_clean_driving_record')->update(['has_clean_driving_record' => false]);
        DB::table('seekers')->whereNull('skill_washing')->update(['skill_washing' => false]);
        DB::table('seekers')->whereNull('skill_cooking')->update(['skill_cooking' => false]);
        DB::table('seekers')->whereNull('skill_babysitting')->update(['skill_babysitting' => false]);
        DB::table('seekers')->whereNull('skill_cleaning')->update(['skill_cleaning' => false]);

        // ============ JOB_APPLICATIONS TABLE ============
        Schema::table('job_applications', function (Blueprint $table) {
            // Add indexes for common queries
            if (!$this->hasIndex('job_applications', 'job_applications_seeker_id_index')) {
                $table->index('seeker_id', 'job_applications_seeker_id_index');
            }
            if (!$this->hasIndex('job_applications', 'job_applications_job_posting_id_index')) {
                $table->index('job_posting_id', 'job_applications_job_posting_id_index');
            }
            if (!$this->hasIndex('job_applications', 'job_applications_employer_id_index')) {
                $table->index('employer_id', 'job_applications_employer_id_index');
            }
            if (!$this->hasIndex('job_applications', 'job_applications_status_index')) {
                $table->index('status', 'job_applications_status_index');
            }
            
            // Composite index for common filter queries
            if (!$this->hasIndex('job_applications', 'job_applications_seeker_status_index')) {
                $table->index(['seeker_id', 'status'], 'job_applications_seeker_status_index');
            }
            if (!$this->hasIndex('job_applications', 'job_applications_employer_status_index')) {
                $table->index(['employer_id', 'status'], 'job_applications_employer_status_index');
            }
        });

        // ============ JOB_POSTINGS TABLE ============
        Schema::table('job_postings', function (Blueprint $table) {
            // Add indexes for common queries
            if (!$this->hasIndex('job_postings', 'job_postings_employer_id_index')) {
                $table->index('employer_id', 'job_postings_employer_id_index');
            }
            if (!$this->hasIndex('job_postings', 'job_postings_status_index')) {
                $table->index('status', 'job_postings_status_index');
            }
        });

        // ============ SEEKER_RESUMES TABLE ============
        Schema::table('seeker_resumes', function (Blueprint $table) {
            // Ensure defaults
            $table->integer('profile_completion')->default(0)->change();
            
            // Add indexes
            if (!$this->hasIndex('seeker_resumes', 'seeker_resumes_user_id_index')) {
                $table->index('user_id', 'seeker_resumes_user_id_index');
            }
            if (!$this->hasIndex('seeker_resumes', 'seeker_resumes_seeker_id_index')) {
                $table->index('seeker_id', 'seeker_resumes_seeker_id_index');
            }
        });

        // Update existing null values
        DB::table('seeker_resumes')->whereNull('profile_completion')->update(['profile_completion' => 0]);

        // ============ USERS TABLE ============
        Schema::table('users', function (Blueprint $table) {
            // Ensure defaults
            $table->boolean('is_onboarding_completed')->default(false)->change();
            
            // Add index on role for filtering
            if (!$this->hasIndex('users', 'users_role_index')) {
                $table->index('role', 'users_role_index');
            }
        });

        // Update existing null values
        DB::table('users')->whereNull('is_onboarding_completed')->update(['is_onboarding_completed' => false]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Remove indexes (defaults can stay)
        Schema::table('seekers', function (Blueprint $table) {
            $table->dropIndexIfExists('seekers_user_id_index');
        });

        Schema::table('job_applications', function (Blueprint $table) {
            $table->dropIndexIfExists('job_applications_seeker_id_index');
            $table->dropIndexIfExists('job_applications_job_posting_id_index');
            $table->dropIndexIfExists('job_applications_employer_id_index');
            $table->dropIndexIfExists('job_applications_status_index');
            $table->dropIndexIfExists('job_applications_seeker_status_index');
            $table->dropIndexIfExists('job_applications_employer_status_index');
        });

        Schema::table('job_postings', function (Blueprint $table) {
            $table->dropIndexIfExists('job_postings_employer_id_index');
            $table->dropIndexIfExists('job_postings_status_index');
        });

        Schema::table('seeker_resumes', function (Blueprint $table) {
            $table->dropIndexIfExists('seeker_resumes_user_id_index');
            $table->dropIndexIfExists('seeker_resumes_seeker_id_index');
        });

        Schema::table('users', function (Blueprint $table) {
            $table->dropIndexIfExists('users_role_index');
        });
    }

    /**
     * Check if an index exists on a table.
     */
    private function hasIndex(string $table, string $indexName): bool
    {
        $indexes = DB::select("SHOW INDEX FROM {$table} WHERE Key_name = ?", [$indexName]);
        return count($indexes) > 0;
    }
};
