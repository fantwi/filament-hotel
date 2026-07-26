<?php

namespace App\Filament\Admin\Pages;

use App\Services\KitchenProductionReportService;
use Filament\Pages\Page;
use Illuminate\Support\Carbon;

class KitchenProductionReport extends Page
{
    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-chart-bar-square';

    protected static string|\UnitEnum|null $navigationGroup = 'Restaurant';

    protected static ?string $navigationLabel = 'Production vs Sales';

    protected static ?string $title = 'Kitchen Production vs Sales';

    protected static ?int $navigationSort = 90;

    protected string $view = 'filament.admin.pages.kitchen-production-report';

    public string $fromDate;

    public string $untilDate;

    public function mount(): void
    {
        $this->fromDate = now()->startOfMonth()->toDateString();
        $this->untilDate = today()->toDateString();
    }

    public function applyFilters(): void
    {
        $this->validate([
            'fromDate' => ['required', 'date', 'before_or_equal:untilDate'],
            'untilDate' => ['required', 'date', 'after_or_equal:fromDate'],
        ]);
    }

    public function getReportProperty(): array
    {
        return app(KitchenProductionReportService::class)->build(
            Carbon::parse($this->fromDate),
            Carbon::parse($this->untilDate),
        );
    }

    public static function canAccess(): bool
    {
        return auth()->user()?->can('view kitchen production reports') ?? false;
    }
}
