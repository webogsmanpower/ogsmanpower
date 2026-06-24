<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Migration: Add branding and approval workflow fields to contract_templates
 * 
 * Purpose: Enhanced contract branding with company details, signatory info,
 * and internal approval workflow support.
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('contract_templates', function (Blueprint $table) {
            // Enhanced Branding - Company Details
            $table->text('company_address')
                ->nullable()
                ->after('footer_text')
                ->comment('Custom company address block for header/footer');
            $table->string('company_phone', 50)
                ->nullable()
                ->after('company_address')
                ->comment('Company phone number');
            $table->string('company_email')
                ->nullable()
                ->after('company_phone')
                ->comment('Company email address');
            
            // Signatory Information
            $table->string('signatory_name')
                ->nullable()
                ->after('company_email')
                ->comment('Prepared By / Authorized Signatory name');
            $table->string('signatory_title')
                ->nullable()
                ->after('signatory_name')
                ->comment('Signatory job title (e.g., HR Director)');
            $table->string('signatory_signature_path')
                ->nullable()
                ->after('signatory_title')
                ->comment('Path to signatory signature image');
            
            // Internal Approval Workflow
            $table->foreignId('default_approver_id')
                ->nullable()
                ->after('signatory_signature_path')
                ->constrained('employer_users')
                ->nullOnDelete()
                ->comment('Default approver for contracts using this template');
            $table->boolean('requires_approval')
                ->default(false)
                ->after('default_approver_id')
                ->comment('Whether contracts from this template require internal approval');
        });

        // Add approval fields to contracts table
        Schema::table('contracts', function (Blueprint $table) {
            // Branding snapshot (copied from template at creation time)
            $table->string('header_logo_path')
                ->nullable()
                ->after('template_id')
                ->comment('Logo used in this contract');
            $table->text('company_address')
                ->nullable()
                ->after('header_logo_path')
                ->comment('Company address in this contract');
            $table->string('company_phone', 50)
                ->nullable()
                ->after('company_address')
                ->comment('Company phone in this contract');
            $table->string('company_email')
                ->nullable()
                ->after('company_phone')
                ->comment('Company email in this contract');
            $table->string('signatory_name')
                ->nullable()
                ->after('company_email')
                ->comment('Signatory name for this contract');
            $table->string('signatory_title')
                ->nullable()
                ->after('signatory_name')
                ->comment('Signatory title for this contract');
            $table->string('signatory_signature_path')
                ->nullable()
                ->after('signatory_title')
                ->comment('Signatory signature image path');
            
            // Internal Approval Workflow
            $table->foreignId('approver_id')
                ->nullable()
                ->after('signatory_signature_path')
                ->constrained('employer_users')
                ->nullOnDelete()
                ->comment('User who needs to approve this contract');
            $table->timestamp('approved_at')
                ->nullable()
                ->after('approver_id')
                ->comment('When the contract was internally approved');
            $table->foreignId('approved_by')
                ->nullable()
                ->after('approved_at')
                ->constrained('users')
                ->nullOnDelete()
                ->comment('User who approved the contract');
            $table->text('approval_notes')
                ->nullable()
                ->after('approved_by')
                ->comment('Notes from the approver');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('contracts', function (Blueprint $table) {
            $table->dropForeign(['approver_id']);
            $table->dropForeign(['approved_by']);
            $table->dropColumn([
                'header_logo_path',
                'company_address',
                'company_phone',
                'company_email',
                'signatory_name',
                'signatory_title',
                'signatory_signature_path',
                'approver_id',
                'approved_at',
                'approved_by',
                'approval_notes',
            ]);
        });

        Schema::table('contract_templates', function (Blueprint $table) {
            $table->dropForeign(['default_approver_id']);
            $table->dropColumn([
                'company_address',
                'company_phone',
                'company_email',
                'signatory_name',
                'signatory_title',
                'signatory_signature_path',
                'default_approver_id',
                'requires_approval',
            ]);
        });
    }
};
