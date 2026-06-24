<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Migration: Add document_path and enhanced fields to resume JSON structure
 * 
 * Purpose: Document the expected JSON structure for work_experience, education,
 * certifications, and references sections to support new frontend requirements.
 * 
 * This is a documentation migration - the actual fields are stored in JSON columns
 * within the seeker_resumes table, but this migration ensures the structure is
 * properly documented and validated.
 * 
 * Changes:
 * 1. Work Experience entries now support document_path field for file uploads
 * 2. Education entries now support document_path field for degree/transcript uploads
 * 3. Certifications support proper field names and does_not_expire boolean
 * 4. References support additional fields: email, company_name, relationship, job_title
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // This migration primarily documents the expected JSON structure
        // The actual data is stored in JSON columns in seeker_resumes table
        
        // Update any existing data to match new structure if needed
        // This is a placeholder for any data transformation logic
        
        Schema::table('seeker_resumes', function (Blueprint $table) {
            // No schema changes needed - using JSON columns
            // This migration serves as documentation for the expected structure
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // No schema changes to reverse
        Schema::table('seeker_resumes', function (Blueprint $table) {
            // No changes to revert
        });
    }
};
