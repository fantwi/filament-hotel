<?php

namespace App\Filament\Admin\Widgets;

use App\Filament\Admin\Concerns\InteractsWithDashboardDateRange;
use App\Services\CorporateCreditService;
use Filament\Widgets\Widget;

class CorporateBillingOverview extends Widget
{
    use InteractsWithDashboardDateRange;

    protected string $view = 'filament.admin.widgets.corporate-billing-overview';

    protected int|string|array $columnSpan = 'full';

    public static function canView(): bool
    {
        return auth()->user()?->hasAnyRole(['super_admin', 'admin', 'manager', 'accountant']) ?? false;
    }

    protected function getViewData(): array
    {
        [$start, $end] = $this->dashboardDateRange();

        return [
            'overview' => app(CorporateCreditService::class)->dashboardOverview($start, $end),
            'periodLabel' => $this->dashboardDateRangeLabel(),
        ];
    }
}
