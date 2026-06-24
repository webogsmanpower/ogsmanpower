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
        Schema::table('visa_step_documents', function (Blueprint $table) {
            // Add requirement_name for strict document-requirement linking
            $table->string('requirement_name', 255)->nullable()->after('filename')
                ->comment('The specific requirement this document fulfills (e.g., passport, police_clearance)');
            
            // Add index for faster lookups by requirement
            $table->index(['visa_step_id', 'requirement_name'], 'idx_step_requirement');
            $table->index(['visa_process_step_id', 'requirement_name'], 'idx_process_step_requirement');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('visa_step_documents', function (Blueprint $table) {
            $table->dropIndex('idx_step_requirement');
            $table->dropIndex('idx_process_step_requirement');
            $table->dropColumn('requirement_name');
        });
    }
};
