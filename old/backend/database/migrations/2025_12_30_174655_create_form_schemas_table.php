<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * 
     * Creates the form_schemas table for dynamic form configuration.
     * Admins can define which fields appear in seeker/employer forms.
     */
    public function up(): void
    {
        Schema::create('form_schemas', function (Blueprint $table) {
            $table->id();
            $table->string('module'); // 'seeker_profile', 'job_post', 'employer_profile'
            $table->string('section'); // 'basic_info', 'experience', 'education', etc.
            $table->string('name'); // Human-readable name
            $table->text('description')->nullable();
            $table->json('fields'); // Array of field definitions
            $table->integer('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->boolean('is_required')->default(false); // Section is required for completion
            $table->timestamps();
            
            // Indexes
            $table->unique(['module', 'section']);
            $table->index(['module', 'is_active']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('form_schemas');
    }
};
