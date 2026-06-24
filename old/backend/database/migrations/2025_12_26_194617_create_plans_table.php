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
        Schema::create('plans', function (Blueprint $table) {
            $table->id();
            $table->string('name'); // e.g., "Gold Employer", "Basic Seeker"
            $table->string('slug')->unique(); // URL-friendly identifier
            $table->enum('role_type', ['employer', 'seeker']);
            $table->decimal('price', 10, 2); // Price with 2 decimal places
            $table->enum('interval', ['monthly', 'yearly', 'one_time']);
            $table->json('features')->nullable(); // List of perks/features
            $table->json('limits')->nullable(); // System limits (job_posts, profile_unlocks, etc.)
            $table->boolean('is_active')->default(true);
            $table->text('description')->nullable();
            $table->integer('sort_order')->default(0);
            $table->timestamps();
            
            // Indexes for performance
            $table->index(['role_type', 'is_active']);
            $table->index('slug');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('plans');
    }
};
