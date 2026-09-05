<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Prevent menu-item deletion from removing kitchen production history.
     */
    public function up(): void
    {
        Schema::table('kitchen_productions', function (Blueprint $table): void {
            $table->dropForeign(['menu_item_id']);
            $table->foreign('menu_item_id')
                ->references('id')
                ->on('menu_items')
                ->restrictOnDelete();
        });
    }

    /**
     * Restore the original cascading relationship.
     */
    public function down(): void
    {
        Schema::table('kitchen_productions', function (Blueprint $table): void {
            $table->dropForeign(['menu_item_id']);
            $table->foreign('menu_item_id')
                ->references('id')
                ->on('menu_items')
                ->cascadeOnDelete();
        });
    }
};
