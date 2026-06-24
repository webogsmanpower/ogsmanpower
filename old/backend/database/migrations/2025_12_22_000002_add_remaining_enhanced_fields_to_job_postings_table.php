<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Migration: Add remaining enhanced fields to job_postings table
 * 
 * Adds missing columns for:
 * - Nationality preference
 * - Functional area/job category
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('job_postings', function (Blueprint $table) {
            // Additional candidate preferences
            $table->string('nationality_preference')
                ->nullable()
                ->after('age_max')
                ->comment('Preferred nationality (country name or code)');
            
            // Job categorization
            $table->string('functional_area')
                ->nullable()
                ->after('nationality_preference')
                ->comment('Job functional area/category (e.g., IT, Healthcare, Engineering)');
            
            // Index for filtering
            $table->index('functional_area', 'idx_job_postings_functional_area');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('job_postings', function (Blueprint $table) {
            // Drop index first
            $table->dropIndex('idx_job_postings_functional_area');
            
            // Drop columns
            $table->dropColumn([
                'nationality_preference',
                'functional_area',
            ]);
        });
    }
};
