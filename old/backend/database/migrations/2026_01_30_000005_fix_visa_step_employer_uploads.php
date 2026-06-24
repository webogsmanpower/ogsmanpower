<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Create visa_step_employer_uploads table for employer-uploaded documents
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Drop existing table if it exists with wrong structure
        Schema::dropIfExists('visa_step_employer_uploads');
        
        // Recreate with correct structure
        Schema::create('visa_step_employer_uploads', function (Blueprint $table) {
            $table->id();
            $table->foreignId('visa_step_id')->constrained()->onDelete('cascade');
            $table->string('title', 255);
            $table->text('description')->nullable();
            $table->string('file_path');
            $table->string('file_name');
            $table->string('file_type', 100);
            $table->unsignedBigInteger('file_size');
            $table->boolean('is_visible_to_seeker')->default(true);
            $table->foreignId('uploaded_by')->constrained('users')->onDelete('cascade');
            $table->timestamps();
            
            $table->index('visa_step_id');
            $table->index('uploaded_by');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('visa_step_employer_uploads');
    }
};
