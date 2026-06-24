<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('assessment_attempts', function (Blueprint $table) {
            $table->boolean('retry_allowed')->default(false)->after('status');
            $table->string('retry_granted_by')->nullable()->after('retry_allowed'); // 'employer' or 'admin'
            $table->unsignedBigInteger('retry_granted_by_id')->nullable()->after('retry_granted_by');
            $table->timestamp('retry_granted_at')->nullable()->after('retry_granted_by_id');
            $table->text('retry_reason')->nullable()->after('retry_granted_at');
        });

        // Add rejection_reason to job_applications if not exists
        if (!Schema::hasColumn('job_applications', 'assessment_failed')) {
            Schema::table('job_applications', function (Blueprint $table) {
                $table->boolean('assessment_failed')->default(false)->after('status');
                $table->text('assessment_rejection_reason')->nullable()->after('assessment_failed');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('assessment_attempts', function (Blueprint $table) {
            $table->dropColumn(['retry_allowed', 'retry_granted_by', 'retry_granted_by_id', 'retry_granted_at', 'retry_reason']);
        });

        if (Schema::hasColumn('job_applications', 'assessment_failed')) {
            Schema::table('job_applications', function (Blueprint $table) {
                $table->dropColumn(['assessment_failed', 'assessment_rejection_reason']);
            });
        }
    }
};
