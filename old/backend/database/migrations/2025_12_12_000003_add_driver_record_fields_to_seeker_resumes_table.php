<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('seeker_resumes', function (Blueprint $table) {
            if (!Schema::hasColumn('seeker_resumes', 'license_issuing_authority')) {
                $table->string('license_issuing_authority', 150)
                    ->nullable()
                    ->after('license_issuing_country');
            }

            if (!Schema::hasColumn('seeker_resumes', 'accident_free_years')) {
                $table->string('accident_free_years', 50)
                    ->nullable()
                    ->after('license_issuing_authority');
            }

            if (!Schema::hasColumn('seeker_resumes', 'has_clean_driving_record')) {
                $table->boolean('has_clean_driving_record')
                    ->default(false)
                    ->after('accident_free_years');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('seeker_resumes', function (Blueprint $table) {
            if (Schema::hasColumn('seeker_resumes', 'has_clean_driving_record')) {
                $table->dropColumn('has_clean_driving_record');
            }

            if (Schema::hasColumn('seeker_resumes', 'accident_free_years')) {
                $table->dropColumn('accident_free_years');
            }

            if (Schema::hasColumn('seeker_resumes', 'license_issuing_authority')) {
                $table->dropColumn('license_issuing_authority');
            }
        });
    }
};
