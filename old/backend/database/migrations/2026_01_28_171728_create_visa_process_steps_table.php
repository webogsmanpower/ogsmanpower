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
        Schema::create('visa_process_steps', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('visa_status_id');
            $table->string('name');
            $table->string('label');
            $table->enum('status', ['pending', 'in_progress', 'approved', 'rejected'])->default('pending');
            $table->boolean('is_custom')->default(false);
            $table->timestamps();

            $table->foreign('visa_status_id')->references('id')->on('visa_statuses')->cascadeOnDelete();
            $table->index(['visa_status_id', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('visa_process_steps');
    }
};
