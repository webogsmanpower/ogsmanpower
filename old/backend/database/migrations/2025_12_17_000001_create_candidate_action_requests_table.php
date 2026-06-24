<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * 
     * This table stores action requests from employers to candidates.
     * Types: document_upload, answer_question, update_profile
     */
    public function up(): void
    {
        Schema::create('candidate_action_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employer_id')->constrained('employers')->onDelete('cascade');
            $table->foreignId('seeker_id')->constrained('seekers')->onDelete('cascade');
            $table->foreignId('job_application_id')->nullable()->constrained('job_applications')->onDelete('set null');
            $table->foreignId('created_by')->constrained('users')->onDelete('cascade');
            
            $table->enum('request_type', ['document_upload', 'answer_question', 'update_profile']);
            $table->string('title');
            $table->text('message');
            $table->boolean('is_required')->default(true);
            $table->date('due_date')->nullable();
            
            $table->enum('status', ['pending', 'completed', 'expired', 'cancelled'])->default('pending');
            
            // Response fields
            $table->text('response_text')->nullable();
            $table->string('response_file_path')->nullable();
            $table->timestamp('responded_at')->nullable();
            
            $table->timestamps();
            
            // Indexes for common queries
            $table->index(['seeker_id', 'status']);
            $table->index(['employer_id', 'status']);
            $table->index(['job_application_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('candidate_action_requests');
    }
};
