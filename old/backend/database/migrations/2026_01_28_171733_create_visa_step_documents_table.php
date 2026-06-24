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
        Schema::create('visa_step_documents', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('visa_step_id');
            $table->unsignedBigInteger('seeker_id');
            $table->string('path');
            $table->string('filename');
            $table->enum('status', ['uploaded', 'verified', 'rejected'])->default('uploaded');
            $table->string('rejection_reason', 500)->nullable();
            $table->timestamps();

            $table->foreign('visa_step_id')->references('id')->on('visa_steps')->cascadeOnDelete();
            $table->foreign('seeker_id')->references('id')->on('seekers')->cascadeOnDelete();
            $table->index(['visa_step_id', 'seeker_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('visa_step_documents');
    }
};
