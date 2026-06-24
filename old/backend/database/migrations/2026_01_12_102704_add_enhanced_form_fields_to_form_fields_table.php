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
        Schema::table('form_fields', function (Blueprint $table) {
            $table->string('section')->nullable()->after('is_active')->comment('Subsection grouping');
            $table->tinyInteger('col_span')->default(1)->after('section')->comment('Field width 1-2');
            $table->string('variant')->nullable()->after('col_span')->comment('Field variant like avatar');
            $table->text('helper_text')->nullable()->after('help_text')->comment('Additional help text');
            $table->string('component')->nullable()->after('helper_text')->comment('Custom component name');
            $table->json('validation_options')->nullable()->after('validation_rules')->comment('Advanced validation options');
            $table->tinyInteger('min_validity_months')->nullable()->after('validation_options')->comment('For date fields validity');
            $table->text('min_validity_message')->nullable()->after('min_validity_months')->comment('Custom validation message');
            $table->json('country_code_options')->nullable()->after('min_validity_message')->comment('For phone fields');
            $table->string('default_country_code')->nullable()->after('country_code_options')->comment('Default country code for phone fields');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('form_fields', function (Blueprint $table) {
            $table->dropColumn([
                'section',
                'col_span', 
                'variant',
                'helper_text',
                'component',
                'validation_options',
                'min_validity_months',
                'min_validity_message',
                'country_code_options',
                'default_country_code'
            ]);
        });
    }
};
