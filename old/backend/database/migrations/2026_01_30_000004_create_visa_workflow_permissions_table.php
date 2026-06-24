<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Create visa_workflow_permissions table for access control
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('visa_workflow_permissions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('visa_status_id')->constrained()->onDelete('cascade');
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->enum('permission_level', ['view', 'edit', 'verify', 'admin'])->default('view');
            $table->boolean('can_view_internal_notes')->default(false);
            $table->boolean('can_verify_documents')->default(false);
            $table->boolean('can_edit_steps')->default(false);
            $table->boolean('can_add_notes')->default(false);
            $table->boolean('can_request_documents')->default(false);
            $table->boolean('can_upload_documents')->default(false);
            $table->timestamps();
            
            $table->unique(['visa_status_id', 'user_id']);
            $table->index('user_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('visa_workflow_permissions');
    }
};
