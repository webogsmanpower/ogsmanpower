<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Create visa_workflow_steps table for dynamic step management
 * 
 * This table allows employers to create custom steps for each visa status,
 * replacing the hardcoded STEPS constant.
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('visa_workflow_steps', function (Blueprint $table) {
            $table->id();
            $table->foreignId('visa_status_id')->constrained()->onDelete('cascade');
            $table->string('name', 100); // e.g., 'documents_pending', 'medical_scheduled'
            $table->string('label', 255); // e.g., 'Documents Pending', 'Medical Scheduled'
            $table->text('description')->nullable();
            $table->integer('sort_order')->default(0);
            $table->enum('status', ['pending', 'in_progress', 'completed', 'skipped', 'blocked'])->default('pending');
            $table->boolean('is_mandatory')->default(true);
            $table->boolean('requires_documents')->default(false);
            $table->boolean('requires_questionnaires')->default(false);
            $table->boolean('requires_employer_uploads')->default(false);
            $table->json('required_documents')->nullable(); // Array of document types required
            $table->json('questionnaire_config')->nullable(); // Questionnaire configuration
            $table->json('employer_uploads')->nullable(); // Documents uploaded by employer
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->foreignId('completed_by')->nullable()->constrained('users')->onDelete('set null');
            $table->text('public_notes')->nullable(); // Visible to seeker
            $table->text('internal_notes')->nullable(); // Only employer/internal team
            $table->timestamps();
            
            // Indexes for performance
            $table->index(['visa_status_id', 'sort_order']);
            $table->index('status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('visa_workflow_steps');
    }
};
