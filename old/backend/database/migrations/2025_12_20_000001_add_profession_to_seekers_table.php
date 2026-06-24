<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Migration: Add profession field to seekers table
 * 
 * Purpose: Allow seekers to specify their profession/job title
 * which will be displayed on their CV and profile.
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('seekers', function (Blueprint $table) {
            if (!Schema::hasColumn('seekers', 'profession')) {
                $table->string('profession', 100)
                    ->nullable()
                    ->after('last_name')
                    ->comment('User profession/job title for CV and profile display');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('seekers', function (Blueprint $table) {
            if (Schema::hasColumn('seekers', 'profession')) {
                $table->dropColumn('profession');
            }
        });
    }
};
