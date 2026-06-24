<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Migration: Add template-related fields to contracts table
 * 
 * Purpose: Support template-based contract generation with attachment uploads
 * and enhanced signing metadata.
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('contracts', function (Blueprint $table) {
            // Template reference
            $table->foreignId('template_id')
                ->nullable()
                ->after('template_used')
                ->constrained('contract_templates')
                ->nullOnDelete()
                ->comment('Source template if created from template');
            
            // HTML content (for template-based contracts)
            $table->longText('html_content')
                ->nullable()
                ->after('terms')
                ->comment('Generated HTML content from template with filled placeholders');
            
            // Attachment (for uploaded PDF contracts)
            $table->string('attachment_path')
                ->nullable()
                ->after('document_path')
                ->comment('Path to uploaded PDF attachment (alternative to template)');
            
            // Signed document
            $table->string('signed_pdf_path')
                ->nullable()
                ->after('seeker_signature_path')
                ->comment('Path to signed document uploaded by seeker');
            
            // Enhanced signature metadata
            $table->json('signature_metadata')
                ->nullable()
                ->after('seeker_user_agent')
                ->comment('Audit trail: {ip, timestamp, initials, method}');
            
            // Seeker initials for digital acknowledgement
            $table->string('seeker_initials', 10)
                ->nullable()
                ->after('signature_metadata')
                ->comment('Seeker initials for digital signature');
            
            // Acknowledgement checkbox
            $table->boolean('terms_accepted')
                ->default(false)
                ->after('seeker_initials')
                ->comment('Whether seeker accepted terms checkbox');
            
            // Job ID (direct reference, not just through application)
            $table->foreignId('job_id')
                ->nullable()
                ->after('seeker_id')
                ->constrained('job_postings')
                ->nullOnDelete()
                ->comment('Direct job reference');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('contracts', function (Blueprint $table) {
            $table->dropForeign(['template_id']);
            $table->dropForeign(['job_id']);
            $table->dropColumn([
                'template_id',
                'html_content',
                'attachment_path',
                'signed_pdf_path',
                'signature_metadata',
                'seeker_initials',
                'terms_accepted',
                'job_id',
            ]);
        });
    }
};
