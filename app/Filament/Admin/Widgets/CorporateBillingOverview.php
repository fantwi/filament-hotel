<?php

namespace App\Filament\Admin\Widgets;

use App\Services\CorporateCreditService;
use Filament\Widgets\Widget;

class CorporateBillingOverview extends Widget
{
    protected string $view = 'filament.admin.widgets.corporate-billing-overview';

    protected int|string|array $columnSpan = 'full';

    public static function canView(): bool
    {
        return auth()->user()?->hasAnyRole(['super_admin', 'admin', 'manager', 'accountant']) ?? false;
    }

    protected function getViewData(): array
    {
        return [
            'overview' => app(CorporateCreditService::class)->dashboardOverview(),
        ];
    }
}
