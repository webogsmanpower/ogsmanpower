<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Migration: Add role-specific fields to seeker_resumes table
 * 
 * Purpose: Add dedicated columns for CV role-specific data validation
 * These fields are required for Smart Data Validation system
 * 
 * Role-specific fields being added:
 * - Domestic Worker: full_body_photo, references
 * - Driver: license_number, license_expiry, vehicle_category
 * - Security Guard: height, weight, chest_measurement
 * - Beautician: portfolio_link, specialization
 * - Steel Fixer: safety_certifications
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('seeker_resumes', function (Blueprint $table) {
            // Only add columns if they don't already exist
            if (!Schema::hasColumn('seeker_resumes', 'full_body_photo')) {
                $table->string('full_body_photo')
                    ->nullable()
                    ->comment('Path to full body photo for domestic worker applications');
            }
                
            if (!Schema::hasColumn('seeker_resumes', 'references')) {
                $table->text('references')
                    ->nullable()
                    ->comment('References text for domestic worker applications');
            }

            if (!Schema::hasColumn('seeker_resumes', 'license_number')) {
                $table->string('license_number')
                    ->nullable()
                    ->comment('Driver license number');
            }
                
            if (!Schema::hasColumn('seeker_resumes', 'license_expiry')) {
                $table->date('license_expiry')
                    ->nullable()
                    ->comment('Driver license expiry date');
            }
                
            if (!Schema::hasColumn('seeker_resumes', 'vehicle_category')) {
                $table->enum('vehicle_category', ['LTV', 'HTV'])
                    ->nullable()
                    ->comment('Vehicle category: Light or Heavy Transport Vehicle');
            }

            if (!Schema::hasColumn('seeker_resumes', 'height')) {
                $table->decimal('height', 5, 2)
                    ->nullable()
                    ->comment('Height in centimeters');
            }
                
            if (!Schema::hasColumn('seeker_resumes', 'weight')) {
                $table->decimal('weight', 5, 2)
                    ->nullable()
                    ->comment('Weight in kilograms');
            }
                
            if (!Schema::hasColumn('seeker_resumes', 'chest_measurement')) {
                $table->decimal('chest_measurement', 5, 2)
                    ->nullable()
                    ->comment('Chest measurement in centimeters');
            }

            if (!Schema::hasColumn('seeker_resumes', 'portfolio_link')) {
                $table->string('portfolio_link')
                    ->nullable()
                    ->comment('Portfolio URL for beautician applications');
            }
                
            if (!Schema::hasColumn('seeker_resumes', 'specialization')) {
                $table->string('specialization')
                    ->nullable()
                    ->comment('Beautician specialization (e.g., hair, makeup, nails)');
            }

            if (!Schema::hasColumn('seeker_resumes', 'safety_certifications')) {
                $table->text('safety_certifications')
                    ->nullable()
                    ->comment('Safety certifications for steel fixer role');
            }
        });

        // Add indexes only if columns exist and indexes don't exist
        if (Schema::hasColumn('seeker_resumes', 'license_number') && !Schema::hasIndex('seeker_resumes', 'idx_seeker_resumes_license')) {
            Schema::table('seeker_resumes', function (Blueprint $table) {
                $table->index('license_number', 'idx_seeker_resumes_license');
            });
        }
        
        if (Schema::hasColumn('seeker_resumes', 'license_expiry') && !Schema::hasIndex('seeker_resumes', 'idx_seeker_resumes_license_expiry')) {
            Schema::table('seeker_resumes', function (Blueprint $table) {
                $table->index('license_expiry', 'idx_seeker_resumes_license_expiry');
            });
        }
        
        if (Schema::hasColumn('seeker_resumes', 'vehicle_category') && !Schema::hasIndex('seeker_resumes', 'idx_seeker_resumes_vehicle_category')) {
            Schema::table('seeker_resumes', function (Blueprint $table) {
                $table->index('vehicle_category', 'idx_seeker_resumes_vehicle_category');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('seeker_resumes', function (Blueprint $table) {
            $table->dropIndex('idx_seeker_resumes_license');
            $table->dropIndex('idx_seeker_resumes_license_expiry');
            $table->dropIndex('idx_seeker_resumes_vehicle_category');
            
            $table->dropColumn([
                'full_body_photo',
                'references',
                'license_number',
                'license_expiry',
                'vehicle_category',
                'height',
                'weight',
                'chest_measurement',
                'portfolio_link',
                'specialization',
                'safety_certifications'
            ]);
        });
    }
};
