<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Migration: Create employer_users table (RBAC Sub-Users)
 * 
 * Purpose: Allow employers to invite team members with specific roles and permissions.
 * Supports role-based access control for HR managers, recruiters, interviewers, etc.
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('employer_users', function (Blueprint $table) {
            $table->id();
            
            // Relationships
            $table->foreignId('employer_id')
                ->constrained('employers')
                ->cascadeOnDelete()
                ->comment('Parent employer company');
            $table->foreignId('user_id')
                ->constrained('users')
                ->cascadeOnDelete()
                ->comment('Team member user account');
            
            // Role & Permissions
            $table->enum('role', ['admin', 'hr_manager', 'recruiter', 'interviewer', 'viewer'])
                ->default('viewer')
                ->comment('Team member role');
            $table->json('permissions')
                ->nullable()
                ->comment('Granular permissions override (JSON object)');
            
            // Invitation tracking
            $table->foreignId('invited_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete()
                ->comment('User who sent the invitation');
            $table->timestamp('invited_at')
                ->nullable()
                ->comment('When invitation was sent');
            $table->timestamp('accepted_at')
                ->nullable()
                ->comment('When invitation was accepted');
            
            // Status
            $table->boolean('is_active')
                ->default(true)
                ->comment('Whether this team member is active');
            
            // Timestamps
            $table->timestamps();
            
            // Indexes
            $table->unique(['employer_id', 'user_id'], 'idx_employer_users_unique');
            $table->index('employer_id', 'idx_employer_users_employer');
            $table->index('user_id', 'idx_employer_users_user');
            $table->index('role', 'idx_employer_users_role');
            $table->index('is_active', 'idx_employer_users_active');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('employer_users');
    }
};
