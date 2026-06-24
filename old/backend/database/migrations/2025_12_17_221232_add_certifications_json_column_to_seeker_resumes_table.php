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
            if (!Schema::hasColumn('seeker_resumes', 'certifications')) {
                $table->json('certifications')
                    ->nullable()
                    ->after('languages');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('seeker_resumes', function (Blueprint $table) {
            if (Schema::hasColumn('seeker_resumes', 'certifications')) {
                $table->dropColumn('certifications');
            }
        });
    }
};
