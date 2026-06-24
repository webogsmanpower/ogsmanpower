<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Migration: Create employers table
 * 
 * Purpose: Store company/employer profiles for the Employer Module.
 * Each employer is linked to a user (owner/admin) and can have multiple sub-users.
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('employers', function (Blueprint $table) {
            $table->id();
            
            // Owner/admin user relationship
            $table->foreignId('user_id')
                ->constrained('users')
                ->cascadeOnDelete()
                ->comment('Owner/admin user of this employer account');
            
            // Company Information
            $table->string('company_name')
                ->comment('Official company name');
            $table->string('company_name_ar')
                ->nullable()
                ->comment('Arabic company name for RTL support');
            $table->string('trade_license_number')
                ->nullable()
                ->comment('Business/trade license number');
            $table->enum('company_type', ['sole_proprietor', 'partnership', 'corporation', 'government', 'ngo', 'other'])
                ->default('corporation')
                ->comment('Type of business entity');
            $table->string('industry')
                ->nullable()
                ->comment('Industry sector (e.g., Construction, Healthcare)');
            $table->enum('company_size', ['1-10', '11-50', '51-200', '201-500', '500+'])
                ->nullable()
                ->comment('Number of employees');
            
            // Location
            $table->string('country', 3)
                ->comment('Country code (ISO 3166-1 alpha-3)');
            $table->string('city')
                ->nullable()
                ->comment('City name');
            $table->text('address')
                ->nullable()
                ->comment('Full street address');
            
            // Contact Information
            $table->string('phone', 20)
                ->nullable()
                ->comment('Primary contact phone');
            $table->string('email')
                ->nullable()
                ->comment('Primary contact email');
            $table->string('website')
                ->nullable()
                ->comment('Company website URL');
            
            // Branding
            $table->string('logo_path')
                ->nullable()
                ->comment('Path to company logo file');
            $table->text('description')
                ->nullable()
                ->comment('Company description/about');
            $table->text('description_ar')
                ->nullable()
                ->comment('Arabic company description');
            
            // Verification Status
            $table->boolean('is_verified')
                ->default(false)
                ->comment('Admin verification status');
            $table->timestamp('verified_at')
                ->nullable()
                ->comment('When the employer was verified');
            $table->foreignId('verified_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete()
                ->comment('Admin who verified');
            
            // Settings & Metadata
            $table->json('settings')
                ->nullable()
                ->comment('Company-specific settings (notifications, preferences)');
            $table->json('social_links')
                ->nullable()
                ->comment('Social media links (LinkedIn, Twitter, etc.)');
            
            // Timestamps
            $table->timestamps();
            $table->softDeletes();
            
            // Indexes
            $table->index('user_id', 'idx_employers_user_id');
            $table->index('company_name', 'idx_employers_company_name');
            $table->index('country', 'idx_employers_country');
            $table->index('is_verified', 'idx_employers_verified');
            $table->index(['country', 'industry'], 'idx_employers_country_industry');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('employers');
    }
};
