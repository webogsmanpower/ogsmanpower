<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Employer Settings / Subscription Limits Table
 * 
 * Stores usage limits and subscription settings for employers.
 * Admins can configure these limits globally or per-employer.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('employer_settings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employer_id')->unique()->constrained('employers')->onDelete('cascade');
            
            // Test/Assessment Limits
            $table->integer('custom_test_limit')->default(3); // Max custom tests employer can create
            $table->integer('test_taker_limit')->default(50); // Max candidates per test per month
            $table->integer('custom_tests_created')->default(0); // Current count
            $table->integer('test_takers_this_month')->default(0); // Current month usage
            $table->timestamp('test_takers_reset_at')->nullable(); // When to reset monthly counter
            
            // Credits for paid admin tests
            $table->decimal('assessment_credits', 10, 2)->default(0.00);
            
            // Job posting limits
            $table->integer('active_job_limit')->default(10);
            $table->integer('featured_job_limit')->default(2);
            
            // Screening question limits
            $table->integer('screening_questions_per_job')->default(10);
            
            // Subscription tier (for future expansion)
            $table->enum('subscription_tier', ['free', 'basic', 'professional', 'enterprise'])->default('free');
            $table->timestamp('subscription_expires_at')->nullable();
            
            $table->json('extra_settings')->nullable(); // Additional configurable settings
            $table->timestamps();
        });

        // Global admin settings for default limits
        Schema::create('admin_settings', function (Blueprint $table) {
            $table->id();
            $table->string('key')->unique();
            $table->text('value');
            $table->string('type')->default('string'); // string, integer, boolean, json
            $table->string('category')->default('general');
            $table->text('description')->nullable();
            $table->timestamps();

            $table->index('category');
        });

        // Insert default admin settings
        DB::table('admin_settings')->insert([
            ['key' => 'default_custom_test_limit', 'value' => '3', 'type' => 'integer', 'category' => 'assessments', 'description' => 'Default max custom tests per employer', 'created_at' => now(), 'updated_at' => now()],
            ['key' => 'default_test_taker_limit', 'value' => '50', 'type' => 'integer', 'category' => 'assessments', 'description' => 'Default max test takers per month', 'created_at' => now(), 'updated_at' => now()],
            ['key' => 'default_screening_questions_limit', 'value' => '10', 'type' => 'integer', 'category' => 'screening', 'description' => 'Default max screening questions per job', 'created_at' => now(), 'updated_at' => now()],
            ['key' => 'default_active_job_limit', 'value' => '10', 'type' => 'integer', 'category' => 'jobs', 'description' => 'Default max active jobs per employer', 'created_at' => now(), 'updated_at' => now()],
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('admin_settings');
        Schema::dropIfExists('employer_settings');
    }
};
