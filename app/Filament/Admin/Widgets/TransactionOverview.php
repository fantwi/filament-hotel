<?php

namespace App\Filament\Admin\Widgets;

use App\Filament\Admin\Concerns\InteractsWithDashboardDateRange;
use App\Services\TransactionDashboardSummary;
use Filament\Widgets\Widget;

/**
 * Provides the transaction overview Filament dashboard widget.
 */
class TransactionOverview extends Widget
{
    use InteractsWithDashboardDateRange;

    protected string $view = 'filament.admin.widgets.transaction-overview';

    protected int|string|array $columnSpan = 'full';

    /**
     * Determines whether the current user may view this feature.
     */
    public static function canView(): bool
    {
        return auth()->user()?->can('view transaction dashboard') ?? false;
    }

    /**
     * Builds and returns view data.
     */
    protected function getViewData(): array
    {
        [$start, $end] = $this->dashboardDateRange();
        $summary = app(TransactionDashboardSummary::class)->summarize($start, $end);

        return [
            'rows' => $summary['rows'],
            'periodLabel' => $this->dashboardDateRangeLabel(),
            'totals' => $summary['totals'],
        ];
    }
}
