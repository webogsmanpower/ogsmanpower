<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Migration: Add is_custom field to skills table
 * 
 * This field distinguishes between:
 * - Seeded skills (is_custom = false) - Should NOT be returned by API
 * - User-created custom skills (is_custom = true) - Returned by API for hybrid autocomplete
 * 
 * Frontend has STATIC_SKILLS constant with 500+ skills for zero-latency lookups.
 * Backend should ONLY return custom user-created skills to avoid infinite loops.
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('skills', function (Blueprint $table) {
            $table->boolean('is_custom')->default(false)->after('is_active')->index();
            $table->foreignId('created_by')->nullable()->after('is_custom')->constrained('users')->nullOnDelete();
        });

        // Mark all existing skills as seeded (not custom)
        DB::table('skills')->update(['is_custom' => false]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('skills', function (Blueprint $table) {
            $table->dropColumn(['is_custom', 'created_by']);
        });
    }
};
