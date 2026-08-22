<?php

namespace App\Filament\Admin\Widgets;

use App\Filament\Admin\Concerns\InteractsWithDashboardDateRange;
use App\Services\CorporateCreditService;
use Filament\Widgets\Widget;

/**
 * Provides the corporate billing overview Filament dashboard widget.
 */
class CorporateBillingOverview extends Widget
{
    use InteractsWithDashboardDateRange;

    protected string $view = 'filament.admin.widgets.corporate-billing-overview';

    protected static bool $isLazy = false;

    protected int|string|array $columnSpan = 'full';

    /**
     * Determines whether the current user may view this feature.
     */
    public static function canView(): bool
    {
        return auth()->user()?->hasAnyRole(['super_admin', 'admin', 'manager', 'accountant']) ?? false;
    }

    /**
     * Builds and returns view data.
     */
    protected function getViewData(): array
    {
        [$start, $end] = $this->dashboardDateRange();

        return [
            'overview' => app(CorporateCreditService::class)->dashboardOverview($start, $end),
            'periodLabel' => $this->dashboardDateRangeLabel(),
        ];
    }
}
