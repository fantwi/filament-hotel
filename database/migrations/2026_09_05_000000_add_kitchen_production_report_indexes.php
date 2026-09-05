<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Adds date-first indexes used by the kitchen production report's period
     * and opening-balance aggregates.
     */
    public function up(): void
    {
        Schema::table('kitchen_productions', function (Blueprint $table): void {
            $table->index(
                ['production_date', 'menu_item_id'],
                'kitchen_productions_report_period_index',
            );
        });

        Schema::table('restaurant_orders', function (Blueprint $table): void {
            $table->index(
                ['stock_deducted_at', 'id'],
                'restaurant_orders_stock_deducted_report_index',
            );
            $table->index(
                ['stock_reversed_at', 'id'],
                'restaurant_orders_stock_reversed_report_index',
            );
        });
    }

    /**
     * Removes the kitchen production report indexes.
     */
    public function down(): void
    {
        Schema::table('kitchen_productions', function (Blueprint $table): void {
            $table->dropIndex('kitchen_productions_report_period_index');
        });

        Schema::table('restaurant_orders', function (Blueprint $table): void {
            $table->dropIndex('restaurant_orders_stock_deducted_report_index');
            $table->dropIndex('restaurant_orders_stock_reversed_report_index');
        });
    }
};
