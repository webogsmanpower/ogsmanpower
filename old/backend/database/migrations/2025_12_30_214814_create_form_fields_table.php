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
        Schema::create('form_fields', function (Blueprint $table) {
            $table->id();
            $table->foreignId('section_id')->constrained('form_sections')->onDelete('cascade');
            $table->string('label'); // "Date of Birth", "First Name"
            $table->string('name'); // dob, first_name
            $table->string('type'); // text, number, date, select, file, textarea, rich_text, tags
            $table->boolean('required')->default(false);
            $table->json('options')->nullable(); // for select/multi-select: [{"value": "A+", "label": "A+"}]
            $table->integer('sort_order')->default(0);
            $table->boolean('is_system')->default(false); // prevents deleting core fields
            $table->text('placeholder')->nullable();
            $table->text('help_text')->nullable();
            $table->string('validation_rules')->nullable(); // laravel validation rules string
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            
            $table->index(['section_id', 'is_active', 'sort_order']);
            $table->unique(['section_id', 'name']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('form_fields');
    }
};
