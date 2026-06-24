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
        Schema::table('visa_process_steps', function (Blueprint $table) {
            $table->string('target_step')->nullable()->after('is_custom')->comment('The visa step this document request is associated with');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('visa_process_steps', function (Blueprint $table) {
            $table->dropColumn('target_step');
        });
    }
};
