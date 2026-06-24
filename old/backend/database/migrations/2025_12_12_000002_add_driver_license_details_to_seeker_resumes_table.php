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
            if (!Schema::hasColumn('seeker_resumes', 'license_expiry_date')) {
                $table->date('license_expiry_date')
                    ->nullable()
                    ->after('license_expiry');
            }

            if (!Schema::hasColumn('seeker_resumes', 'license_issuing_country')) {
                $table->string('license_issuing_country', 100)
                    ->nullable()
                    ->after('license_expiry_date');
            }

            if (!Schema::hasColumn('seeker_resumes', 'license_type')) {
                $table->string('license_type', 50)
                    ->nullable()
                    ->after('license_issuing_country');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('seeker_resumes', function (Blueprint $table) {
            if (Schema::hasColumn('seeker_resumes', 'license_expiry_date')) {
                $table->dropColumn('license_expiry_date');
            }

            if (Schema::hasColumn('seeker_resumes', 'license_issuing_country')) {
                $table->dropColumn('license_issuing_country');
            }

            if (Schema::hasColumn('seeker_resumes', 'license_type')) {
                $table->dropColumn('license_type');
            }
        });
    }
};
