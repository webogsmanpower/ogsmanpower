<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Create visa_step_questionnaires table for questionnaire management
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('visa_step_questionnaires', function (Blueprint $table) {
            $table->id();
            $table->foreignId('visa_step_id')->constrained()->onDelete('cascade');
            $table->string('title', 255);
            $table->text('description')->nullable();
            $table->enum('question_type', ['text', 'textarea', 'select', 'multiselect', 'checkbox', 'radio', 'date', 'number', 'file']);
            $table->text('options')->nullable(); // JSON for select/multiselect options
            $table->boolean('is_required')->default(true);
            $table->integer('sort_order')->default(0);
            $table->json('validation_rules')->nullable(); // e.g., {"min": 10, "max": 100, "pattern": ".*"}
            $table->timestamps();
            
            $table->index('visa_step_id');
        });

        // Create answers table
        Schema::create('visa_step_questionnaire_answers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('questionnaire_id')->constrained('visa_step_questionnaires')->onDelete('cascade');
            $table->foreignId('seeker_id')->constrained()->onDelete('cascade');
            $table->text('answer'); // Store answer as text/JSON
            $table->string('file_path')->nullable(); // For file-type questions
            $table->timestamp('answered_at')->nullable();
            $table->timestamps();
            
            $table->unique(['questionnaire_id', 'seeker_id'], 'visa_step_qa_unique');
            $table->index('seeker_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('visa_step_questionnaire_answers');
        Schema::dropIfExists('visa_step_questionnaires');
    }
};
