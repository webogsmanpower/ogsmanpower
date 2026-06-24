<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Migration: Create contract_templates table
 * 
 * Purpose: Reusable contract templates for employers with WYSIWYG content
 * and dynamic placeholders for auto-filling candidate data.
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('contract_templates', function (Blueprint $table) {
            $table->id();
            
            // Relationships
            $table->foreignId('employer_id')
                ->constrained('employers')
                ->cascadeOnDelete()
                ->comment('Owning employer');
            $table->foreignId('created_by')
                ->constrained('users')
                ->cascadeOnDelete()
                ->comment('User who created the template');
            
            // Template Info
            $table->string('name')
                ->comment('Template name for identification');
            $table->text('description')
                ->nullable()
                ->comment('Template description');
            
            // Content
            $table->longText('content')
                ->comment('WYSIWYG HTML content with placeholders');
            $table->json('placeholders')
                ->nullable()
                ->comment('Available placeholders: [{key: "candidate_name", label: "Candidate Name", default: ""}]');
            
            // Branding
            $table->string('header_image_path')
                ->nullable()
                ->comment('Custom header image/logo for this template');
            $table->string('footer_text')
                ->nullable()
                ->comment('Footer text for the template');
            
            // Settings
            $table->boolean('is_default')
                ->default(false)
                ->comment('Default template for this employer');
            $table->boolean('is_active')
                ->default(true)
                ->comment('Whether template is available for use');
            
            // Metadata
            $table->unsignedInteger('usage_count')
                ->default(0)
                ->comment('Number of contracts created from this template');
            
            // Timestamps
            $table->timestamps();
            $table->softDeletes();
            
            // Indexes
            $table->index('employer_id', 'idx_contract_templates_employer');
            $table->index(['employer_id', 'is_active'], 'idx_contract_templates_employer_active');
            $table->index(['employer_id', 'is_default'], 'idx_contract_templates_employer_default');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('contract_templates');
    }
};
