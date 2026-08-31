<?php

namespace App\Filament\Admin\Pages\Dashboards;

use Carbon\Carbon;
use Filament\Actions\Action;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Pages\Dashboard;
use Filament\Pages\Dashboard\Concerns\HasFiltersForm;
use Filament\Schemas\Components\Actions;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

/**
 * Provides the time filtered dashboard Filament administration page.
 */
abstract class TimeFilteredDashboard extends Dashboard
{
    use HasFiltersForm;

    /**
     * Holds filter edits until the user explicitly applies them.
     *
     * The committed values remain in the inherited `$filters` property so
     * widgets do not refresh for every keystroke in a custom date range.
     */
    public ?array $draftFilters = null;

    /**
     * Builds the dashboard filter form without Filament's default live
     * synchronization. The Apply and Reset actions commit the draft state.
     */
    public function getFiltersForm(): Schema
    {
        if ((! $this->isCachingSchemas) && $this->hasCachedSchema('filtersForm')) {
            return $this->getSchema('filtersForm');
        }

        $schema = $this->makeSchema()
            ->columns([
                'md' => 2,
                'xl' => 3,
                '2xl' => 4,
            ])
            ->extraAttributes(['wire:partial' => 'table-filters-form'])
            ->statePath('draftFilters');

        return $this->filtersForm($schema);
    }

    /**
     * Configures filters form for the Filament administration interface.
     */
    public function filtersForm(Schema $schema): Schema
    {
        [$start, $end] = static::presetRange('monthly');

        return $schema->components([
            Section::make('Dashboard period')
                ->description('Choose a preset period or refine it with a custom start and end date, then apply the range. Widgets refresh only after the range passes validation.')
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
                        ->live(condition: false),

                    DatePicker::make('start_date')
                        ->label('Start date')
                        ->default($start->toDateString())
                        ->live(condition: false),

                    DatePicker::make('end_date')
                        ->label('End date')
                        ->default($end->toDateString())
                        ->live(condition: false)
                        ->minDate(fn ($get): ?string => $get('start_date')),

                    Actions::make([
                        Action::make('applyFilters')
                            ->label('Apply filters')
                            ->icon('heroicon-o-funnel')
                            ->color('primary')
                            ->action(function (): void {
                                $this->applyDashboardFilters();
                            }),
                        Action::make('resetFilters')
                            ->label('Reset')
                            ->icon('heroicon-o-arrow-path')
                            ->color('gray')
                            ->action(function (): void {
                                $this->resetDashboardFilters();
                            }),
                    ])
                        ->fullWidth(),
                ])
                ->columns(['default' => 1, 'md' => 3])
                ->columnSpanFull(),
        ]);
    }

    /**
     * Validates and commits the draft dashboard filter range.
     */
    public function applyDashboardFilters(): void
    {
        $draft = $this->getFiltersForm()->getState();
        $draft['period'] = $this->normalisePeriod($draft['period'] ?? 'monthly');

        $previous = $this->filters ?? [];
        $hasCompleteRange = filled($draft['start_date'] ?? null) && filled($draft['end_date'] ?? null);
        $rangeFollowsPrevious = $hasCompleteRange
            && ($draft['start_date'] ?? null) === ($previous['start_date'] ?? null)
            && ($draft['end_date'] ?? null) === ($previous['end_date'] ?? null);
        [$defaultStart, $defaultEnd] = static::presetRange('monthly');
        $rangeFollowsDefault = $hasCompleteRange
            && ($draft['start_date'] ?? null) === $defaultStart->toDateString()
            && ($draft['end_date'] ?? null) === $defaultEnd->toDateString();

        // A changed preset should update its dates when the user has not
        // customised the currently displayed range.
        if (! $hasCompleteRange || $rangeFollowsPrevious || $rangeFollowsDefault) {
            [$start, $end] = static::presetRange($draft['period']);
            $draft['start_date'] = $start->toDateString();
            $draft['end_date'] = $end->toDateString();
        }

        $this->draftFilters = $draft;

        $validated = $this->validate([
            'draftFilters.period' => ['required', 'in:daily,weekly,monthly,quarterly,yearly'],
            'draftFilters.start_date' => ['required', 'date', 'before_or_equal:draftFilters.end_date'],
            'draftFilters.end_date' => ['required', 'date', 'after_or_equal:draftFilters.start_date'],
        ]);

        $this->filters = $validated['draftFilters'];
        $this->getFiltersForm()->fill($this->filters);
        $this->resetValidation();
    }

    /**
     * Restores the monthly range and commits it immediately.
     */
    public function resetDashboardFilters(): void
    {
        [$start, $end] = static::presetRange('monthly');
        $defaults = [
            'period' => 'monthly',
            'start_date' => $start->toDateString(),
            'end_date' => $end->toDateString(),
        ];

        $this->draftFilters = $defaults;
        $this->filters = $defaults;
        $this->getFiltersForm()->fill($defaults);
        $this->resetValidation();
    }

    /**
     * Restricts dashboard filters to the supported period presets.
     */
    private function normalisePeriod(mixed $period): string
    {
        return in_array($period, ['daily', 'weekly', 'monthly', 'quarterly', 'yearly'], true)
            ? $period
            : 'monthly';
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
