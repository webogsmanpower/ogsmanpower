<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Migration: Add profession and additional CV-specific fields to seeker_resumes table
 * 
 * Purpose: Complete the CV generation system with all required fields identified
 * during the Phase 1 audit. These fields ensure dynamic, profession-specific CVs
 * with no hardcoded dummy data.
 * 
 * Fields being added:
 * - profession: User's profession/job title (mandatory for all CV types)
 * - license_type: Driver license type (LTV, HTV, Motorcycle, etc.)
 * - driving_experience_years: Years of driving experience
 * - driving_history: JSON array of driving history records
 * - personal_qualities: JSON array for domestic worker qualities
 * - physical_capabilities: JSON array for steel fixer capabilities
 * - construction_projects: JSON array for steel fixer project experience
 * - availability_notes: Additional availability information
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('seeker_resumes', function (Blueprint $table) {
            // Profession field - mandatory for all CV types
            if (!Schema::hasColumn('seeker_resumes', 'profession')) {
                $table->string('profession', 100)
                    ->nullable()
                    ->after('resume_format')
                    ->comment('User profession/job title for CV header');
            }

            // Driver-specific fields
            if (!Schema::hasColumn('seeker_resumes', 'license_type')) {
                $table->string('license_type', 50)
                    ->nullable()
                    ->after('vehicle_category')
                    ->comment('License type: LTV, HTV, Motorcycle, Commercial, etc.');
            }

            if (!Schema::hasColumn('seeker_resumes', 'driving_experience_years')) {
                $table->unsignedTinyInteger('driving_experience_years')
                    ->nullable()
                    ->after('license_type')
                    ->comment('Years of driving experience');
            }

            if (!Schema::hasColumn('seeker_resumes', 'driving_history')) {
                $table->json('driving_history')
                    ->nullable()
                    ->after('driving_experience_years')
                    ->comment('JSON array: driving records, accidents, violations');
            }

            // Domestic Worker-specific fields
            if (!Schema::hasColumn('seeker_resumes', 'personal_qualities')) {
                $table->json('personal_qualities')
                    ->nullable()
                    ->after('full_body_photo')
                    ->comment('JSON array of personal qualities: honest, punctual, etc.');
            }

            // Steel Fixer-specific fields
            if (!Schema::hasColumn('seeker_resumes', 'physical_capabilities')) {
                $table->json('physical_capabilities')
                    ->nullable()
                    ->after('safety_certifications')
                    ->comment('JSON array: heavy lifting, height work, etc.');
            }

            if (!Schema::hasColumn('seeker_resumes', 'construction_projects')) {
                $table->json('construction_projects')
                    ->nullable()
                    ->after('physical_capabilities')
                    ->comment('JSON array of construction project experiences');
            }

            // General availability notes
            if (!Schema::hasColumn('seeker_resumes', 'availability_notes')) {
                $table->text('availability_notes')
                    ->nullable()
                    ->after('availability')
                    ->comment('Additional availability information text');
            }

            // Social links for CV display
            if (!Schema::hasColumn('seeker_resumes', 'linkedin_url')) {
                $table->string('linkedin_url', 255)
                    ->nullable()
                    ->after('social_profiles')
                    ->comment('LinkedIn profile URL for CV display');
            }

            if (!Schema::hasColumn('seeker_resumes', 'website_url')) {
                $table->string('website_url', 255)
                    ->nullable()
                    ->after('linkedin_url')
                    ->comment('Personal website/portfolio URL');
            }
        });

        // Add index for profession for faster filtering
        if (Schema::hasColumn('seeker_resumes', 'profession')) {
            Schema::table('seeker_resumes', function (Blueprint $table) {
                $table->index('profession', 'idx_seeker_resumes_profession');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('seeker_resumes', function (Blueprint $table) {
            // Drop index first
            $table->dropIndex('idx_seeker_resumes_profession');

            // Drop columns
            $columns = [
                'profession',
                'license_type',
                'driving_experience_years',
                'driving_history',
                'personal_qualities',
                'physical_capabilities',
                'construction_projects',
                'availability_notes',
                'linkedin_url',
                'website_url',
            ];

            foreach ($columns as $column) {
                if (Schema::hasColumn('seeker_resumes', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
