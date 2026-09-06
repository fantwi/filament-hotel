<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Adds the timestamp-first index used by stock-ledger period filters.
     */
    public function up(): void
    {
        Schema::table('kitchen_stock_movements', function (Blueprint $table): void {
            $table->index(
                'occurred_at',
                'kitchen_stock_movements_occurred_at_index',
            );
        });
    }

    /**
     * Removes the stock-ledger timestamp index.
     */
    public function down(): void
    {
        Schema::table('kitchen_stock_movements', function (Blueprint $table): void {
            $table->dropIndex('kitchen_stock_movements_occurred_at_index');
        });
    }
};
