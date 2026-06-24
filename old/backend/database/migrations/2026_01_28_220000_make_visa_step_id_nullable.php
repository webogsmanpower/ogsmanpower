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
            // Drop the foreign key constraint first
            $table->dropForeign(['visa_step_id']);
            
            // Make visa_step_id nullable
            $table->unsignedBigInteger('visa_step_id')->nullable()->change();
            
            // Re-add foreign key constraint (optional, as it can be null)
            $table->foreign('visa_step_id')
                ->references('id')
                ->on('visa_steps')
                ->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('visa_step_documents', function (Blueprint $table) {
            // Drop the foreign key
            $table->dropForeign(['visa_step_id']);
            
            // Make it NOT NULL again
            $table->unsignedBigInteger('visa_step_id')->nullable(false)->change();
            
            // Re-add foreign key
            $table->foreign('visa_step_id')
                ->references('id')
                ->on('visa_steps')
                ->onDelete('cascade');
        });
    }
};
