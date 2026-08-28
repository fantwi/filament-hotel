<?php

namespace App\Filament\Admin\Pages\Dashboards;

use Carbon\Carbon;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Pages\Dashboard;
use Filament\Pages\Dashboard\Concerns\HasFiltersForm;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;

/**
 * Provides the time filtered dashboard Filament administration page.
 */
abstract class TimeFilteredDashboard extends Dashboard
{
    use HasFiltersForm;

    /**
     * Configures filters form for the Filament administration interface.
     */
    public function filtersForm(Schema $schema): Schema
    {
        [$start, $end] = static::presetRange('monthly');

        return $schema->components([
            Section::make('Dashboard period')
                ->description('Choose a preset period or refine it with a custom start and end date. Every dashboard widget refreshes to show only data in this range.')
                ->schema([
                    Select::make('period')
                        ->label('Breakdown')
                        ->options([
                            'daily' => 'Daily',
                            'weekly' => 'Weekly',
                            'monthly' => 'Monthly',
                            'quarterly' => 'Quarterly',
                            'yearly' => 'Yearly',
                        ])
                        ->default('monthly')
                        ->live()
                        ->afterStateUpdated(function (?string $state, Set $set): void {
                            [$from, $until] = static::presetRange($state ?? 'monthly');

                            $set('start_date', $from->toDateString());
                            $set('end_date', $until->toDateString());
                        }),

                    DatePicker::make('start_date')
                        ->label('Start date')
                        ->default($start->toDateString())
                        ->live(),

                    DatePicker::make('end_date')
                        ->label('End date')
                        ->default($end->toDateString())
                        ->live()
                        ->minDate(fn ($get): ?string => $get('start_date')),
                ])
                ->columns(['default' => 1, 'md' => 3])
                ->columnSpanFull(),
        ]);
    }

    /**
     * @return array{0: Carbon, 1: Carbon}
     */
    public static function presetRange(string $period): array
    {
        $now = now();

        $start = match ($period) {
            'daily' => $now->copy()->startOfDay(),
            'weekly' => $now->copy()->startOfWeek(),
            'quarterly' => $now->copy()->startOfQuarter(),
            'yearly' => $now->copy()->startOfYear(),
            default => $now->copy()->startOfMonth(),
        };

        return [$start, $now->copy()->endOfDay()];
    }
}
