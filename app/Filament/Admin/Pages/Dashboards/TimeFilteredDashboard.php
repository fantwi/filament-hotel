<?php

namespace App\Filament\Admin\Pages\Dashboards;

use Carbon\Carbon;
use Filament\Actions\Action;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Pages\Dashboard;
use Filament\Pages\Dashboard\Concerns\HasFiltersForm;
use Filament\Schemas\Components\Actions;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;

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
     * Places KPI widgets first and keeps secondary dashboard content grouped.
     *
     * Each dashboard still owns its widget list; this layout only changes how
     * those widgets are presented so the most actionable numbers stay visible.
     */
    public function content(Schema $schema): Schema
    {
        [$priorityWidgets, $sections] = $this->dashboardWidgetLayout();
        $components = [
            ...(method_exists($this, 'getFiltersForm') ? [$this->getFiltersFormContentComponent()] : []),
        ];

        if ($priorityWidgets !== []) {
            $components[] = $this->widgetGrid($priorityWidgets);
        }

        if (count($sections) === 1) {
            $label = array_key_first($sections);
            $components[] = Section::make($label)
                ->description($this->dashboardSectionDescription($label))
                ->schema([$this->widgetGrid($sections[$label])])
                ->collapsible()
                ->collapsed($label === 'Guidance');
        } elseif ($sections !== []) {
            $tabs = [];

            foreach ($sections as $label => $widgets) {
                $tabs[] = Tab::make($label)
                    ->icon($this->dashboardSectionIcon($label))
                    ->schema([$this->widgetGrid($widgets)]);
            }

            $components[] = Tabs::make('Dashboard sections')
                ->tabs($tabs)
                ->persistTabInQueryString('dashboard-section')
                ->columnSpanFull();
        }

        return $schema->components($components);
    }

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
     * Splits a dashboard's widgets into above-the-fold KPIs and tabbed detail.
     *
     * @return array{0: array<int, mixed>, 1: array<string, array<int, mixed>>}
     */
    protected function dashboardWidgetLayout(): array
    {
        $priorityWidgets = [];
        $sections = [
            'Operations' => [],
            'Finance' => [],
            'Restaurant' => [],
            'Kitchen' => [],
            'Guidance' => [],
        ];

        foreach ($this->getWidgets() as $widget) {
            $widgetClass = $this->normalizeWidgetClass($widget);

            if ($this->isPriorityDashboardWidget($widgetClass)) {
                $priorityWidgets[] = $widget;

                continue;
            }

            $section = $this->dashboardSectionForWidget($widgetClass);

            $sections[$section][] = $widget;
        }

        return [$priorityWidgets, array_filter($sections, filled(...))];
    }

    /**
     * Assigns a secondary widget to its dashboard section.
     */
    protected function dashboardSectionForWidget(string $widgetClass): string
    {
        $name = class_basename($widgetClass);

        return match (true) {
            $name === 'RoleDashboardOverview' => 'Guidance',
            str_contains($name, 'Restaurant')
                || str_contains($name, 'Menu')
                || str_contains($name, 'BestSelling') => 'Restaurant',
            str_contains($name, 'Corporate')
                || str_contains($name, 'Payment')
                || str_contains($name, 'Receivable')
                || str_contains($name, 'Revenue')
                || str_contains($name, 'Transaction') => 'Finance',
            str_contains($name, 'Kitchen') => 'Kitchen',
            default => 'Operations',
        };
    }

    /**
     * Determines whether a widget belongs in the dashboard's executive KPI row.
     */
    protected function isPriorityDashboardWidget(string $widgetClass): bool
    {
        return str_ends_with(class_basename($widgetClass), 'Stats');
    }

    /**
     * Wraps widget components in the dashboard's responsive widget grid.
     *
     * @param  array<int, mixed>  $widgets
     */
    protected function widgetGrid(array $widgets): Grid
    {
        return Grid::make($this->getColumns())
            ->schema($this->getWidgetsSchemaComponents($widgets));
    }

    /**
     * Returns the accessible icon for a secondary dashboard section.
     */
    protected function dashboardSectionIcon(string $section): Heroicon
    {
        return match ($section) {
            'Finance' => Heroicon::OutlinedBanknotes,
            'Restaurant' => Heroicon::OutlinedCake,
            'Kitchen' => Heroicon::OutlinedFire,
            'Guidance' => Heroicon::OutlinedInformationCircle,
            default => Heroicon::OutlinedClipboardDocumentList,
        };
    }

    /**
     * Returns a concise description for a secondary dashboard section.
     */
    protected function dashboardSectionDescription(string $section): string
    {
        return match ($section) {
            'Finance' => 'Payments, revenue, and outstanding balances for the selected period.',
            'Restaurant' => 'Restaurant sales, order trends, and menu performance.',
            'Kitchen' => 'Kitchen production, stock, and order-queue activity.',
            'Guidance' => 'Role-specific priorities and reporting context.',
            default => 'Operational activity and follow-up items for the selected period.',
        };
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
