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
        if (!Schema::hasTable('cashier_subscription_items')) {
            return;
        }

        Schema::table('cashier_subscription_items', function (Blueprint $table) {
            if (!Schema::hasColumn('cashier_subscription_items', 'meter_id')) {
                $table->string('meter_id')->nullable()->after('stripe_price');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (!Schema::hasTable('cashier_subscription_items')) {
            return;
        }

        Schema::table('cashier_subscription_items', function (Blueprint $table) {
            if (Schema::hasColumn('cashier_subscription_items', 'meter_id')) {
                $table->dropColumn('meter_id');
            }
        });
    }
};
