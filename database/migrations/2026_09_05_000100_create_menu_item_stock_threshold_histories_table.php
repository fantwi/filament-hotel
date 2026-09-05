<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Applies this database schema change and establishes the initial threshold history.
     */
    public function up(): void
    {
        Schema::create('menu_item_stock_threshold_histories', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('menu_item_id')->constrained()->cascadeOnDelete();
            $table->decimal('threshold', 12, 3);
            $table->dateTime('effective_from');
            $table->timestamps();
            $table->index(
                ['menu_item_id', 'effective_from', 'id'],
                'menu_item_threshold_effective_index',
            );
        });

        $recordedAt = now();

        DB::table('menu_items')
            ->select(['id', 'low_stock_threshold', 'created_at', 'updated_at'])
            ->orderBy('id')
            ->chunkById(500, function ($menuItems) use ($recordedAt): void {
                $rows = $menuItems->map(fn (object $menuItem): array => [
                    'menu_item_id' => $menuItem->id,
                    'threshold' => $menuItem->low_stock_threshold ?? 0,
                    'effective_from' => $menuItem->created_at ?? $menuItem->updated_at ?? $recordedAt,
                    'created_at' => $recordedAt,
                    'updated_at' => $recordedAt,
                ])->all();

                if ($rows !== []) {
                    DB::table('menu_item_stock_threshold_histories')->insert($rows);
                }
            });
    }

    /**
     * Reverts this database schema change.
     */
    public function down(): void
    {
        Schema::dropIfExists('menu_item_stock_threshold_histories');
    }
};
