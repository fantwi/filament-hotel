<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Assign production batches to the restaurant whose stock they consume.
     */
    public function up(): void
    {
        Schema::disableForeignKeyConstraints();

        try {
            Schema::table('kitchen_productions', function (Blueprint $table): void {
                $table->foreignId('restaurant_id')
                    ->nullable()
                    ->after('menu_item_id')
                    ->constrained()
                    ->restrictOnDelete();
            });
        } finally {
            Schema::enableForeignKeyConstraints();
        }

        $restaurantIds = DB::table('restaurants')->orderBy('id')->limit(2)->pluck('id');
        $soleRestaurantId = $restaurantIds->count() === 1 ? $restaurantIds->first() : null;

        DB::table('kitchen_productions')
            ->select('id')
            ->orderBy('id')
            ->chunkById(100, function ($productions) use ($soleRestaurantId): void {
                foreach ($productions as $production) {
                    $ingredientRestaurantIds = DB::table('kitchen_production_ingredients')
                        ->join('ingredients', 'ingredients.id', '=', 'kitchen_production_ingredients.ingredient_id')
                        ->where('kitchen_production_ingredients.kitchen_production_id', $production->id)
                        ->distinct()
                        ->pluck('ingredients.restaurant_id');

                    $restaurantId = $ingredientRestaurantIds->count() === 1
                        ? $ingredientRestaurantIds->first()
                        : $soleRestaurantId;

                    if ($restaurantId !== null) {
                        DB::table('kitchen_productions')
                            ->where('id', $production->id)
                            ->update(['restaurant_id' => $restaurantId]);
                    }
                }
            });
    }

    /**
     * Remove restaurant ownership from production batches.
     */
    public function down(): void
    {
        Schema::table('kitchen_productions', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('restaurant_id');
        });
    }
};
