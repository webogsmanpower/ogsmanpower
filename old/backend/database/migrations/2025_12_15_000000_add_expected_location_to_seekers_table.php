<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Adds expected_location field to seekers table for Driver CV module.
     */
    public function up(): void
    {
        Schema::table('seekers', function (Blueprint $table) {
            if (!Schema::hasColumn('seekers', 'expected_location')) {
                $table->string('expected_location', 255)->nullable()->after('has_clean_driving_record');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('seekers', function (Blueprint $table) {
            if (Schema::hasColumn('seekers', 'expected_location')) {
                $table->dropColumn('expected_location');
            }
        });
    }
};
