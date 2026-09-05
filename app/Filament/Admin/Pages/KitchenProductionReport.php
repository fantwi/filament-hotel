<?php

namespace App\Filament\Admin\Pages;

use App\Filament\Admin\Concerns\InteractsWithReportPeriod;
use App\Services\KitchenProductionReportService;
use Filament\Pages\Page;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Livewire\WithPagination;

/**
 * Provides the kitchen production report Filament administration page.
 */
class KitchenProductionReport extends Page
{
    use InteractsWithReportPeriod;
    use WithPagination;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-chart-bar-square';

    protected static string|\UnitEnum|null $navigationGroup = 'Kitchen & Inventory';

    protected static ?string $navigationLabel = 'Production vs Sales';

    protected static ?string $title = 'Kitchen Production vs Sales';

    protected static ?int $navigationSort = 40;

    protected string $view = 'filament.admin.pages.kitchen-production-report';

    private const STOCK_STATUS_OPTIONS = [
        'healthy' => 'Healthy',
        'low' => 'Low balance',
        'negative' => 'Below zero',
    ];

    private const SORT_OPTIONS = [
        'name' => 'Menu item',
        'category' => 'Category',
        'produced' => 'Quantity produced',
        'sold_units' => 'Units sold',
        'closing_balance' => 'Closing balance',
        'sell_through' => 'Available-stock sell-through',
        'net_revenue' => 'Net revenue',
    ];

    public string $reportSearch = '';

    public string $categoryFilter = '';

    public string $stockStatus = '';

    public string $sortBy = 'name';

    public string $sortDirection = 'asc';

    public int $perPage = 25;

    /**
     * Builds and returns report property.
     */
    public function getReportProperty(): array
    {
        [$start, $end] = $this->periodBounds();

        $report = app(KitchenProductionReportService::class)->build(
            $start,
            $end,
        );
        $allRows = $report['rows'];
        $filteredRows = $this->sortRows($this->filterRows($allRows));

        $report['rows'] = $this->paginateRows($filteredRows);
        $report['total_rows'] = $allRows->count();
        $report['category_options'] = $allRows
            ->pluck('category')
            ->unique()
            ->sort(SORT_NATURAL | SORT_FLAG_CASE)
            ->values()
            ->all();

        return $report;
    }

    /**
     * Returns valid closing-stock status choices for the register.
     *
     * @return array<string, string>
     */
    public function stockStatusOptions(): array
    {
        return self::STOCK_STATUS_OPTIONS;
    }

    /**
     * Returns supported report-row sort choices.
     *
     * @return array<string, string>
     */
    public function sortOptions(): array
    {
        return self::SORT_OPTIONS;
    }

    /**
     * Determines whether the register differs from its default view.
     */
    public function hasRegisterFilters(): bool
    {
        return trim($this->reportSearch) !== ''
            || $this->categoryFilter !== ''
            || $this->stockStatus !== ''
            || $this->sortBy !== 'name'
            || $this->sortDirection !== 'asc'
            || $this->perPage !== 25;
    }

    /**
     * Clears row filters and restores the default register order and page size.
     */
    public function resetRegisterFilters(): void
    {
        $this->reportSearch = '';
        $this->categoryFilter = '';
        $this->stockStatus = '';
        $this->sortBy = 'name';
        $this->sortDirection = 'asc';
        $this->perPage = 25;
        $this->resetProductionRowsPage();
    }

    /**
     * Returns to the first page after the menu-item search changes.
     */
    public function updatedReportSearch(): void
    {
        $this->resetProductionRowsPage();
    }

    /**
     * Returns to the first page after the category filter changes.
     */
    public function updatedCategoryFilter(): void
    {
        $this->resetProductionRowsPage();
    }

    /**
     * Returns to the first page after the stock-status filter changes.
     */
    public function updatedStockStatus(): void
    {
        $this->resetProductionRowsPage();
    }

    /**
     * Returns to the first page after the sort field changes.
     */
    public function updatedSortBy(): void
    {
        $this->resetProductionRowsPage();
    }

    /**
     * Returns to the first page after the sort direction changes.
     */
    public function updatedSortDirection(): void
    {
        $this->resetProductionRowsPage();
    }

    /**
     * Returns to the first page after the page size changes.
     */
    public function updatedPerPage(): void
    {
        $this->resetProductionRowsPage();
    }

    /**
     * Applies the register-only search and stock filters.
     *
     * @param  Collection<int, array<string, mixed>>  $rows
     * @return Collection<int, array<string, mixed>>
     */
    private function filterRows(Collection $rows): Collection
    {
        $search = mb_strtolower(mb_substr(trim($this->reportSearch), 0, 100));

        if ($search !== '') {
            $rows = $rows->filter(
                fn (array $row): bool => str_contains(mb_strtolower((string) $row['name']), $search),
            );
        }

        if ($this->categoryFilter !== '') {
            $rows = $rows->where('category', $this->categoryFilter);
        }

        if (array_key_exists($this->stockStatus, self::STOCK_STATUS_OPTIONS)) {
            $rows = $rows->where('stock_status', $this->stockStatus);
        }

        return $rows->values();
    }

    /**
     * Sorts report rows while keeping unavailable numeric values last.
     *
     * @param  Collection<int, array<string, mixed>>  $rows
     * @return Collection<int, array<string, mixed>>
     */
    private function sortRows(Collection $rows): Collection
    {
        $sortBy = array_key_exists($this->sortBy, self::SORT_OPTIONS)
            ? $this->sortBy
            : 'name';
        $descending = $this->sortDirection === 'desc';

        return $rows
            ->sort(function (array $left, array $right) use ($descending, $sortBy): int {
                $leftValue = $left[$sortBy] ?? null;
                $rightValue = $right[$sortBy] ?? null;

                if ($leftValue === null || $rightValue === null) {
                    if ($leftValue === $rightValue) {
                        return strnatcasecmp((string) $left['name'], (string) $right['name']);
                    }

                    return $leftValue === null ? 1 : -1;
                }

                $comparison = is_string($leftValue) && is_string($rightValue)
                    ? strnatcasecmp($leftValue, $rightValue)
                    : $leftValue <=> $rightValue;

                if ($comparison === 0) {
                    $comparison = strnatcasecmp((string) $left['name'], (string) $right['name']);
                }

                return $descending ? -$comparison : $comparison;
            })
            ->values();
    }

    /**
     * Returns only the selected report-register page to the browser.
     *
     * @param  Collection<int, array<string, mixed>>  $rows
     */
    private function paginateRows(Collection $rows): LengthAwarePaginator
    {
        $perPage = in_array($this->perPage, [10, 25, 50, 100], true)
            ? $this->perPage
            : 25;
        $pageName = 'production_rows_page';
        $currentPage = max(1, $this->getPage($pageName));

        return new LengthAwarePaginator(
            $rows->forPage($currentPage, $perPage)->values(),
            $rows->count(),
            $perPage,
            $currentPage,
            [
                'path' => request()->url(),
                'pageName' => $pageName,
            ],
        );
    }

    /**
     * Resets the production register after any row-control change.
     */
    private function resetProductionRowsPage(): void
    {
        $this->resetPage('production_rows_page');
    }

    /**
     * Identifies the report paginator reset after a period is applied.
     */
    protected function reportPaginatorName(): ?string
    {
        return 'production_rows_page';
    }

    /**
     * Determines whether the current user may access this feature.
     */
    public static function canAccess(): bool
    {
        return auth()->user()?->can('view kitchen production reports') ?? false;
    }
}
