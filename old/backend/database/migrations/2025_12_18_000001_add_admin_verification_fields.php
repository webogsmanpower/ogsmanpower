<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Migration: Add Admin Verification Fields
 * 
 * Purpose: Add verification workflow fields to employers table
 * and super_admin flag to users table for Admin Module.
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Add super_admin flag to users table
        Schema::table('users', function (Blueprint $table) {
            $table->boolean('super_admin')
                ->default(false)
                ->after('role')
                ->comment('Super admin flag for elevated privileges');
        });

        // Add verification workflow fields to employers table
        Schema::table('employers', function (Blueprint $table) {
            $table->enum('verification_status', ['pending', 'verified', 'rejected'])
                ->default('pending')
                ->after('is_verified')
                ->comment('Verification workflow status');
            
            $table->text('rejection_reason')
                ->nullable()
                ->after('verification_status')
                ->comment('Reason for rejection if status is rejected');
            
            $table->timestamp('rejection_date')
                ->nullable()
                ->after('rejection_reason')
                ->comment('When the employer was rejected');
            
            $table->foreignId('rejected_by')
                ->nullable()
                ->after('rejection_date')
                ->constrained('users')
                ->nullOnDelete()
                ->comment('Admin who rejected');

            // Index for verification queue queries
            $table->index('verification_status', 'idx_employers_verification_status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('employers', function (Blueprint $table) {
            $table->dropIndex('idx_employers_verification_status');
            $table->dropForeign(['rejected_by']);
            $table->dropColumn([
                'verification_status',
                'rejection_reason',
                'rejection_date',
                'rejected_by',
            ]);
        });

        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('super_admin');
        });
    }
};
