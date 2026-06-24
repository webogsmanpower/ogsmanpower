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
        // Site Settings table for logo and global settings
        Schema::create('site_settings', function (Blueprint $table) {
            $table->id();
            $table->string('key')->unique();
            $table->json('value');
            $table->string('type')->default('text'); // text, image, json, boolean
            $table->text('description')->nullable();
            $table->timestamps();
            
            $table->index('key');
        });

        // Pages table for dynamic page management
        Schema::create('pages', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->string('slug')->unique();
            $table->text('meta_description')->nullable();
            $table->json('meta_keywords')->nullable();
            $table->string('status')->default('draft'); // draft, published, archived
            $table->string('template')->default('default'); // default, landing, about, contact
            $table->boolean('is_homepage')->default(false);
            $table->integer('sort_order')->default(0);
            $table->timestamps();
            
            $table->index('slug');
            $table->index('status');
            $table->index('is_homepage');
        });

        // Content Blocks table for flexible content management
        Schema::create('content_blocks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('page_id')->constrained()->onDelete('cascade');
            $table->string('type'); // hero, features, text, image, cta, testimonials
            $table->string('name')->nullable(); // For identification
            $table->json('content'); // Flexible content storage
            $table->integer('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            
            $table->index(['page_id', 'sort_order']);
            $table->index('type');
        });

        // Navigation Menus table
        Schema::create('navigation_menus', function (Blueprint $table) {
            $table->id();
            $table->string('name'); // main, footer, admin
            $table->string('location'); // header, footer
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            
            $table->index('location');
        });

        // Navigation Items table
        Schema::create('navigation_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('menu_id')->constrained('navigation_menus')->onDelete('cascade');
            $table->string('label');
            $table->string('url');
            $table->string('target')->default('_self'); // _self, _blank
            $table->integer('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->foreignId('parent_id')->nullable()->constrained('navigation_items')->onDelete('cascade');
            $table->timestamps();
            
            $table->index(['menu_id', 'sort_order']);
            $table->index('parent_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('navigation_items');
        Schema::dropIfExists('navigation_menus');
        Schema::dropIfExists('content_blocks');
        Schema::dropIfExists('pages');
        Schema::dropIfExists('site_settings');
    }
};
