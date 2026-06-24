<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Migration to add domestic worker specific fields to seekers table.
 * These fields support the Domestic Worker CV template with Just-in-Time data collection.
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('seekers', function (Blueprint $table) {
            // Number of children (for domestic worker applications)
            $table->unsignedInteger('number_of_children')->nullable()->after('has_clean_driving_record');
            
            // Domestic worker skill checkboxes
            $table->boolean('skill_washing')->default(false)->after('number_of_children');
            $table->boolean('skill_cooking')->default(false)->after('skill_washing');
            $table->boolean('skill_babysitting')->default(false)->after('skill_cooking');
            $table->boolean('skill_cleaning')->default(false)->after('skill_babysitting');
            
            // Full body image path for domestic worker CV
            $table->string('full_body_image_path')->nullable()->after('skill_cleaning');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('seekers', function (Blueprint $table) {
            $table->dropColumn([
                'number_of_children',
                'skill_washing',
                'skill_cooking',
                'skill_babysitting',
                'skill_cleaning',
                'full_body_image_path',
            ]);
        });
    }
};
