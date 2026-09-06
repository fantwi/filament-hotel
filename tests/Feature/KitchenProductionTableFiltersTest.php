<?php

namespace Tests\Feature;

use App\Filament\Admin\Resources\KitchenProductions\KitchenProductionResource;
use App\Filament\Admin\Resources\KitchenProductions\Pages\ListKitchenProductions;
use App\Models\KitchenProduction;
use App\Models\MenuCategory;
use App\Models\MenuItem;
use App\Models\Restaurant;
use App\Models\User;
use Filament\Facades\Filament;
use Filament\Support\Enums\Width;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class KitchenProductionTableFiltersTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Filament::setCurrentPanel(Filament::getPanel('admin'));
    }

    public function test_filter_choices_include_only_values_represented_in_production_history(): void
    {
        $restaurant = $this->restaurant();
        $usedProducer = $this->staff('Ama', 'Mensah');
        $unusedProducer = $this->staff('Kojo', 'Asare');
        $usedCategory = $this->category('Prepared Meals');
        $unusedCategory = $this->category('Unproduced Meals');
        $historicalItem = $this->menuItem($usedCategory, 'Historical Jollof', tracksProduction: false);
        $unusedItem = $this->menuItem($unusedCategory, 'Unproduced Soup');

        $this->production($restaurant, $historicalItem, $usedProducer, '2026-09-01');

        $table = $this->table();

        self::assertSame(
            [$historicalItem->id => 'Historical Jollof'],
            $table->getFilter('menu_item')?->getOptions(),
        );
        self::assertSame(
            [$usedCategory->id => 'Prepared Meals'],
            $table->getFilter('category')?->getOptions(),
        );
        self::assertSame(
            [$usedProducer->id => 'Ama Mensah'],
            $table->getFilter('produced_by')?->getOptions(),
        );
        self::assertArrayNotHasKey($unusedItem->id, $table->getFilter('menu_item')?->getOptions() ?? []);
        self::assertArrayNotHasKey($unusedCategory->id, $table->getFilter('category')?->getOptions() ?? []);
        self::assertArrayNotHasKey($unusedProducer->id, $table->getFilter('produced_by')?->getOptions() ?? []);
    }

    public function test_filter_panel_uses_a_wide_responsive_grid_without_crowding_mobile_dates(): void
    {
        $table = $this->table();
        $productionDate = $table->getFilter('production_date');

        self::assertSame(['default' => 1, 'md' => 2, 'xl' => 3], $table->getFiltersFormColumns());
        self::assertSame(Width::FourExtraLarge, $table->getFiltersFormWidth());
        self::assertSame(['default' => 1, 'sm' => 2], $productionDate?->getColumns());
        self::assertSame(['default' => 1, 'md' => 2], $productionDate?->getColumnSpan());
    }

    public function test_category_producer_and_waste_filters_combine_without_leaking_other_batches(): void
    {
        $restaurant = $this->restaurant();
        $targetProducer = $this->staff('Efua', 'Owusu');
        $otherProducer = $this->staff('Yaw', 'Boateng');
        $targetCategory = $this->category('Main Meals');
        $otherCategory = $this->category('Desserts');
        $targetItem = $this->menuItem($targetCategory, 'Waakye');
        $otherItem = $this->menuItem($otherCategory, 'Fruit Salad');
        $target = $this->production($restaurant, $targetItem, $targetProducer, '2026-09-10', wasted: 2);
        $this->production($restaurant, $otherItem, $targetProducer, '2026-09-10', wasted: 2);
        $this->production($restaurant, $targetItem, $otherProducer, '2026-09-10', wasted: 2);
        $this->production($restaurant, $targetItem, $targetProducer, '2026-09-10', wasted: 0);

        $table = $this->table();
        $query = KitchenProduction::query();

        $table->getFilter('category')?->apply($query, ['value' => $targetCategory->id]);
        $table->getFilter('produced_by')?->apply($query, ['value' => $targetProducer->id]);
        $table->getFilter('waste_only')?->apply($query, ['isActive' => true]);

        self::assertSame([$target->id], $query->pluck('id')->all());
    }

    public function test_quick_period_filter_uses_exact_operational_date_ranges(): void
    {
        $this->travelTo('2026-09-15 12:00:00');

        $restaurant = $this->restaurant();
        $producer = $this->staff('Akosua', 'Darko');
        $category = $this->category('Quick Period Meals');
        $item = $this->menuItem($category, 'Banku');
        $today = $this->production($restaurant, $item, $producer, '2026-09-15');
        $sixDaysAgo = $this->production($restaurant, $item, $producer, '2026-09-09');
        $outsideSevenDays = $this->production($restaurant, $item, $producer, '2026-09-08');
        $monthStart = $this->production($restaurant, $item, $producer, '2026-09-01');
        $previousMonth = $this->production($restaurant, $item, $producer, '2026-08-12');
        $older = $this->production($restaurant, $item, $producer, '2026-07-31');

        self::assertSame([$today->id], $this->filteredIds('date_preset', 'today'));
        self::assertSame(
            [$today->id, $sixDaysAgo->id],
            $this->filteredIds('date_preset', 'last_7_days'),
        );
        self::assertSame(
            [$today->id, $sixDaysAgo->id, $outsideSevenDays->id, $monthStart->id],
            $this->filteredIds('date_preset', 'this_month'),
        );
        self::assertSame([$previousMonth->id], $this->filteredIds('date_preset', 'previous_month'));
        self::assertNotContains($older->id, $this->filteredIds('date_preset', 'previous_month'));

        $this->travelBack();
    }

    public function test_reversed_custom_dates_are_rejected_before_the_filter_becomes_active(): void
    {
        $staff = $this->staff('Kofi', 'Adjei', authorized: true);

        Livewire::actingAs($staff)
            ->test(ListKitchenProductions::class)
            ->set('tableDeferredFilters.production_date.from', '2026-09-20')
            ->set('tableDeferredFilters.production_date.until', '2026-09-10')
            ->call('applyTableFilters')
            ->assertHasErrors(['tableDeferredFilters.production_date.from'])
            ->assertSet('tableFilters.production_date.from', null)
            ->assertSet('tableFilters.production_date.until', null);
    }

    public function test_reversed_custom_dates_from_the_query_string_are_discarded_during_table_boot(): void
    {
        $staff = $this->staff('Abena', 'Ofori', authorized: true);

        Livewire::withQueryParams([
            'filters' => [
                'production_date' => [
                    'from' => '2026-09-20',
                    'until' => '2026-09-10',
                ],
            ],
        ])->actingAs($staff)
            ->test(ListKitchenProductions::class)
            ->assertSet('tableFilters.production_date.from', null)
            ->assertSet('tableFilters.production_date.until', null);
    }

    public function test_valid_custom_dates_from_the_query_string_remain_active(): void
    {
        $staff = $this->staff('Esi', 'Amoako', authorized: true);
        $restaurant = $this->restaurant();
        $category = $this->category('Custom Period Meals');
        $item = $this->menuItem($category, 'Red Red');
        $inside = $this->production($restaurant, $item, $staff, '2026-09-15');
        $before = $this->production($restaurant, $item, $staff, '2026-09-09');
        $after = $this->production($restaurant, $item, $staff, '2026-09-21');

        Livewire::withQueryParams([
            'filters' => [
                'production_date' => [
                    'from' => '2026-09-10',
                    'until' => '2026-09-20',
                ],
            ],
        ])->actingAs($staff)
            ->test(ListKitchenProductions::class)
            ->assertCanSeeTableRecords([$inside])
            ->assertCanNotSeeTableRecords([$before, $after]);
    }

    public function test_quick_period_predicates_keep_the_production_date_column_indexable(): void
    {
        $query = KitchenProduction::query();

        $this->table()->getFilter('date_preset')?->apply($query, ['value' => 'last_7_days']);

        self::assertNotContains('Date', array_column($query->getQuery()->wheres, 'type'));
    }

    public function test_default_sort_explicitly_uses_newest_date_then_newest_batch_id(): void
    {
        $sortedQuery = $this->table()->getDefaultSort(KitchenProduction::query(), 'desc');

        self::assertInstanceOf(Builder::class, $sortedQuery);
        self::assertSame([
            ['column' => 'production_date', 'direction' => 'desc'],
            ['column' => 'id', 'direction' => 'desc'],
        ], $sortedQuery->getQuery()->orders);
    }

    private function table(): Table
    {
        return KitchenProductionResource::table(Table::make($this->createMock(HasTable::class)));
    }

    /**
     * @return list<int>
     */
    private function filteredIds(string $filterName, string $value): array
    {
        $query = KitchenProduction::query()->orderByDesc('production_date')->orderByDesc('id');
        $this->table()->getFilter($filterName)?->apply($query, ['value' => $value]);

        return $query->pluck('id')->all();
    }

    private function restaurant(): Restaurant
    {
        return Restaurant::query()->create([
            'name' => 'Filter Kitchen '.str()->random(8),
            'description' => 'Kitchen production filter tests.',
            'opening_time' => '08:00',
            'closing_time' => '22:00',
        ]);
    }

    private function category(string $name): MenuCategory
    {
        return MenuCategory::query()->create([
            'name' => $name,
            'slug' => str()->random(16),
        ]);
    }

    private function menuItem(MenuCategory $category, string $name, bool $tracksProduction = true): MenuItem
    {
        return MenuItem::query()->create([
            'menu_category_id' => $category->id,
            'name' => $name,
            'slug' => str($name)->slug().'-'.str()->random(6),
            'price' => 25,
            'tracks_kitchen_production' => $tracksProduction,
            'production_unit' => 'portion',
            'inventory_consumption_mode' => 'none',
        ]);
    }

    private function staff(string $firstName, string $lastName, bool $authorized = false): User
    {
        $staff = User::factory()->create([
            'department' => 'kitchen_staff',
            'first_name' => $firstName,
            'last_name' => $lastName,
        ]);

        if ($authorized) {
            $staff->givePermissionTo(Permission::findOrCreate('manage kitchen production', 'web'));
        }

        return $staff;
    }

    private function production(
        Restaurant $restaurant,
        MenuItem $menuItem,
        User $producer,
        string $date,
        float $wasted = 0,
    ): KitchenProduction {
        return KitchenProduction::query()->create([
            'restaurant_id' => $restaurant->id,
            'menu_item_id' => $menuItem->id,
            'produced_by' => $producer->id,
            'production_date' => $date,
            'quantity_produced' => 20,
            'quantity_wasted' => $wasted,
        ]);
    }
}
