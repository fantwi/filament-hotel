<?php

namespace Tests\Feature;

use App\Filament\Admin\Pages\KitchenProductionReport;
use App\Filament\Admin\Resources\MenuItems\MenuItemResource;
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
use Spatie\Permission\Models\Role;
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
            'exceptionFilter' => 'wastage',
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
            ->assertSet('exceptionFilter', '')
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
            ->assertSee('Exceptions')
            ->assertSee('Stock needs attention')
            ->assertSee('Sort by')
            ->assertSee('Rows per page')
            ->assertSee('Showing 3 of 3 tracked items')
            ->assertSee('Register filters do not change the period overview metrics.')
            ->assertSee('Threshold: 5.000 portion');
    }

    public function test_report_keeps_cards_through_laptop_widths_and_defers_the_wide_table(): void
    {
        $this->travelTo('2026-09-05 09:00:00');
        $this->reportFixtures();

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

    public function test_desktop_register_prioritizes_stock_status_and_groups_related_metrics(): void
    {
        $this->travelTo('2026-09-05 09:00:00');
        $this->reportFixtures();

        $html = Livewire::actingAs($this->authorizedUser())
            ->test(KitchenProductionReport::class)
            ->assertSuccessful()
            ->html();
        $document = new \DOMDocument;
        @$document->loadHTML($html);
        $xpath = new \DOMXPath($document);
        $table = $xpath->query('//*[@data-kitchen-production-desktop-register]//table')?->item(0);

        self::assertInstanceOf(\DOMElement::class, $table);

        $groupHeaders = $xpath->query('.//thead/tr[1]/th[@scope="colgroup"]', $table);
        self::assertCount(5, $groupHeaders);
        self::assertSame(
            ['Item & stock', 'Production', 'Sales', 'Inventory balance', 'Performance'],
            array_map(
                static fn (\DOMNode $header): string => trim($header->textContent),
                iterator_to_array($groupHeaders),
            ),
        );
        self::assertSame(
            ['2', '3', '2', '3', '2'],
            array_map(
                static fn (\DOMElement $header): string => $header->getAttribute('colspan'),
                iterator_to_array($groupHeaders),
            ),
        );

        $columnHeaders = $xpath->query('.//thead/tr[2]/th[@scope="col"]', $table);
        self::assertCount(12, $columnHeaders);
        self::assertSame('Menu item', trim($columnHeaders->item(0)->textContent));
        self::assertSame('Closing stock', trim($columnHeaders->item(1)->textContent));
        self::assertStringContainsString('sticky', $columnHeaders->item(0)->getAttribute('class'));
        self::assertStringContainsString('left-0', $columnHeaders->item(0)->getAttribute('class'));
        self::assertStringContainsString('sticky', $columnHeaders->item(1)->getAttribute('class'));
        self::assertStringContainsString('left-64', $columnHeaders->item(1)->getAttribute('class'));

        $firstDataRow = $xpath->query('.//tbody/tr[1]', $table)?->item(0);
        self::assertInstanceOf(\DOMElement::class, $firstDataRow);
        $leadingCells = $xpath->query('./th | ./td', $firstDataRow);
        self::assertStringContainsString('sticky', $leadingCells->item(0)->getAttribute('class'));
        self::assertStringContainsString('left-0', $leadingCells->item(0)->getAttribute('class'));
        self::assertStringContainsString('sticky', $leadingCells->item(1)->getAttribute('class'));
        self::assertStringContainsString('left-64', $leadingCells->item(1)->getAttribute('class'));

        foreach (range(2, 11) as $columnIndex) {
            self::assertStringContainsString('text-right', $columnHeaders->item($columnIndex)->getAttribute('class'));
            self::assertStringContainsString('tabular-nums', $leadingCells->item($columnIndex)->getAttribute('class'));
        }
    }

    public function test_report_exposes_an_accessible_loading_state_for_all_result_refreshes(): void
    {
        $html = Livewire::actingAs($this->authorizedUser())
            ->test(KitchenProductionReport::class)
            ->assertSuccessful()
            ->html();
        $document = new \DOMDocument;
        @$document->loadHTML($html);
        $xpath = new \DOMXPath($document);
        $loadingTargets = 'applyReportPeriod,resetReportPeriod,reportSearch,categoryFilter,stockStatus,exceptionFilter,sortBy,sortDirection,perPage,resetRegisterFilters,gotoPage,previousPage,nextPage';
        $reportRegion = $xpath->query('//section[@aria-label="Kitchen production report results"]')?->item(0);

        self::assertInstanceOf(\DOMElement::class, $reportRegion);
        self::assertSame('aria-busy', $reportRegion->getAttribute('wire:loading.attr'));
        self::assertSame($loadingTargets, $reportRegion->getAttribute('wire:target'));

        $results = $xpath->query('./div[@data-kitchen-production-report-results]', $reportRegion)?->item(0);
        self::assertInstanceOf(\DOMElement::class, $results);
        self::assertSame($loadingTargets, $results->getAttribute('wire:target'));
        self::assertStringContainsString('pointer-events-none', $results->getAttribute('wire:loading.class'));
        self::assertStringContainsString('opacity-60', $results->getAttribute('wire:loading.class'));

        $status = $xpath->query('./div[@data-kitchen-production-report-loading-overlay and @role="status"]', $reportRegion)?->item(0);
        self::assertInstanceOf(\DOMElement::class, $status);
        self::assertSame('polite', $status->getAttribute('aria-live'));
        self::assertSame('true', $status->getAttribute('aria-atomic'));
        self::assertSame($loadingTargets, $status->getAttribute('wire:target'));
        self::assertTrue($status->hasAttribute('wire:loading.flex'));
        self::assertStringContainsString('Updating kitchen production report', $status->textContent);

        $periodForm = $xpath->query('//form[@*[name()="wire:submit"]="applyReportPeriod"]')?->item(0);
        self::assertInstanceOf(\DOMElement::class, $periodForm);
        $periodFields = $xpath->query(
            './/select[@*[name()="wire:loading.attr"]="disabled"] | .//input[@*[name()="wire:loading.attr"]="disabled"]',
            $periodForm,
        );
        self::assertCount(3, $periodFields);

        foreach ($periodFields as $field) {
            self::assertSame('applyReportPeriod,resetReportPeriod', $field->getAttribute('wire:target'));
        }
    }

    public function test_desktop_report_table_scroll_region_is_keyboard_accessible(): void
    {
        $this->travelTo('2026-09-05 09:00:00');
        $this->reportFixtures();

        $html = Livewire::actingAs($this->authorizedUser())
            ->test(KitchenProductionReport::class)
            ->assertSuccessful()
            ->html();
        $document = new \DOMDocument;
        @$document->loadHTML($html);
        $xpath = new \DOMXPath($document);
        $scrollRegion = $xpath->query('//*[@data-kitchen-production-table-scroll]')?->item(0);

        self::assertInstanceOf(\DOMElement::class, $scrollRegion);
        self::assertSame('region', $scrollRegion->getAttribute('role'));
        self::assertSame('0', $scrollRegion->getAttribute('tabindex'));
        self::assertSame('Production report table; scroll horizontally to review all metrics.', $scrollRegion->getAttribute('aria-label'));
        self::assertStringContainsString('focus-visible:ring-2', $scrollRegion->getAttribute('class'));
    }

    public function test_report_values_define_readable_dark_theme_contrast(): void
    {
        $this->travelTo('2026-09-05 09:00:00');
        $this->reportFixtures();

        $html = Livewire::actingAs($this->authorizedUser())
            ->test(KitchenProductionReport::class)
            ->assertSuccessful()
            ->html();
        $document = new \DOMDocument;
        @$document->loadHTML($html);
        $xpath = new \DOMXPath($document);
        $mobileValues = $xpath->query('//*[@data-kitchen-production-mobile-register]//article[1]/dl')?->item(0);
        $desktopValues = $xpath->query('//*[@data-kitchen-production-desktop-register]//tbody')?->item(0);

        self::assertInstanceOf(\DOMElement::class, $mobileValues);
        self::assertInstanceOf(\DOMElement::class, $desktopValues);
        self::assertStringContainsString('text-gray-700', $mobileValues->getAttribute('class'));
        self::assertStringContainsString('dark:text-gray-200', $mobileValues->getAttribute('class'));
        self::assertStringContainsString('text-gray-700', $desktopValues->getAttribute('class'));
        self::assertStringContainsString('dark:text-gray-200', $desktopValues->getAttribute('class'));
    }

    public function test_unconfigured_report_guides_content_managers_to_menu_items(): void
    {
        Permission::findOrCreate('view kitchen production reports', 'web');
        Role::findOrCreate('manager', 'web');
        $manager = User::factory()->create(['department' => 'management']);
        $manager->assignRole('manager');
        $manager->givePermissionTo('view kitchen production reports');
        Filament::setCurrentPanel(Filament::getPanel('admin'));

        $html = Livewire::actingAs($manager)
            ->test(KitchenProductionReport::class)
            ->assertSuccessful()
            ->html();
        $document = new \DOMDocument;
        @$document->loadHTML($html);
        $xpath = new \DOMXPath($document);
        $emptyState = $xpath->query('//*[@data-kitchen-production-unconfigured-state]')?->item(0);

        self::assertInstanceOf(\DOMElement::class, $emptyState);
        self::assertStringContainsString('Production tracking is not configured', $emptyState->textContent);
        self::assertSame(
            MenuItemResource::getUrl(),
            $xpath->query('.//a[normalize-space()="Manage menu items"]', $emptyState)?->item(0)?->getAttribute('href'),
        );
        self::assertSame(0, $xpath->query('//*[@aria-label="Kitchen production overview"]')?->count());
        self::assertSame(0, $xpath->query('//*[@data-kitchen-production-register-filters]')?->count());
    }

    public function test_inactive_period_keeps_summary_and_replaces_the_zero_register_with_guidance(): void
    {
        $category = MenuCategory::query()->create([
            'name' => 'Inactive period',
            'slug' => 'inactive-period',
        ]);
        $this->trackedItem($category, 'Tracked without activity', 'tracked-without-activity');

        $html = Livewire::actingAs($this->authorizedUser())
            ->test(KitchenProductionReport::class)
            ->assertSuccessful()
            ->html();
        $document = new \DOMDocument;
        @$document->loadHTML($html);
        $xpath = new \DOMXPath($document);
        $emptyState = $xpath->query('//*[@data-kitchen-production-inactive-state]')?->item(0);

        self::assertInstanceOf(\DOMElement::class, $emptyState);
        self::assertStringContainsString('No production or sales activity in this period', $emptyState->textContent);
        self::assertSame(
            '#kitchen-production-report-period-controls',
            $xpath->query('.//a[normalize-space()="Change reporting period"]', $emptyState)?->item(0)?->getAttribute('href'),
        );
        self::assertSame(1, $xpath->query('//*[@aria-label="Kitchen production overview"]')?->count());
        self::assertSame(0, $xpath->query('//*[@data-kitchen-production-register-filters]')?->count());
        self::assertSame(0, $xpath->query('//*[@data-kitchen-production-mobile-register]')?->count());
        self::assertSame(0, $xpath->query('//*[@data-kitchen-production-desktop-register]')?->count());
    }

    public function test_active_period_preserves_the_filter_specific_empty_state(): void
    {
        $this->travelTo('2026-09-05 09:00:00');
        $this->reportFixtures();

        Livewire::actingAs($this->authorizedUser())
            ->test(KitchenProductionReport::class)
            ->set('reportSearch', 'not a tracked menu item')
            ->assertSee('No menu items match the current register filters.')
            ->assertDontSee('No production or sales activity in this period')
            ->assertDontSee('Production tracking is not configured');
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
