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
        Schema::table('form_sections', function (Blueprint $table) {
            $table->boolean('is_multi_entry')->default(false)->after('is_active')->comment('Support for multiple entries');
            $table->string('add_new_label')->nullable()->after('is_multi_entry')->comment('Label for add new button');
            $table->string('entry_title_template')->nullable()->after('add_new_label')->comment('Template for entry titles');
            $table->string('style_variant')->default('default')->after('entry_title_template')->comment('Style variant like sectioned-card');
            $table->text('description')->nullable()->after('style_variant')->comment('Section description');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('form_sections', function (Blueprint $table) {
            $table->dropColumn([
                'is_multi_entry',
                'add_new_label',
                'entry_title_template', 
                'style_variant',
                'description'
            ]);
        });
    }
};
