<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('menu_items', function (Blueprint $table): void {
            $table->string('inventory_consumption_mode')->default('per_order')->after('production_usage_per_sale');
        });

        Schema::table('restaurant_order_items', function (Blueprint $table): void {
            $table->json('ingredient_usage_snapshot')->nullable()->after('production_usage_per_sale');
        });

        Schema::table('restaurant_orders', function (Blueprint $table): void {
            $table->timestamp('stock_deducted_at')->nullable()->after('cancelled_at');
            $table->timestamp('stock_reversed_at')->nullable()->after('stock_deducted_at');
        });
    }

    public function down(): void
    {
        Schema::table('restaurant_orders', function (Blueprint $table): void {
            $table->dropColumn(['stock_deducted_at', 'stock_reversed_at']);
        });

        Schema::table('restaurant_order_items', function (Blueprint $table): void {
            $table->dropColumn('ingredient_usage_snapshot');
        });

        Schema::table('menu_items', function (Blueprint $table): void {
            $table->dropColumn('inventory_consumption_mode');
        });
    }
};
