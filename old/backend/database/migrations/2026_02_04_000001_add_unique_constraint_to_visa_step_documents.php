<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * 
     * Adds unique constraint to prevent duplicate document uploads
     * for the same step/requirement combination per seeker.
     */
    public function up(): void
    {
        Schema::table('visa_step_documents', function (Blueprint $table) {
            // Remove duplicate documents before adding constraint
            DB::statement("
                DELETE t1 FROM visa_step_documents t1
                INNER JOIN visa_step_documents t2 
                WHERE t1.id > t2.id 
                AND t1.visa_step_id = t2.visa_step_id 
                AND t1.seeker_id = t2.seeker_id 
                AND COALESCE(t1.requirement_name, '') = COALESCE(t2.requirement_name, '')
            ");
            
            // Add unique constraint for visa_step_id + seeker_id + requirement_name
            $table->unique(
                ['visa_step_id', 'seeker_id', 'requirement_name'], 
                'unique_step_seeker_requirement'
            );
            
            // Add index for visa_process_step_id + seeker_id
            // Note: Cannot use UNIQUE with WHERE clause in MariaDB
            // Using regular index instead to improve query performance
            if (!Schema::hasIndex('visa_step_documents', 'idx_process_step_seeker')) {
                $table->index(['visa_process_step_id', 'seeker_id'], 'idx_process_step_seeker');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('visa_step_documents', function (Blueprint $table) {
            $table->dropUnique('unique_step_seeker_requirement');
            $table->dropIndex('idx_process_step_seeker');
        });
    }
};
