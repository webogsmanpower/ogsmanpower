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
            $table->json('security_guard_details')->nullable()->after('driver_license');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('seeker_resumes', function (Blueprint $table) {
            $table->dropColumn('security_guard_details');
        });
    }
};
