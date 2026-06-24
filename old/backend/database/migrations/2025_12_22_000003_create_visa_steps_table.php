<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Migration: Create visa_steps table
 * 
 * Purpose: Detailed step-by-step logging for visa processing workflow.
 * Provides audit trail separate from the JSON step_history in visa_statuses.
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('visa_steps', function (Blueprint $table) {
            $table->id();
            
            // Relationships
            $table->foreignId('visa_status_id')
                ->constrained('visa_statuses')
                ->cascadeOnDelete()
                ->comment('Parent visa status record');
            
            // Step Info
            $table->string('step_name')
                ->comment('Name of the step (matches visa_statuses.current_step enum)');
            $table->unsignedTinyInteger('step_order')
                ->comment('Order in the workflow (1-15)');
            
            // Status
            $table->enum('status', ['pending', 'in_progress', 'completed', 'skipped', 'blocked'])
                ->default('pending')
                ->comment('Step status');
            
            // Timing
            $table->timestamp('started_at')
                ->nullable()
                ->comment('When step was started');
            $table->timestamp('completed_at')
                ->nullable()
                ->comment('When step was completed');
            
            // Notes & Documents
            $table->text('notes')
                ->nullable()
                ->comment('Notes for this step');
            $table->json('documents')
                ->nullable()
                ->comment('Documents associated with this step');
            
            // Audit
            $table->foreignId('completed_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete()
                ->comment('User who marked step complete');
            
            // Timestamps
            $table->timestamps();
            
            // Indexes
            $table->index('visa_status_id', 'idx_visa_steps_status');
            $table->index(['visa_status_id', 'step_order'], 'idx_visa_steps_order');
            $table->index(['visa_status_id', 'status'], 'idx_visa_steps_status_filter');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('visa_steps');
    }
};
