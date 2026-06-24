<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Migration: Add enhanced fields to job_postings table
 * 
 * Adds new columns for:
 * - Contract duration (1 Year, 2 Years, Permanent, etc.)
 * - Visa type (Employment Visa, Visit Visa, etc.)
 * - Gender preference
 * - Various allowances (housing, transportation, food, overtime)
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('job_postings', function (Blueprint $table) {
            // Contract & Visa Information
            $table->string('contract_duration', 50)
                ->nullable()
                ->after('job_type')
                ->comment('Contract duration: 1_year, 2_years, 3_years, permanent, project_based');
            
            $table->string('visa_type', 50)
                ->nullable()
                ->after('contract_duration')
                ->comment('Visa type: employment_visa, visit_visa, freelance_visa, investor_visa, family_visa, none');
            
            // Candidate Preferences
            $table->enum('gender_preference', ['male', 'female', 'any'])
                ->default('any')
                ->after('experience_level')
                ->comment('Preferred gender for the position');
            
            $table->unsignedTinyInteger('age_min')
                ->nullable()
                ->after('gender_preference')
                ->comment('Minimum age requirement');
            
            $table->unsignedTinyInteger('age_max')
                ->nullable()
                ->after('age_min')
                ->comment('Maximum age requirement');
            
            // Allowances (Boolean flags)
            $table->boolean('housing_allowance')
                ->default(false)
                ->after('benefits')
                ->comment('Housing/accommodation provided');
            
            $table->boolean('transportation_allowance')
                ->default(false)
                ->after('housing_allowance')
                ->comment('Transportation allowance provided');
            
            $table->boolean('food_allowance')
                ->default(false)
                ->after('transportation_allowance')
                ->comment('Food/meal allowance provided');
            
            $table->boolean('overtime_allowance')
                ->default(false)
                ->after('food_allowance')
                ->comment('Overtime pay available');
            
            $table->boolean('medical_insurance')
                ->default(false)
                ->after('overtime_allowance')
                ->comment('Medical insurance provided');
            
            $table->boolean('annual_ticket')
                ->default(false)
                ->after('medical_insurance')
                ->comment('Annual flight ticket to home country');
            
            // Working Hours
            $table->string('working_hours', 50)
                ->nullable()
                ->after('annual_ticket')
                ->comment('Working hours per day: 8_hours, 10_hours, 12_hours, flexible');
            
            $table->string('working_days', 50)
                ->nullable()
                ->after('working_hours')
                ->comment('Working days per week: 5_days, 6_days, flexible');
            
            // Index for filtering
            $table->index('contract_duration', 'idx_job_postings_contract');
            $table->index('visa_type', 'idx_job_postings_visa');
            $table->index('gender_preference', 'idx_job_postings_gender');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('job_postings', function (Blueprint $table) {
            // Drop indexes first
            $table->dropIndex('idx_job_postings_contract');
            $table->dropIndex('idx_job_postings_visa');
            $table->dropIndex('idx_job_postings_gender');
            
            // Drop columns
            $table->dropColumn([
                'contract_duration',
                'visa_type',
                'gender_preference',
                'age_min',
                'age_max',
                'housing_allowance',
                'transportation_allowance',
                'food_allowance',
                'overtime_allowance',
                'medical_insurance',
                'annual_ticket',
                'working_hours',
                'working_days',
            ]);
        });
    }
};
