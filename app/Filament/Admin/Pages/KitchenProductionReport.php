<?php

namespace App\Filament\Admin\Pages;

use App\Filament\Admin\Concerns\InteractsWithReportPeriod;
use App\Services\KitchenProductionReportService;
use Filament\Pages\Page;

/**
 * Provides the kitchen production report Filament administration page.
 */
class KitchenProductionReport extends Page
{
    use InteractsWithReportPeriod;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-chart-bar-square';

    protected static string|\UnitEnum|null $navigationGroup = 'Kitchen & Inventory';

    protected static ?string $navigationLabel = 'Production vs Sales';

    protected static ?string $title = 'Kitchen Production vs Sales';

    protected static ?int $navigationSort = 40;

    protected string $view = 'filament.admin.pages.kitchen-production-report';

    /**
     * Builds and returns report property.
     */
    public function getReportProperty(): array
    {
        [$start, $end] = $this->periodBounds();

        return app(KitchenProductionReportService::class)->build(
            $start,
            $end,
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
