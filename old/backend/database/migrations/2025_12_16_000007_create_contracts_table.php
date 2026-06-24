<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Migration: Create contracts table
 * 
 * Purpose: Employment contracts sent to candidates after successful interviews.
 * Tracks the full contract lifecycle: draft -> sent -> viewed -> signed/rejected
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('contracts', function (Blueprint $table) {
            $table->id();
            
            // Relationships
            $table->foreignId('job_application_id')
                ->constrained('job_applications')
                ->cascadeOnDelete()
                ->comment('Related job application');
            $table->foreignId('employer_id')
                ->constrained('employers')
                ->cascadeOnDelete()
                ->comment('Issuing employer');
            $table->foreignId('seeker_id')
                ->constrained('seekers')
                ->cascadeOnDelete()
                ->comment('Receiving candidate');
            $table->foreignId('created_by')
                ->constrained('users')
                ->cascadeOnDelete()
                ->comment('User who created the contract');
            
            // Contract Identification
            $table->string('contract_number')
                ->unique()
                ->comment('Unique contract reference number');
            $table->string('title')
                ->comment('Contract title');
            
            // Position Details
            $table->string('job_title')
                ->comment('Position being offered');
            $table->string('department')
                ->nullable()
                ->comment('Department/team');
            $table->string('reporting_to')
                ->nullable()
                ->comment('Reporting manager/supervisor');
            $table->string('work_location')
                ->nullable()
                ->comment('Work location');
            
            // Compensation
            $table->decimal('salary', 12, 2)
                ->comment('Offered salary');
            $table->string('salary_currency', 3)
                ->default('USD')
                ->comment('Salary currency');
            $table->enum('salary_period', ['hourly', 'daily', 'weekly', 'monthly', 'yearly'])
                ->default('monthly')
                ->comment('Salary payment period');
            $table->json('allowances')
                ->nullable()
                ->comment('Additional allowances (housing, transport, etc.)');
            $table->json('benefits')
                ->nullable()
                ->comment('Benefits included');
            
            // Contract Duration
            $table->date('start_date')
                ->comment('Employment start date');
            $table->date('end_date')
                ->nullable()
                ->comment('Contract end date (if fixed-term)');
            $table->enum('contract_type', ['permanent', 'fixed_term', 'probation', 'temporary'])
                ->default('permanent')
                ->comment('Type of employment contract');
            $table->unsignedTinyInteger('probation_months')
                ->nullable()
                ->comment('Probation period in months');
            $table->unsignedTinyInteger('notice_period_days')
                ->nullable()
                ->comment('Notice period in days');
            
            // Working Hours
            $table->string('working_hours')
                ->nullable()
                ->comment('Working hours description');
            $table->unsignedTinyInteger('working_days_per_week')
                ->nullable()
                ->comment('Number of working days per week');
            
            // Terms & Conditions
            $table->text('terms')
                ->nullable()
                ->comment('Terms and conditions text');
            $table->text('special_conditions')
                ->nullable()
                ->comment('Special conditions/clauses');
            $table->json('clauses')
                ->nullable()
                ->comment('Structured contract clauses');
            
            // Document
            $table->string('document_path')
                ->nullable()
                ->comment('Generated PDF file path');
            $table->string('template_used')
                ->nullable()
                ->comment('Contract template identifier');
            
            // Status & Workflow
            $table->enum('status', ['draft', 'pending_approval', 'sent', 'viewed', 'signed', 'rejected', 'expired', 'cancelled'])
                ->default('draft')
                ->comment('Contract status');
            $table->timestamp('sent_at')
                ->nullable()
                ->comment('When sent to candidate');
            $table->timestamp('viewed_at')
                ->nullable()
                ->comment('When candidate first viewed');
            $table->timestamp('signed_at')
                ->nullable()
                ->comment('When candidate signed');
            $table->timestamp('expires_at')
                ->nullable()
                ->comment('Offer expiration date');
            
            // Signatures
            $table->string('seeker_signature_path')
                ->nullable()
                ->comment('Candidate signature file');
            $table->string('employer_signature_path')
                ->nullable()
                ->comment('Employer signature file');
            $table->string('seeker_ip_address')
                ->nullable()
                ->comment('IP address when signed');
            $table->text('seeker_user_agent')
                ->nullable()
                ->comment('Browser info when signed');
            
            // Rejection
            $table->text('rejection_reason')
                ->nullable()
                ->comment('Reason if rejected by candidate');
            $table->text('negotiation_notes')
                ->nullable()
                ->comment('Notes from negotiation');
            
            // Versioning
            $table->unsignedTinyInteger('version')
                ->default(1)
                ->comment('Contract version number');
            $table->unsignedBigInteger('parent_contract_id')
                ->nullable()
                ->comment('Previous version if revised');
            
            // Timestamps
            $table->timestamps();
            $table->softDeletes();
            
            // Indexes
            $table->index('job_application_id', 'idx_contracts_application');
            $table->index('employer_id', 'idx_contracts_employer');
            $table->index('seeker_id', 'idx_contracts_seeker');
            $table->index('status', 'idx_contracts_status');
            $table->index('contract_number', 'idx_contracts_number');
            $table->index(['employer_id', 'status'], 'idx_contracts_employer_status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('contracts');
    }
};
