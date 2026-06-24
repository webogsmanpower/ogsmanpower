<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Migration: Add translations JSON column to seeker-related tables
 * 
 * Purpose: Store cached translations from Google Cloud Translation API
 * to avoid repeated API calls for the same content.
 * 
 * JSON Structure:
 * {
 *     "ar": {
 *         "bio": "...",
 *         "job_title": "...",
 *         "translated_at": "2023-10-27 10:00:00"
 *     },
 *     "fr": {
 *         "bio": "...",
 *         "job_title": "..."
 *     }
 * }
 * 
 * Invalidation: When model is updated, translations are cleared via Observer
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Add translations column to seekers table
        Schema::table('seekers', function (Blueprint $table) {
            $table->json('translations')->nullable()->after('full_body_image_path');
        });

        // Add translations column to seeker_resumes table
        Schema::table('seeker_resumes', function (Blueprint $table) {
            $table->json('translations')->nullable()->after('extra');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('seekers', function (Blueprint $table) {
            $table->dropColumn('translations');
        });

        Schema::table('seeker_resumes', function (Blueprint $table) {
            $table->dropColumn('translations');
        });
    }
};
