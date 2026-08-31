<?php

namespace App\Filament\Admin\Pages;

use App\Services\KitchenProductionReportService;
use Filament\Pages\Page;
use Illuminate\Support\Carbon;

/**
 * Provides the kitchen production report Filament administration page.
 */
class KitchenProductionReport extends Page
{
    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-chart-bar-square';

    protected static string|\UnitEnum|null $navigationGroup = 'Kitchen & Inventory';

    protected static ?string $navigationLabel = 'Production vs Sales';

    protected static ?string $title = 'Kitchen Production vs Sales';

    protected static ?int $navigationSort = 40;

    protected string $view = 'filament.admin.pages.kitchen-production-report';

    public string $fromDate;

    public string $untilDate;

    /**
     * Initializes component state before it is rendered.
     */
    public function mount(): void
    {
        $this->fromDate = now()->startOfMonth()->toDateString();
        $this->untilDate = today()->toDateString();
    }

    /**
     * Applies  filters.
     */
    public function applyFilters(): void
    {
        $this->validate([
            'fromDate' => ['required', 'date', 'before_or_equal:untilDate'],
            'untilDate' => ['required', 'date', 'after_or_equal:fromDate'],
        ]);
    }

    /**
     * Restores the report to the current month's range.
     */
    public function resetFilters(): void
    {
        $this->fromDate = now()->startOfMonth()->toDateString();
        $this->untilDate = today()->toDateString();
        $this->resetValidation();
    }

    /**
     * Builds and returns report property.
     */
    public function getReportProperty(): array
    {
        return app(KitchenProductionReportService::class)->build(
            Carbon::parse($this->fromDate),
            Carbon::parse($this->untilDate),
        );
    }

    /**
     * Determines whether the current user may access this feature.
     */
    public static function canAccess(): bool
    {
        return auth()->user()?->can('view kitchen production reports') ?? false;
    }
}
