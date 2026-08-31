<?php

namespace App\Filament\Admin\Widgets;

use App\Filament\Admin\Pages\CorporateReceivables;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

/**
 * Provides an at-a-glance receivables breakdown for the corporate settlement queue.
 */
class CorporateReceivablesStats extends StatsOverviewWidget
{
    protected int|string|array $columnSpan = 'full';

    /**
     * Keeps the stats row aligned with the queue filters on its host page.
     *
     * @var array<string, mixed>
     */
    public array $filters = [];

    /**
     * Determines whether the current user may view this feature.
     */
    public static function canView(): bool
    {
        return auth()->user()?->hasAnyRole(['super_admin', 'admin', 'accountant']) ?? false;
    }

    /**
     * Builds stats for each receivable service.
     *
     * @return array<int, Stat>
     */
    protected function getStats(): array
    {
        $page = new CorporateReceivables;
        $page->transactionType = (string) ($this->filters['transaction_type'] ?? 'all');
        $page->search = (string) ($this->filters['search'] ?? '');
        $page->organizationId = (string) ($this->filters['organization_id'] ?? '');
        $page->fromDate = (string) ($this->filters['from_date'] ?? '');
        $page->untilDate = (string) ($this->filters['until_date'] ?? '');
        $summary = $page->summary();

        return [
            $this->stat($summary, 'booking', 'Room booking receivables', 'heroicon-o-home-modern'),
            $this->stat($summary, 'conference', 'Conference receivables', 'heroicon-o-building-office'),
            $this->stat($summary, 'reservation', 'Table-reservation receivables', 'heroicon-o-calendar-days'),
            $this->stat($summary, 'order', 'Food-order receivables', 'heroicon-o-shopping-bag'),
        ];
    }

    /**
     * Builds one service receivable stat from the grouped queue.
     *
     * @param  array<string, mixed>  $summary
     */
    private function stat(array $summary, string $type, string $label, string $icon): Stat
    {
        $count = (int) ($summary['by_type'][$type] ?? 0);
        $amount = (float) ($summary['by_type_amount'][$type] ?? 0);

        return Stat::make($label, 'GHS '.number_format($amount, 2))
            ->description($count.' open transaction'.($count === 1 ? '' : 's'))
            ->icon($icon)
            ->color($count === 0 ? 'gray' : 'warning');
    }
}
