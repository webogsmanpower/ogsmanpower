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
        Schema::table('plans', function (Blueprint $table) {
            $table->boolean('discount_enabled')->default(false)->after('sort_order');
            $table->decimal('discount_percentage', 5, 2)->nullable()->after('discount_enabled'); // Supports values like 99.99
            $table->date('discount_valid_until')->nullable()->after('discount_percentage');
            
            // Index for discount queries
            $table->index(['discount_enabled', 'discount_valid_until']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('plans', function (Blueprint $table) {
            $table->dropIndex(['discount_enabled', 'discount_valid_until']);
            $table->dropColumn(['discount_enabled', 'discount_percentage', 'discount_valid_until']);
        });
    }
};
