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
        Schema::table('users', function (Blueprint $table) {
            // Add date_of_birth column
            if (!Schema::hasColumn('users', 'date_of_birth')) {
                $table->date('date_of_birth')->nullable()->after('mobile');
            }
            
            // Add height column (in centimeters for backend storage)
            if (!Schema::hasColumn('users', 'height')) {
                $table->decimal('height', 5, 2)->nullable()->after('date_of_birth');
            }
            
            // Add weight column (in kilograms)
            if (!Schema::hasColumn('users', 'weight')) {
                $table->decimal('weight', 5, 2)->nullable()->after('height');
            }
            
            // Add chest_measurement column (in centimeters)
            if (!Schema::hasColumn('users', 'chest_measurement')) {
                $table->decimal('chest_measurement', 5, 2)->nullable()->after('weight');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (Schema::hasColumn('users', 'date_of_birth')) {
                $table->dropColumn('date_of_birth');
            }
            if (Schema::hasColumn('users', 'height')) {
                $table->dropColumn('height');
            }
            if (Schema::hasColumn('users', 'weight')) {
                $table->dropColumn('weight');
            }
            if (Schema::hasColumn('users', 'chest_measurement')) {
                $table->dropColumn('chest_measurement');
            }
        });
    }
};
