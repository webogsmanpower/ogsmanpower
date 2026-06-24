<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Migration: Add Ban Fields to Users Table
 * 
 * Purpose: Add fields for admin to ban/unban users (seekers).
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->timestamp('banned_at')
                ->nullable()
                ->after('super_admin')
                ->comment('When the user was banned');
            
            $table->string('ban_reason', 500)
                ->nullable()
                ->after('banned_at')
                ->comment('Reason for the ban');
            
            $table->foreignId('banned_by')
                ->nullable()
                ->after('ban_reason')
                ->constrained('users')
                ->nullOnDelete()
                ->comment('Admin who banned the user');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['banned_by']);
            $table->dropColumn([
                'banned_at',
                'ban_reason',
                'banned_by',
            ]);
        });
    }
};
