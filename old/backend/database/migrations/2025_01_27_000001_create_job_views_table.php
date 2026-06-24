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
        Schema::create('job_views', function (Blueprint $table) {
            $table->id();
            $table->foreignId('job_posting_id')->constrained('job_postings')->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('ip_address', 45)->comment('IPv4 or IPv6 address');
            $table->string('user_agent', 500)->nullable();
            $table->string('country_code', 2)->nullable()->comment('ISO 3166-1 alpha-2 country code');
            $table->string('city', 100)->nullable();
            $table->timestamp('viewed_at')->useCurrent();
            $table->timestamps();
            
            // Indexes for performance
            $table->index(['job_posting_id', 'viewed_at'], 'job_views_job_date_index');
            $table->index(['job_posting_id', 'user_id'], 'job_views_job_user_index');
            $table->index(['job_posting_id', 'ip_address'], 'job_views_job_ip_index');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('job_views');
    }
};
