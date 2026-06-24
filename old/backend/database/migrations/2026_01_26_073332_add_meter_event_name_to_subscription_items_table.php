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
            if (!Schema::hasColumn('cashier_subscription_items', 'meter_event_name')) {
                $table->string('meter_event_name')->nullable()->after('quantity');
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
            if (Schema::hasColumn('cashier_subscription_items', 'meter_event_name')) {
                $table->dropColumn('meter_event_name');
            }
        });
    }
};
