<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('menu_items', function (Blueprint $table): void {
            $table->boolean('tracks_kitchen_production')->default(false)->after('is_featured');
            $table->string('production_unit', 40)->default('portion')->after('tracks_kitchen_production');
            $table->decimal('production_usage_per_sale', 12, 3)->default(1)->after('production_unit');
            $table->decimal('low_stock_threshold', 12, 3)->default(0)->after('production_usage_per_sale');
        });
    }
    public function down(): void { Schema::table('menu_items', fn (Blueprint $table) => $table->dropColumn(['tracks_kitchen_production', 'production_unit', 'production_usage_per_sale', 'low_stock_threshold'])); }
};
