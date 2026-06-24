<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Performance Indexes Migration
 * 
 * Adds indexes to frequently queried columns to improve API response times.
 * Part of the Architecture Audit optimization effort.
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Users table indexes
        Schema::table('users', function (Blueprint $table) {
            // Email is frequently used for login lookups
            if (!$this->hasIndex('users', 'users_email_index')) {
                $table->index('email', 'users_email_index');
            }
            
            // Mobile for phone-based lookups
            if (!$this->hasIndex('users', 'users_mobile_index')) {
                $table->index('mobile', 'users_mobile_index');
            }
        });

        // Seekers table indexes
        Schema::table('seekers', function (Blueprint $table) {
            // User ID for relationship lookups
            if (!$this->hasIndex('seekers', 'seekers_user_id_index')) {
                $table->index('user_id', 'seekers_user_id_index');
            }
            
            // License number for driver searches
            if (!$this->hasIndex('seekers', 'seekers_license_number_index')) {
                $table->index('license_number', 'seekers_license_number_index');
            }
            
            // Profile completion status for filtering
            if (!$this->hasIndex('seekers', 'seekers_is_profile_complete_index')) {
                $table->index('is_profile_complete', 'seekers_is_profile_complete_index');
            }
        });

        // Seeker resumes table indexes
        Schema::table('seeker_resumes', function (Blueprint $table) {
            // User ID for relationship lookups
            if (!$this->hasIndex('seeker_resumes', 'seeker_resumes_user_id_index')) {
                $table->index('user_id', 'seeker_resumes_user_id_index');
            }
            
            // Seeker ID for relationship lookups
            if (!$this->hasIndex('seeker_resumes', 'seeker_resumes_seeker_id_index')) {
                $table->index('seeker_id', 'seeker_resumes_seeker_id_index');
            }
        });

        // Login activities table indexes (for session management)
        if (Schema::hasTable('login_activities')) {
            Schema::table('login_activities', function (Blueprint $table) {
                if (!$this->hasIndex('login_activities', 'login_activities_user_id_index')) {
                    $table->index('user_id', 'login_activities_user_id_index');
                }
                
                if (!$this->hasIndex('login_activities', 'login_activities_token_id_index')) {
                    $table->index('token_id', 'login_activities_token_id_index');
                }
            });
        }

        // Personal access tokens table indexes (for session lookups)
        if (Schema::hasTable('personal_access_tokens')) {
            Schema::table('personal_access_tokens', function (Blueprint $table) {
                // Composite index for user token lookups
                if (!$this->hasIndex('personal_access_tokens', 'pat_tokenable_last_used_index')) {
                    $table->index(['tokenable_id', 'tokenable_type', 'last_used_at'], 'pat_tokenable_last_used_index');
                }
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropIndex('users_email_index');
            $table->dropIndex('users_mobile_index');
        });

        Schema::table('seekers', function (Blueprint $table) {
            $table->dropIndex('seekers_user_id_index');
            $table->dropIndex('seekers_license_number_index');
            $table->dropIndex('seekers_is_profile_complete_index');
        });

        Schema::table('seeker_resumes', function (Blueprint $table) {
            $table->dropIndex('seeker_resumes_user_id_index');
            $table->dropIndex('seeker_resumes_seeker_id_index');
        });

        if (Schema::hasTable('login_activities')) {
            Schema::table('login_activities', function (Blueprint $table) {
                $table->dropIndex('login_activities_user_id_index');
                $table->dropIndex('login_activities_token_id_index');
            });
        }

        if (Schema::hasTable('personal_access_tokens')) {
            Schema::table('personal_access_tokens', function (Blueprint $table) {
                $table->dropIndex('pat_tokenable_last_used_index');
            });
        }
    }

    /**
     * Check if an index exists on a table.
     */
    private function hasIndex(string $table, string $indexName): bool
    {
        $indexes = Schema::getIndexes($table);
        
        foreach ($indexes as $index) {
            if ($index['name'] === $indexName) {
                return true;
            }
        }
        
        return false;
    }
};
