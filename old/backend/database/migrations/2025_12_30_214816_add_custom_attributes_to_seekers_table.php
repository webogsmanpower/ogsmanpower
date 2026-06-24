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
            $table->json('custom_attributes')->nullable()->after('profile_completion');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('seekers', function (Blueprint $table) {
            $table->dropColumn('custom_attributes');
        });
    }
};
