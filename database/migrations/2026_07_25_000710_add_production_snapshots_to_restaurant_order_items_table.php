<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('restaurant_order_items', function (Blueprint $table): void {
            $table->string('item_name')->nullable()->after('menu_item_id');
            $table->string('production_unit', 40)->default('portion')->after('item_name');
            $table->decimal('production_usage_per_sale', 12, 3)->default(1)->after('production_unit');
        });
    }

    public function down(): void
    {
        Schema::table('restaurant_order_items', fn (Blueprint $table) => $table->dropColumn(['item_name', 'production_unit', 'production_usage_per_sale']));
    }
};
