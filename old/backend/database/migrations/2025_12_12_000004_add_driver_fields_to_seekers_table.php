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
        Schema::table('seekers', function (Blueprint $table) {
            if (!Schema::hasColumn('seekers', 'license_number')) {
                $table->string('license_number', 50)->nullable()->after('resume_path');
            }

            if (!Schema::hasColumn('seekers', 'license_expiry_date')) {
                $table->date('license_expiry_date')->nullable()->after('license_number');
            }

            if (!Schema::hasColumn('seekers', 'license_issuing_country')) {
                $table->string('license_issuing_country', 100)->nullable()->after('license_expiry_date');
            }

            if (!Schema::hasColumn('seekers', 'license_issuing_authority')) {
                $table->string('license_issuing_authority', 150)->nullable()->after('license_issuing_country');
            }

            if (!Schema::hasColumn('seekers', 'license_type')) {
                $table->string('license_type', 50)->nullable()->after('license_issuing_authority');
            }

            if (!Schema::hasColumn('seekers', 'accident_free_years')) {
                $table->string('accident_free_years', 50)->nullable()->after('license_type');
            }

            if (!Schema::hasColumn('seekers', 'has_clean_driving_record')) {
                $table->boolean('has_clean_driving_record')->default(false)->after('accident_free_years');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('seekers', function (Blueprint $table) {
            if (Schema::hasColumn('seekers', 'has_clean_driving_record')) {
                $table->dropColumn('has_clean_driving_record');
            }

            if (Schema::hasColumn('seekers', 'accident_free_years')) {
                $table->dropColumn('accident_free_years');
            }

            if (Schema::hasColumn('seekers', 'license_type')) {
                $table->dropColumn('license_type');
            }

            if (Schema::hasColumn('seekers', 'license_issuing_authority')) {
                $table->dropColumn('license_issuing_authority');
            }

            if (Schema::hasColumn('seekers', 'license_issuing_country')) {
                $table->dropColumn('license_issuing_country');
            }

            if (Schema::hasColumn('seekers', 'license_expiry_date')) {
                $table->dropColumn('license_expiry_date');
            }

            if (Schema::hasColumn('seekers', 'license_number')) {
                $table->dropColumn('license_number');
            }
        });
    }
};
