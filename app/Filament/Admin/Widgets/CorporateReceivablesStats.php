<?php

namespace App\Filament\Admin\Widgets;

use App\Filament\Admin\Pages\CorporateReceivables;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Collection;

/**
 * Provides an at-a-glance receivables breakdown for the corporate settlement queue.
 */
class CorporateReceivablesStats extends StatsOverviewWidget
{
    protected int|string|array $columnSpan = 'full';

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
        $receivables = (new CorporateReceivables)->receivables();
        $byType = $receivables->groupBy('type');

        return [
            $this->stat($byType, 'booking', 'Room booking receivables', 'heroicon-o-home-modern'),
            $this->stat($byType, 'conference', 'Conference receivables', 'heroicon-o-building-office'),
            $this->stat($byType, 'reservation', 'Table-reservation receivables', 'heroicon-o-calendar-days'),
            $this->stat($byType, 'order', 'Food-order receivables', 'heroicon-o-shopping-bag'),
        ];
    }

    /**
     * Builds one service receivable stat from the grouped queue.
     *
     * @param  Collection<string, Collection>  $groups
     */
    private function stat(Collection $groups, string $type, string $label, string $icon): Stat
    {
        $items = $groups[$type] ?? collect();

        return Stat::make($label, 'GHS '.number_format((float) $items->sum('amount'), 2))
            ->description($items->count().' open transaction'.($items->count() === 1 ? '' : 's'))
            ->icon($icon)
            ->color($items->isEmpty() ? 'gray' : 'warning');
    }
}
