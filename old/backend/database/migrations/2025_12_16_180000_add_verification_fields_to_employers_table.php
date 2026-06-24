<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Migration: Add verification/legal fields to employers table
 * 
 * Purpose: Store legal verification documents for employer registration.
 * Fields: registration_number, license_number, registration_document_path, company_email
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('employers', function (Blueprint $table) {
            // Legal/Verification Fields
            $table->string('registration_number')
                ->nullable()
                ->after('trade_license_number')
                ->comment('Company registration number');
            
            $table->string('license_number')
                ->nullable()
                ->after('registration_number')
                ->comment('Business license number');
            
            $table->string('registration_document_path')
                ->nullable()
                ->after('license_number')
                ->comment('Path to uploaded registration document (PDF/Image)');
            
            // Company contact email (separate from user login email)
            $table->string('company_email')
                ->nullable()
                ->after('email')
                ->comment('Public company contact email');
            
            // Company phone with country code support
            $table->string('company_phone', 30)
                ->nullable()
                ->after('company_email')
                ->comment('Company phone with country code');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('employers', function (Blueprint $table) {
            $table->dropColumn([
                'registration_number',
                'license_number',
                'registration_document_path',
                'company_email',
                'company_phone',
            ]);
        });
    }
};
