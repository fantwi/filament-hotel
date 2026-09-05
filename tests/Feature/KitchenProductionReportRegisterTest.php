<?php

namespace Tests\Feature;

use App\Filament\Admin\Pages\KitchenProductionReport;
use App\Models\KitchenProduction;
use App\Models\MenuCategory;
use App\Models\MenuItem;
use App\Models\RestaurantOrder;
use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Pagination\LengthAwarePaginator;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class KitchenProductionReportRegisterTest extends TestCase
{
    use RefreshDatabase;

    public function test_search_category_and_stock_status_filters_only_narrow_the_register(): void
    {
        $this->travelTo('2026-09-05 09:00:00');
        [$healthyItem, $lowItem, $negativeItem] = $this->reportFixtures();
        $page = $this->reportPage();

        $page->reportSearch = 'braised';
        $report = $page->getReportProperty();

        self::assertInstanceOf(LengthAwarePaginator::class, $report['rows']);
        self::assertSame(3, $report['summary']['tracked_items']);
        self::assertSame(1, $report['rows']->total());
        self::assertSame([$healthyItem->name], $report['rows']->pluck('name')->all());

        $page->reportSearch = '';
        $page->categoryFilter = 'Mains';
        self::assertSame(
            [$healthyItem->name, $lowItem->name],
            $page->getReportProperty()['rows']->pluck('name')->all(),
        );

        $page->categoryFilter = '';
        $page->stockStatus = 'healthy';
        self::assertSame([$healthyItem->name], $page->getReportProperty()['rows']->pluck('name')->all());

        $page->stockStatus = 'low';
        self::assertSame([$lowItem->name], $page->getReportProperty()['rows']->pluck('name')->all());

        $page->stockStatus = 'negative';
        self::assertSame([$negativeItem->name], $page->getReportProperty()['rows']->pluck('name')->all());
    }

    public function test_sorting_and_pagination_return_only_the_requested_register_page(): void
    {
        $this->travelTo('2026-09-05 09:00:00');
        $category = MenuCategory::query()->create([
            'name' => 'Pagination',
            'slug' => 'pagination',
        ]);

        foreach (range(1, 12) as $number) {
            $item = $this->trackedItem(
                $category,
                sprintf('Meal %02d', $number),
                sprintf('meal-%02d', $number),
            );
            $this->production($item, (float) $number);
        }

        $page = $this->reportPage();
        $page->sortBy = 'closing_balance';
        $page->sortDirection = 'desc';
        $page->perPage = 10;
        $page->setPage(2, 'production_rows_page');
        $report = $page->getReportProperty();

        self::assertSame(12, $report['summary']['tracked_items']);
        self::assertSame(12, $report['rows']->total());
        self::assertSame(2, $report['rows']->currentPage());
        self::assertSame(['Meal 02', 'Meal 01'], $report['rows']->pluck('name')->all());
    }

    public function test_register_controls_and_period_changes_reset_the_named_paginator(): void
    {
        $component = Livewire::actingAs($this->authorizedUser())
            ->test(KitchenProductionReport::class);

        foreach ([
            'reportSearch' => 'meal',
            'categoryFilter' => 'Mains',
            'stockStatus' => 'healthy',
            'sortBy' => 'closing_balance',
            'sortDirection' => 'desc',
            'perPage' => 10,
        ] as $property => $value) {
            $component
                ->set('paginators.production_rows_page', 3)
                ->set($property, $value)
                ->assertSet('paginators.production_rows_page', 1);
        }

        $component
            ->set('paginators.production_rows_page', 3)
            ->call('resetRegisterFilters')
            ->assertSet('reportSearch', '')
            ->assertSet('categoryFilter', '')
            ->assertSet('stockStatus', '')
            ->assertSet('sortBy', 'name')
            ->assertSet('sortDirection', 'asc')
            ->assertSet('perPage', 25)
            ->assertSet('paginators.production_rows_page', 1)
            ->set('paginators.production_rows_page', 3)
            ->set('draftPeriod', 'custom')
            ->set('draftStartDate', '2026-08-01')
            ->set('draftEndDate', '2026-08-31')
            ->call('applyReportPeriod')
            ->assertHasNoErrors()
            ->assertSet('paginators.production_rows_page', 1);
    }

    public function test_report_renders_responsive_register_controls_and_result_context(): void
    {
        $this->travelTo('2026-09-05 09:00:00');
        $this->reportFixtures();

        Livewire::actingAs($this->authorizedUser())
            ->test(KitchenProductionReport::class)
            ->assertSuccessful()
            ->assertSee('Filter production register')
            ->assertSee('Search menu items')
            ->assertSee('All categories')
            ->assertSee('All closing-stock statuses')
            ->assertSee('Sort by')
            ->assertSee('Rows per page')
            ->assertSee('Showing 3 of 3 tracked items')
            ->assertSee('Register filters do not change the period overview metrics.');
    }

    public function test_report_keeps_cards_through_laptop_widths_and_defers_the_wide_table(): void
    {
        $html = Livewire::actingAs($this->authorizedUser())
            ->test(KitchenProductionReport::class)
            ->assertSuccessful()
            ->html();
        $document = new \DOMDocument;
        @$document->loadHTML($html);
        $xpath = new \DOMXPath($document);
        $cardRegister = $xpath->query('//*[@data-kitchen-production-mobile-register]')?->item(0);
        $tableRegister = $xpath->query('//*[@data-kitchen-production-desktop-register]')?->item(0);

        self::assertInstanceOf(\DOMElement::class, $cardRegister);
        self::assertInstanceOf(\DOMElement::class, $tableRegister);

        $cardClasses = preg_split('/\s+/', trim($cardRegister->getAttribute('class')));
        $tableClasses = preg_split('/\s+/', trim($tableRegister->getAttribute('class')));

        self::assertContains('grid', $cardClasses);
        self::assertContains('lg:grid-cols-2', $cardClasses);
        self::assertContains('2xl:hidden', $cardClasses);
        self::assertNotContains('md:hidden', $cardClasses);
        self::assertContains('hidden', $tableClasses);
        self::assertContains('2xl:block', $tableClasses);
        self::assertNotContains('md:block', $tableClasses);
    }

    /**
     * @return array{MenuItem, MenuItem, MenuItem}
     */
    private function reportFixtures(): array
    {
        $mains = MenuCategory::query()->create([
            'name' => 'Mains',
            'slug' => 'mains',
        ]);
        $drinks = MenuCategory::query()->create([
            'name' => 'Drinks',
            'slug' => 'drinks',
        ]);
        $healthyItem = $this->trackedItem($mains, 'Braised Beef', 'braised-beef');
        $lowItem = $this->trackedItem($mains, 'Coconut Rice', 'coconut-rice');
        $negativeItem = $this->trackedItem($drinks, 'Date Juice', 'date-juice');

        $this->production($healthyItem, 20);
        $this->production($lowItem, 4);
        $this->production($negativeItem, 2);
        $this->sale($negativeItem, 4);

        return [$healthyItem, $lowItem, $negativeItem];
    }

    private function trackedItem(MenuCategory $category, string $name, string $slug): MenuItem
    {
        return MenuItem::query()->create([
            'menu_category_id' => $category->getKey(),
            'name' => $name,
            'slug' => $slug,
            'price' => 25,
            'tracks_kitchen_production' => true,
            'production_unit' => 'portion',
            'production_usage_per_sale' => 1,
            'low_stock_threshold' => 5,
        ]);
    }

    private function production(MenuItem $item, float $quantity): void
    {
        KitchenProduction::query()->create([
            'menu_item_id' => $item->getKey(),
            'production_date' => '2026-09-03',
            'quantity_produced' => $quantity,
            'quantity_wasted' => 0,
        ]);
    }

    private function sale(MenuItem $item, int $quantity): void
    {
        $order = RestaurantOrder::query()->create([
            'order_number' => 'REPORT-REGISTER-'.str()->upper(str()->random(8)),
            'ordering_channel' => 'web',
            'subtotal' => $quantity * 25,
            'total' => $quantity * 25,
            'status' => 'served',
            'payment_status' => 'completed',
            'stock_deducted_at' => '2026-09-04 12:00:00',
        ]);
        $order->items()->create([
            'menu_item_id' => $item->getKey(),
            'item_name' => $item->name,
            'production_unit' => 'portion',
            'production_usage_per_sale' => 1,
            'quantity' => $quantity,
            'unit_price' => 25,
            'total_price' => $quantity * 25,
        ]);
    }

    private function reportPage(): KitchenProductionReport
    {
        $page = new KitchenProductionReport;
        $page->period = 'custom';
        $page->startDate = '2026-09-01';
        $page->endDate = '2026-09-30';

        return $page;
    }

    private function authorizedUser(): User
    {
        Permission::findOrCreate('view kitchen production reports', 'web');
        $user = User::factory()->create(['department' => 'kitchen']);
        $user->givePermissionTo('view kitchen production reports');
        Filament::setCurrentPanel(Filament::getPanel('admin'));

        return $user;
    }
}
