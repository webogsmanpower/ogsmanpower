<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Migration: Create visa_statuses table
 * 
 * Purpose: Track visa processing steps for hired candidates.
 * Mirrors the visa tracking logic used in the Seeker module.
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('visa_statuses', function (Blueprint $table) {
            $table->id();
            
            // Relationships
            $table->foreignId('contract_id')
                ->constrained('contracts')
                ->cascadeOnDelete()
                ->comment('Related employment contract');
            $table->foreignId('employer_id')
                ->constrained('employers')
                ->cascadeOnDelete()
                ->comment('Sponsoring employer');
            $table->foreignId('seeker_id')
                ->constrained('seekers')
                ->cascadeOnDelete()
                ->comment('Candidate applying for visa');
            
            // Visa Information
            $table->string('visa_type')
                ->comment('Type of visa (work, employment, etc.)');
            $table->string('destination_country', 3)
                ->comment('Target country code');
            $table->string('origin_country', 3)
                ->nullable()
                ->comment('Origin country code');
            
            // Current Status
            $table->enum('current_step', [
                'not_started',
                'documents_pending',
                'documents_submitted',
                'documents_verified',
                'medical_scheduled',
                'medical_completed',
                'medical_cleared',
                'visa_applied',
                'visa_processing',
                'visa_approved',
                'visa_rejected',
                'travel_scheduled',
                'departed',
                'arrived',
                'completed'
            ])->default('not_started')
                ->comment('Current visa processing step');
            
            // Step History
            $table->json('step_history')
                ->nullable()
                ->comment('Array of step changes with timestamps and notes');
            
            // Documents
            $table->json('documents_required')
                ->nullable()
                ->comment('List of required documents');
            $table->json('documents_submitted')
                ->nullable()
                ->comment('Submitted documents with paths and status');
            $table->json('documents_verified')
                ->nullable()
                ->comment('Verified documents with verification details');
            
            // Medical Examination
            $table->date('medical_date')
                ->nullable()
                ->comment('Scheduled medical exam date');
            $table->string('medical_center')
                ->nullable()
                ->comment('Medical examination center');
            $table->enum('medical_result', ['pending', 'pass', 'fail', 'conditional'])
                ->nullable()
                ->comment('Medical examination result');
            $table->text('medical_notes')
                ->nullable()
                ->comment('Medical examination notes');
            $table->string('medical_certificate_path')
                ->nullable()
                ->comment('Medical certificate file path');
            
            // Visa Application
            $table->date('visa_application_date')
                ->nullable()
                ->comment('Date visa was applied');
            $table->string('visa_application_number')
                ->nullable()
                ->comment('Visa application reference number');
            $table->string('visa_number')
                ->nullable()
                ->comment('Issued visa number');
            $table->date('visa_issue_date')
                ->nullable()
                ->comment('Visa issue date');
            $table->date('visa_expiry_date')
                ->nullable()
                ->comment('Visa expiry date');
            $table->string('visa_document_path')
                ->nullable()
                ->comment('Visa document file path');
            $table->text('visa_rejection_reason')
                ->nullable()
                ->comment('Reason if visa rejected');
            
            // Travel Information
            $table->date('travel_date')
                ->nullable()
                ->comment('Scheduled travel date');
            $table->string('flight_number')
                ->nullable()
                ->comment('Flight number');
            $table->string('departure_airport')
                ->nullable()
                ->comment('Departure airport code');
            $table->string('arrival_airport')
                ->nullable()
                ->comment('Arrival airport code');
            $table->dateTime('departure_time')
                ->nullable()
                ->comment('Departure date and time');
            $table->dateTime('arrival_time')
                ->nullable()
                ->comment('Expected arrival date and time');
            $table->date('actual_arrival_date')
                ->nullable()
                ->comment('Actual arrival date');
            
            // Accommodation (if employer provides)
            $table->string('accommodation_address')
                ->nullable()
                ->comment('Accommodation address');
            $table->string('accommodation_contact')
                ->nullable()
                ->comment('Accommodation contact person');
            
            // Notes & Updates
            $table->text('notes')
                ->nullable()
                ->comment('General notes');
            $table->foreignId('last_updated_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete()
                ->comment('User who last updated');
            
            // Timestamps
            $table->timestamps();
            
            // Indexes
            $table->index('contract_id', 'idx_visa_contract');
            $table->index('employer_id', 'idx_visa_employer');
            $table->index('seeker_id', 'idx_visa_seeker');
            $table->index('current_step', 'idx_visa_step');
            $table->index('destination_country', 'idx_visa_destination');
            $table->index(['employer_id', 'current_step'], 'idx_visa_employer_step');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('visa_statuses');
    }
};
