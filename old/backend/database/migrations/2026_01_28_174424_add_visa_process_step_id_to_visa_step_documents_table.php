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
        Schema::table('visa_step_documents', function (Blueprint $table) {
            $table->unsignedBigInteger('visa_process_step_id')->nullable()->after('visa_step_id');

            $table->foreign('visa_process_step_id')
                ->references('id')
                ->on('visa_process_steps')
                ->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('visa_step_documents', function (Blueprint $table) {
            $table->dropForeign(['visa_process_step_id']);
            $table->dropColumn('visa_process_step_id');
        });
    }
};
