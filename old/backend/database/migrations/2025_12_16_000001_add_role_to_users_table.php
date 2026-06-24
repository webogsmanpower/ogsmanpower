<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Migration: Add role fields to users table
 * 
 * Purpose: Enable multi-role support for users (seeker, employer, agent, agency, admin).
 * Users can have a primary role and optionally switch between roles if they have multiple.
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // Primary role - defaults to 'seeker' for backward compatibility
            $table->enum('role', ['seeker', 'employer', 'agent', 'agency', 'admin'])
                ->default('seeker')
                ->after('email')
                ->comment('Primary user role');
            
            // Active role for users with multiple roles (e.g., someone who is both seeker and employer)
            $table->enum('active_role', ['seeker', 'employer', 'agent', 'agency', 'admin'])
                ->nullable()
                ->after('role')
                ->comment('Currently active role for multi-role users');
            
            // Index for role-based queries
            $table->index('role', 'idx_users_role');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropIndex('idx_users_role');
            $table->dropColumn(['role', 'active_role']);
        });
    }
};
