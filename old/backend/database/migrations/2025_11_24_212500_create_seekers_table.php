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
        Schema::create('seekers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')
                ->constrained()
                ->cascadeOnUpdate()
                ->cascadeOnDelete();
            $table->string('first_name');
            $table->string('last_name');
            $table->string('headline')->nullable();
            $table->date('date_of_birth')->nullable();
            $table->string('current_location')->nullable();
            $table->unsignedTinyInteger('experience_years')->default(0);
            $table->boolean('is_profile_complete')->default(false);
            $table->json('skills')->nullable();
            $table->text('bio')->nullable();
            $table->string('resume_path')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('seekers');
    }
};
