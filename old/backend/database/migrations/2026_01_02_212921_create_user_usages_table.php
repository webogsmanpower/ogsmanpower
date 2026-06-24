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
        Schema::create('user_usages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('feature_key'); // 'application', 'bilingual_cv', 'job_alert', etc.
            $table->integer('count')->default(0);
            $table->date('reset_at'); // When this counter resets (monthly)
            $table->timestamps();
            
            // Indexes
            $table->index(['user_id', 'feature_key', 'reset_at']);
            $table->unique(['user_id', 'feature_key', 'reset_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('user_usages');
    }
};
