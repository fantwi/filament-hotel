<?php

namespace Tests\Feature;

use App\Models\Ingredient;
use App\Models\KitchenProduction;
use App\Models\KitchenStockMovement;
use App\Models\MenuCategory;
use App\Models\MenuItem;
use App\Models\Restaurant;
use App\Models\RestaurantOrder;
use App\Models\User;
use App\Services\KitchenStockService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class KitchenStockServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_receipts_wastage_and_stock_counts_create_auditable_ledger(): void
    {
        $ingredient = $this->ingredient();
        $stock = app(KitchenStockService::class);

        $stock->receive($ingredient, 10, 8.5, 'DEL-001');
        $stock->recordWastage($ingredient, 1.25, 'Spoiled delivery');
        $stock->adjustToCountedStock($ingredient, 7.5, 'Physical count');

        $ingredient->refresh();

        $this->assertSame('7.500', $ingredient->current_stock);
        $this->assertSame(3, KitchenStockMovement::query()->count());
        $this->assertSame(KitchenStockMovement::TYPE_RECEIPT, KitchenStockMovement::query()->oldest('id')->value('type'));
        $this->assertSame(KitchenStockMovement::TYPE_ADJUSTMENT_OUT, KitchenStockMovement::query()->latest('id')->value('type'));
    }

    public function test_order_consumption_is_idempotent_and_is_reversed_when_cancelled(): void
    {
        $ingredient = $this->ingredient(10);
        $menuItem = $this->menuItem();
        $order = RestaurantOrder::create([
            'order_number' => 'FOOD-TEST-001',
            'ordering_channel' => 'web',
            'subtotal' => 20,
            'total' => 20,
            'payment_status' => 'completed',
            'status' => 'confirmed',
        ]);
        $order->items()->create([
            'menu_item_id' => $menuItem->id,
            'item_name' => 'Test Meal',
            'quantity' => 2,
            'unit_price' => 10,
            'total_price' => 20,
            'ingredient_usage_snapshot' => [[
                'ingredient_id' => $ingredient->id,
                'quantity_per_item' => 1.25,
                'consumption_mode' => 'per_order',
            ]],
        ]);

        $stock = app(KitchenStockService::class);
        $stock->consumeForOrder($order);
        $stock->consumeForOrder($order);

        $ingredient->refresh();
        $order->refresh();
        $this->assertSame('7.500', $ingredient->current_stock);
        $this->assertNotNull($order->stock_deducted_at);
        $this->assertSame(1, KitchenStockMovement::query()->where('type', KitchenStockMovement::TYPE_CONSUMPTION)->count());

        $stock->reverseForOrder($order);
        $stock->reverseForOrder($order);

        $ingredient->refresh();
        $order->refresh();
        $this->assertSame('10.000', $ingredient->current_stock);
        $this->assertNotNull($order->stock_reversed_at);
        $this->assertSame(1, KitchenStockMovement::query()->where('type', KitchenStockMovement::TYPE_REVERSAL)->count());
    }

    public function test_batch_production_consumes_ingredients_once_for_batch_mode_items(): void
    {
        $ingredient = $this->ingredient(10);
        $menuItem = $this->menuItem('production_batch');
        $menuItem->recipeIngredients()->create([
            'ingredient_id' => $ingredient->id,
            'quantity_per_item' => 0.5,
        ]);
        $production = KitchenProduction::create([
            'menu_item_id' => $menuItem->id,
            'production_date' => today(),
            'quantity_produced' => 4,
            'quantity_wasted' => 0,
        ]);

        $stock = app(KitchenStockService::class);
        $stock->consumeForProduction($production);
        $stock->consumeForProduction($production);

        $ingredient->refresh();
        $this->assertSame('8.000', $ingredient->current_stock);
        $this->assertSame(1, KitchenStockMovement::query()->whereMorphedTo('reference', $production)->where('type', KitchenStockMovement::TYPE_CONSUMPTION)->count());
    }

    public function test_voiding_a_production_batch_restores_stock_and_preserves_its_audit_record(): void
    {
        [$actor, $ingredient, $production] = $this->postedProductionBatch();
        $stock = app(KitchenStockService::class);
        $stock->voidProduction($production, 'Batch was entered twice.', $actor);

        $production->refresh();

        $this->assertSame('10.000', $ingredient->refresh()->current_stock);
        $this->assertNotNull($production->voided_at);
        $this->assertSame($actor->id, $production->voided_by);
        $this->assertSame('Batch was entered twice.', $production->void_reason);
        $this->assertDatabaseHas('kitchen_productions', ['id' => $production->id]);
        $this->assertDatabaseHas('kitchen_production_ingredients', [
            'kitchen_production_id' => $production->id,
            'ingredient_id' => $ingredient->id,
        ]);
        $this->assertDatabaseHas('kitchen_stock_movements', [
            'ingredient_id' => $ingredient->id,
            'type' => KitchenStockMovement::TYPE_REVERSAL,
            'direction' => KitchenStockMovement::DIRECTION_IN,
            'quantity' => 2,
            'reference_type' => $production->getMorphClass(),
            'reference_id' => $production->id,
            'performed_by' => $actor->id,
        ]);
    }

    public function test_voiding_a_production_batch_twice_does_not_restore_stock_twice(): void
    {
        [$actor, $ingredient, $production] = $this->postedProductionBatch();
        $stock = app(KitchenStockService::class);
        $stock->voidProduction($production, 'Original void reason.', $actor);
        $stock->voidProduction($production, 'Duplicate request.', $actor);

        $this->assertSame('10.000', $ingredient->refresh()->current_stock);
        $this->assertSame('Original void reason.', $production->refresh()->void_reason);
        $this->assertSame(
            1,
            KitchenStockMovement::query()
                ->whereMorphedTo('reference', $production)
                ->where('type', KitchenStockMovement::TYPE_REVERSAL)
                ->count(),
        );
    }

    public function test_voiding_a_production_batch_requires_an_audit_reason(): void
    {
        [$actor, $ingredient, $production] = $this->postedProductionBatch();
        $stock = app(KitchenStockService::class);

        try {
            $stock->voidProduction($production, '   ', $actor);
            $this->fail('A blank void reason should be rejected.');
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey('reason', $exception->errors());
        }

        $this->assertSame('8.000', $ingredient->refresh()->current_stock);
        $this->assertNull($production->refresh()->voided_at);
        $this->assertSame(0, KitchenStockMovement::query()->where('type', KitchenStockMovement::TYPE_REVERSAL)->count());
    }

    /**
     * @return array{User, Ingredient, KitchenProduction}
     */
    private function postedProductionBatch(): array
    {
        $actor = User::factory()->create(['department' => 'kitchen_manager']);
        $ingredient = $this->ingredient(10);
        $menuItem = $this->menuItem('production_batch');
        $production = KitchenProduction::create([
            'menu_item_id' => $menuItem->id,
            'production_date' => today(),
            'quantity_produced' => 4,
            'quantity_wasted' => 0,
        ]);
        $production->ingredients()->create([
            'ingredient_id' => $ingredient->id,
            'quantity_used' => 2,
            'unit' => 'kg',
        ]);
        app(KitchenStockService::class)->consumeForProduction($production);

        return [$actor, $ingredient, $production];
    }

    private function ingredient(float $stock = 0): Ingredient
    {
        $restaurant = Restaurant::create([
            'name' => 'Test Restaurant',
            'description' => 'Test restaurant',
            'opening_time' => '08:00',
            'closing_time' => '22:00',
        ]);

        return Ingredient::create([
            'restaurant_id' => $restaurant->id,
            'name' => 'Rice '.str()->random(8),
            'unit' => 'kg',
            'current_stock' => $stock,
            'reorder_level' => 2,
            'unit_cost' => 5,
        ]);
    }

    private function menuItem(string $inventoryConsumptionMode = 'per_order'): MenuItem
    {
        $category = MenuCategory::create([
            'name' => 'Test Category '.str()->random(8),
            'slug' => str()->random(12),
        ]);

        return MenuItem::create([
            'menu_category_id' => $category->id,
            'name' => 'Test Meal '.str()->random(8),
            'slug' => str()->random(12),
            'price' => 10,
            'inventory_consumption_mode' => $inventoryConsumptionMode,
        ]);
    }
}
