<?php

namespace App\Filament\Admin\Widgets;

use App\Models\Payment;
use Carbon\Carbon;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class AccountantPeriodReport extends StatsOverviewWidget
{
    protected int|string|array $columnSpan = 'full';

    public static function canView(): bool
    {
        return auth()->user()?->can('view accountant dashboard') ?? false;
    }

    protected function getStats(): array
    {
        return [
            $this->revenueStat('Daily Revenue', today()->startOfDay(), 'Today', 'primary'),
            $this->revenueStat('Weekly Revenue', today()->startOfWeek(), 'Since Monday', 'info'),
            $this->revenueStat('Monthly Revenue', today()->startOfMonth(), now()->format('F Y'), 'success'),
            $this->revenueStat('Quarterly Revenue', today()->startOfQuarter(), 'Current quarter', 'warning'),
            $this->revenueStat('Annual Revenue', today()->startOfYear(), 'Year to date', 'gray'),
        ];
    }

    private function revenueStat(string $label, Carbon $from, string $description, string $color): Stat
    {
        $revenue = Payment::query()
            ->whereIn('payment_status', ['paid', 'completed'])
            ->whereBetween('created_at', [$from, now()])
            ->sum('amount');

        return Stat::make($label, 'GHS ' . number_format($revenue, 2))
            ->description($description)
            ->icon('heroicon-o-banknotes')
            ->color($color);
    }
}
