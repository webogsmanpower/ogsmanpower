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
            // Add columns if they don't exist
            if (!Schema::hasColumn('plans', 'name_ar')) {
                $table->string('name_ar')->nullable();
            }
            
            if (!Schema::hasColumn('plans', 'description_ar')) {
                $table->text('description_ar')->nullable();
            }
            
            if (!Schema::hasColumn('plans', 'trial_days')) {
                $table->integer('trial_days')->default(0);
            }
            
            if (!Schema::hasColumn('plans', 'stripe_price_id')) {
                $table->string('stripe_price_id')->nullable();
            }
            
            if (!Schema::hasColumn('plans', 'paypal_plan_id')) {
                $table->string('paypal_plan_id')->nullable();
            }
            
            if (!Schema::hasColumn('plans', 'sort_order')) {
                $table->integer('sort_order')->default(0);
            }
            
            // Add indexes if they don't exist
            if (!Schema::hasIndex('plans', 'plans_role_type_is_active_index')) {
                $table->index(['role_type', 'is_active']);
            }
            
            if (!Schema::hasIndex('plans', 'plans_is_addon_is_active_index')) {
                $table->index(['is_addon', 'is_active']);
            }
            
            if (!Schema::hasIndex('plans', 'plans_sort_order_index')) {
                $table->index('sort_order');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('plans', function (Blueprint $table) {
            // Drop indexes if they exist
            $sm = Schema::getConnection()->getDoctrineSchemaManager();
            $indexes = $sm->listTableIndexes('plans');
            
            if (isset($indexes['plans_role_type_is_active_index'])) {
                $table->dropIndex(['role_type', 'is_active']);
            }
            
            if (isset($indexes['plans_is_addon_is_active_index'])) {
                $table->dropIndex(['is_addon', 'is_active']);
            }
            
            if (isset($indexes['plans_sort_order_index'])) {
                $table->dropIndex('sort_order');
            }
            
            // Drop columns if they exist
            if (Schema::hasColumn('plans', 'name_ar')) {
                $table->dropColumn('name_ar');
            }
            
            if (Schema::hasColumn('plans', 'description_ar')) {
                $table->dropColumn('description_ar');
            }
            
            if (Schema::hasColumn('plans', 'trial_days')) {
                $table->dropColumn('trial_days');
            }
            
            if (Schema::hasColumn('plans', 'stripe_price_id')) {
                $table->dropColumn('stripe_price_id');
            }
            
            if (Schema::hasColumn('plans', 'paypal_plan_id')) {
                $table->dropColumn('paypal_plan_id');
            }
            
            if (Schema::hasColumn('plans', 'sort_order')) {
                $table->dropColumn('sort_order');
            }
        });
    }
};
