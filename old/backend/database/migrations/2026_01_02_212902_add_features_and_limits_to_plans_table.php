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
            // Only add columns if they don't exist
            if (!Schema::hasColumn('plans', 'features')) {
                $table->json('features')->nullable()->after('description');
            }
            
            if (!Schema::hasColumn('plans', 'limits')) {
                $table->json('limits')->nullable()->after('description');
            }
            
            if (!Schema::hasColumn('plans', 'bilingual_cv_price')) {
                $table->decimal('bilingual_cv_price', 8, 2)->default(1.00)->after('description');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('plans', function (Blueprint $table) {
            $table->dropColumn(['features', 'limits', 'bilingual_cv_price']);
        });
    }
};
