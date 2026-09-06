<?php

namespace Tests\Feature;

use App\Filament\Admin\Resources\KitchenStockMovements\KitchenStockMovementResource;
use App\Models\Ingredient;
use App\Models\KitchenProduction;
use App\Models\KitchenStockMovement;
use App\Models\Restaurant;
use App\Models\RestaurantOrder;
use App\Models\User;
use Filament\Support\Enums\Width;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class KitchenStockMovementFiltersTest extends TestCase
{
    use RefreshDatabase;

    public function test_operational_filters_are_complete_and_use_user_facing_choices(): void
    {
        $restaurant = $this->restaurant('Main Restaurant');
        $unusedRestaurant = $this->restaurant('Unused Restaurant');
        $ingredient = $this->ingredient($restaurant, 'Rice');
        $recorder = $this->staff('Ama', 'Mensah');
        $unusedRecorder = $this->staff('Kojo', 'Asare');

        $this->movement($ingredient, $recorder, referenceType: (new KitchenProduction)->getMorphClass());

        $table = $this->table();

        self::assertSame([
            'ingredient_id',
            'type',
            'direction',
            'restaurant',
            'performed_by',
            'source',
            'date_preset',
            'occurred_at',
        ], array_keys($table->getFilters()));
        self::assertSame([
            KitchenStockMovement::DIRECTION_IN => 'Stock in',
            KitchenStockMovement::DIRECTION_OUT => 'Stock out',
        ], $table->getFilter('direction')?->getOptions());
        self::assertSame([$restaurant->id => 'Main Restaurant'], $table->getFilter('restaurant')?->getOptions());
        self::assertSame([$recorder->id => 'Ama Mensah'], $table->getFilter('performed_by')?->getOptions());
        self::assertArrayNotHasKey($unusedRestaurant->id, $table->getFilter('restaurant')?->getOptions() ?? []);
        self::assertArrayNotHasKey($unusedRecorder->id, $table->getFilter('performed_by')?->getOptions() ?? []);
        self::assertSame([
            (new KitchenProduction)->getMorphClass() => 'Production batch',
            (new RestaurantOrder)->getMorphClass() => 'Food order',
            'manual' => 'Manual entry',
        ], $table->getFilter('source')?->getOptions());
        self::assertSame([
            'today' => 'Today',
            'last_7_days' => 'Last 7 days',
            'this_month' => 'This month',
        ], $table->getFilter('date_preset')?->getOptions());
    }

    public function test_direction_restaurant_recorder_and_source_filters_combine_without_leaking_other_movements(): void
    {
        $mainRestaurant = $this->restaurant('Main Restaurant');
        $otherRestaurant = $this->restaurant('Pool Restaurant');
        $mainRice = $this->ingredient($mainRestaurant, 'Rice');
        $poolRice = $this->ingredient($otherRestaurant, 'Rice');
        $recorder = $this->staff('Ama', 'Mensah');
        $otherRecorder = $this->staff('Kojo', 'Asare');
        $productionSource = (new KitchenProduction)->getMorphClass();
        $orderSource = (new RestaurantOrder)->getMorphClass();

        $target = $this->movement($mainRice, $recorder, referenceType: $productionSource);
        $this->movement($poolRice, $recorder, referenceType: $productionSource);
        $this->movement($mainRice, $otherRecorder, referenceType: $productionSource);
        $this->movement($mainRice, $recorder, direction: KitchenStockMovement::DIRECTION_OUT, referenceType: $productionSource);
        $this->movement($mainRice, $recorder, referenceType: $orderSource);
        $this->movement($mainRice, $recorder);

        $query = KitchenStockMovement::query();
        $table = $this->table();

        $table->getFilter('direction')?->apply($query, ['value' => KitchenStockMovement::DIRECTION_IN]);
        $table->getFilter('restaurant')?->apply($query, ['value' => $mainRestaurant->id]);
        $table->getFilter('performed_by')?->apply($query, ['value' => $recorder->id]);
        $table->getFilter('source')?->apply($query, ['value' => $productionSource]);

        self::assertSame([$target->id], $query->pluck('id')->all());
    }

    public function test_manual_source_filter_returns_only_unlinked_stock_entries(): void
    {
        $restaurant = $this->restaurant('Main Restaurant');
        $ingredient = $this->ingredient($restaurant, 'Rice');
        $recorder = $this->staff('Ama', 'Mensah');
        $manual = $this->movement($ingredient, $recorder);
        $this->movement($ingredient, $recorder, referenceType: (new KitchenProduction)->getMorphClass());

        $query = KitchenStockMovement::query();
        $this->table()->getFilter('source')?->apply($query, ['value' => 'manual']);

        self::assertSame([$manual->id], $query->pluck('id')->all());
    }

    public function test_quick_periods_use_exact_inclusive_operational_day_boundaries(): void
    {
        $this->travelTo('2026-09-15 12:00:00');

        $restaurant = $this->restaurant('Main Restaurant');
        $ingredient = $this->ingredient($restaurant, 'Rice');
        $recorder = $this->staff('Ama', 'Mensah');
        $today = $this->movement($ingredient, $recorder, occurredAt: '2026-09-15 23:59:59');
        $sixDaysAgo = $this->movement($ingredient, $recorder, occurredAt: '2026-09-09 00:00:00');
        $outsideSevenDays = $this->movement($ingredient, $recorder, occurredAt: '2026-09-08 23:59:59');
        $monthStart = $this->movement($ingredient, $recorder, occurredAt: '2026-09-01 00:00:00');
        $previousMonth = $this->movement($ingredient, $recorder, occurredAt: '2026-08-31 23:59:59');

        self::assertSame([$today->id], $this->filteredPeriodIds('today'));
        self::assertSame([$today->id, $sixDaysAgo->id], $this->filteredPeriodIds('last_7_days'));
        self::assertSame(
            [$today->id, $sixDaysAgo->id, $outsideSevenDays->id, $monthStart->id],
            $this->filteredPeriodIds('this_month'),
        );
        self::assertNotContains($previousMonth->id, $this->filteredPeriodIds('this_month'));

        $this->travelBack();
    }

    public function test_filter_panel_uses_a_wide_responsive_grid(): void
    {
        $table = $this->table();
        $dateRange = $table->getFilter('occurred_at');

        self::assertSame(['default' => 1, 'md' => 2, 'xl' => 3], $table->getFiltersFormColumns());
        self::assertSame(Width::FourExtraLarge, $table->getFiltersFormWidth());
        self::assertSame(['default' => 1, 'sm' => 2], $dateRange?->getColumns());
        self::assertSame(['default' => 1, 'md' => 2], $dateRange?->getColumnSpan());
    }

    private function table(): Table
    {
        return KitchenStockMovementResource::table(Table::make($this->createMock(HasTable::class)));
    }

    /** @return list<int> */
    private function filteredPeriodIds(string $period): array
    {
        $query = KitchenStockMovement::query()->orderByDesc('occurred_at')->orderByDesc('id');
        $this->table()->getFilter('date_preset')?->apply($query, ['value' => $period]);

        return $query->pluck('id')->all();
    }

    private function restaurant(string $name): Restaurant
    {
        return Restaurant::query()->create([
            'name' => $name,
            'description' => "{$name} filter fixture.",
            'opening_time' => '08:00',
            'closing_time' => '22:00',
        ]);
    }

    private function ingredient(Restaurant $restaurant, string $name): Ingredient
    {
        return Ingredient::query()->create([
            'restaurant_id' => $restaurant->id,
            'name' => $name,
            'unit' => 'kg',
            'current_stock' => 20,
            'reorder_level' => 5,
            'unit_cost' => 4,
            'is_active' => true,
        ]);
    }

    private function staff(string $firstName, string $lastName): User
    {
        return User::withoutEvents(fn (): User => User::factory()->create([
            'department' => 'kitchen_staff',
            'first_name' => $firstName,
            'last_name' => $lastName,
        ]));
    }

    private function movement(
        Ingredient $ingredient,
        User $recorder,
        string $direction = KitchenStockMovement::DIRECTION_IN,
        ?string $referenceType = null,
        string $occurredAt = '2026-09-10 10:00:00',
    ): KitchenStockMovement {
        return KitchenStockMovement::query()->create([
            'ingredient_id' => $ingredient->id,
            'type' => $direction === KitchenStockMovement::DIRECTION_IN
                ? KitchenStockMovement::TYPE_RECEIPT
                : KitchenStockMovement::TYPE_CONSUMPTION,
            'direction' => $direction,
            'quantity' => 2,
            'balance_before' => 10,
            'balance_after' => $direction === KitchenStockMovement::DIRECTION_IN ? 12 : 8,
            'performed_by' => $recorder->id,
            'reference_type' => $referenceType,
            'reference_id' => $referenceType === null ? null : 999,
            'occurred_at' => $occurredAt,
        ]);
    }
}
