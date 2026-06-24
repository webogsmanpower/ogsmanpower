<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * 
     * NOTE: The job_postings table already has:
     * - skills_required (must-have skills)
     * - skills_preferred (nice-to-have skills)
     * 
     * This migration is a no-op since the columns already exist.
     */
    public function up(): void
    {
        // Columns already exist: skills_required and skills_preferred
        // No changes needed
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // No changes to revert
    }
};
