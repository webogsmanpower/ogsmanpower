<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Migration: Add salary columns to job_applications
 * 
 * Purpose: Capture current and expected salary during application
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('job_applications', function (Blueprint $table) {
            $table->string('current_salary')->nullable()->after('cover_letter')->comment('Current salary of applicant');
            $table->string('expected_salary')->nullable()->after('current_salary')->comment('Expected salary by applicant');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('job_applications', function (Blueprint $table) {
            $table->dropColumn(['current_salary', 'expected_salary']);
        });
    }
};
