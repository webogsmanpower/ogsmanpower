<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Migration: Create document_verifications table
 * 
 * Purpose: Track employer verification of seeker documents.
 * Allows employers to verify passports, certificates, and other documents.
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('document_verifications', function (Blueprint $table) {
            $table->id();
            
            // Relationships
            $table->foreignId('employer_id')
                ->constrained('employers')
                ->cascadeOnDelete()
                ->comment('Employer performing verification');
            $table->foreignId('seeker_id')
                ->constrained('seekers')
                ->cascadeOnDelete()
                ->comment('Document owner');
            $table->foreignId('job_application_id')
                ->nullable()
                ->constrained('job_applications')
                ->nullOnDelete()
                ->comment('Related job application if applicable');
            
            // Document Information
            $table->string('document_type')
                ->comment('Type: passport, cnic, certificate, license, medical, police_clearance, etc.');
            $table->string('document_name')
                ->nullable()
                ->comment('Document name/title');
            $table->string('document_number')
                ->nullable()
                ->comment('Document number/ID');
            $table->string('document_path')
                ->comment('File path to the document');
            $table->date('document_issue_date')
                ->nullable()
                ->comment('Document issue date');
            $table->date('document_expiry_date')
                ->nullable()
                ->comment('Document expiry date');
            
            // Verification Status
            $table->enum('status', ['pending', 'in_review', 'verified', 'rejected', 'expired'])
                ->default('pending')
                ->comment('Verification status');
            $table->foreignId('verified_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete()
                ->comment('User who verified/rejected');
            $table->timestamp('verified_at')
                ->nullable()
                ->comment('Verification timestamp');
            
            // Rejection Details
            $table->string('rejection_reason')
                ->nullable()
                ->comment('Reason if rejected');
            $table->text('rejection_details')
                ->nullable()
                ->comment('Detailed rejection feedback');
            
            // Verification Notes
            $table->text('notes')
                ->nullable()
                ->comment('Internal verification notes');
            $table->json('verification_checklist')
                ->nullable()
                ->comment('Checklist items verified');
            
            // Flags
            $table->boolean('is_original_verified')
                ->default(false)
                ->comment('Original document verified in person');
            $table->boolean('requires_resubmission')
                ->default(false)
                ->comment('Document needs to be resubmitted');
            
            // Timestamps
            $table->timestamps();
            
            // Indexes
            $table->index('employer_id', 'idx_doc_verify_employer');
            $table->index('seeker_id', 'idx_doc_verify_seeker');
            $table->index('job_application_id', 'idx_doc_verify_application');
            $table->index('document_type', 'idx_doc_verify_type');
            $table->index('status', 'idx_doc_verify_status');
            $table->index(['employer_id', 'status'], 'idx_doc_verify_employer_status');
            $table->index(['seeker_id', 'document_type'], 'idx_doc_verify_seeker_type');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('document_verifications');
    }
};
