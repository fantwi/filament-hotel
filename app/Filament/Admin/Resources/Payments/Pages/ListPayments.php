<?php

namespace App\Filament\Admin\Resources\Payments\Pages;

use App\Filament\Admin\Resources\Payments\PaymentResource;
use App\Filament\Admin\Widgets\PaymentReportStats;
use App\Services\PaymentReportFilters;
use Filament\Actions\Action;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Pages\Dashboard\Concerns\HasFiltersForm;
use Filament\Resources\Pages\ListRecords;
use Filament\Schemas\Components\Actions;
use Filament\Schemas\Components\EmbeddedSchema;
use Filament\Schemas\Components\EmbeddedTable;
use Filament\Schemas\Components\RenderHook;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\View\PanelsRenderHook;

/**
 * Configures Filament administration for list payments.
 */
class ListPayments extends ListRecords
{
    use HasFiltersForm {
        mountHasFilters as protected mountBaseFilters;
    }

    protected static string $resource = PaymentResource::class;

    /**
     * Holds edits until staff deliberately apply the Payment report filters.
     *
     * @var array<string, mixed>|null
     */
    public ?array $draftFilters = null;

    /**
     * Loads persisted filters before copying them into the deferred form state.
     */
    public function mountHasFilters(): void
    {
        $this->mountBaseFilters();

        $this->filters = array_replace($this->defaultPaymentFilters(), $this->filters ?? []);
        $this->draftFilters = $this->filters;
        $this->getFiltersForm()->fill($this->draftFilters);
    }

    /**
     * Uses deferred state so field edits do not refresh the table or widgets.
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
     * Provides the shared transaction and date controls used by the table and metrics.
     */
    public function filtersForm(Schema $schema): Schema
    {
        [$start, $end] = PaymentReportFilters::dateRange(['period' => 'monthly']);

        return $schema->components([
            Section::make('Payment filters')
                ->description(fn (): string => 'Applied: '.$this->activeFilterLabel().'. Edit the controls, then apply them to update the summary and transactions together.')
                ->schema([
                    Select::make('transaction_type')
                        ->label('Transaction type')
                        ->options(PaymentReportFilters::typeOptions())
                        ->default('all')
                        ->live(condition: false),

                    Select::make('payment_status')
                        ->label('Payment status')
                        ->options(PaymentReportFilters::statusOptions())
                        ->default('all')
                        ->live(condition: false),

                    Select::make('payment_method')
                        ->label('Payment method')
                        ->options(PaymentReportFilters::methodOptions())
                        ->default('all')
                        ->live(condition: false),

                    Select::make('date_basis')
                        ->label('Date basis')
                        ->options(PaymentReportFilters::dateBasisOptions())
                        ->default('created_at')
                        ->live(condition: false),

                    Select::make('period')
                        ->label('Breakdown')
                        ->options(PaymentReportFilters::periodOptions())
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
                        Action::make('applyPaymentFilters')
                            ->label('Apply filters')
                            ->icon('heroicon-o-funnel')
                            ->color('primary')
                            ->keyBindings(['mod+enter'])
                            ->action(function (): void {
                                $this->applyPaymentFilters();
                            }),
                        Action::make('resetPaymentFilters')
                            ->label('Reset')
                            ->icon('heroicon-o-arrow-path')
                            ->color('gray')
                            ->action(function (): void {
                                $this->resetPaymentFilters();
                            }),
                    ])
                        ->fullWidth(),
                ])
                ->columns(['default' => 1, 'md' => 4])
                ->columnSpanFull(),
        ]);
    }

    /**
     * Validates and commits the draft filters before refreshing the table.
     */
    public function applyPaymentFilters(): void
    {
        $defaults = $this->defaultPaymentFilters();
        $draft = array_replace($defaults, $this->draftFilters ?? []);
        $draft['transaction_type'] = array_key_exists((string) $draft['transaction_type'], PaymentReportFilters::typeOptions())
            ? (string) $draft['transaction_type']
            : 'all';
        $draft['period'] = array_key_exists((string) $draft['period'], PaymentReportFilters::periodOptions())
            ? (string) $draft['period']
            : 'monthly';
        $draft['payment_status'] = array_key_exists((string) $draft['payment_status'], PaymentReportFilters::statusOptions())
            ? (string) $draft['payment_status']
            : 'all';
        $draft['payment_method'] = array_key_exists((string) $draft['payment_method'], PaymentReportFilters::methodOptions())
            ? (string) $draft['payment_method']
            : 'all';
        $draft['date_basis'] = array_key_exists((string) $draft['date_basis'], PaymentReportFilters::dateBasisOptions())
            ? (string) $draft['date_basis']
            : 'created_at';

        $previous = array_replace($defaults, $this->filters ?? []);
        $hasCompleteRange = filled($draft['start_date'] ?? null) && filled($draft['end_date'] ?? null);
        $rangeFollowsPrevious = $hasCompleteRange
            && ($draft['start_date'] ?? null) === ($previous['start_date'] ?? null)
            && ($draft['end_date'] ?? null) === ($previous['end_date'] ?? null);
        $rangeFollowsDefault = $hasCompleteRange
            && ($draft['start_date'] ?? null) === $defaults['start_date']
            && ($draft['end_date'] ?? null) === $defaults['end_date'];

        if (! $hasCompleteRange || $rangeFollowsPrevious || $rangeFollowsDefault) {
            [$start, $end] = PaymentReportFilters::dateRange(['period' => $draft['period']]);
            $draft['start_date'] = $start->toDateString();
            $draft['end_date'] = $end->toDateString();
        }

        $this->draftFilters = $draft;

        $validated = $this->validate([
            'draftFilters.transaction_type' => ['required', 'in:'.implode(',', array_keys(PaymentReportFilters::typeOptions()))],
            'draftFilters.period' => ['required', 'in:'.implode(',', array_keys(PaymentReportFilters::periodOptions()))],
            'draftFilters.payment_status' => ['required', 'in:'.implode(',', array_keys(PaymentReportFilters::statusOptions()))],
            'draftFilters.payment_method' => ['required', 'in:'.implode(',', array_keys(PaymentReportFilters::methodOptions()))],
            'draftFilters.date_basis' => ['required', 'in:'.implode(',', array_keys(PaymentReportFilters::dateBasisOptions()))],
            'draftFilters.start_date' => ['required', 'date', 'before_or_equal:draftFilters.end_date'],
            'draftFilters.end_date' => ['required', 'date', 'after_or_equal:draftFilters.start_date'],
        ]);

        $this->filters = $validated['draftFilters'];
        $this->getFiltersForm()->fill($this->filters);
        $this->updatedFilters();
        $this->resetValidation();
        $this->resetTable();
    }

    /**
     * Restores the default monthly scope and refreshes all Payment results.
     */
    public function resetPaymentFilters(): void
    {
        $defaults = $this->defaultPaymentFilters();

        $this->draftFilters = $defaults;
        $this->filters = $defaults;
        $this->getFiltersForm()->fill($defaults);
        $this->updatedFilters();
        $this->resetValidation();
        $this->resetTable();
    }

    /**
     * Summarizes the committed scope shown by the metrics and transactions.
     */
    public function activeFilterLabel(): string
    {
        $filters = array_replace($this->defaultPaymentFilters(), $this->filters ?? []);
        [$start, $end] = PaymentReportFilters::dateRange($filters);

        return sprintf(
            '%s · %s · %s · %s · %s to %s',
            PaymentReportFilters::typeLabel((string) $filters['transaction_type']),
            PaymentReportFilters::statusLabel((string) $filters['payment_status']),
            PaymentReportFilters::methodLabel((string) $filters['payment_method']),
            PaymentReportFilters::dateBasisLabel((string) $filters['date_basis']),
            $start->format('M j, Y'),
            $end->format('M j, Y'),
        );
    }

    /**
     * @return array{transaction_type: string, payment_status: string, payment_method: string, date_basis: string, period: string, start_date: string, end_date: string}
     */
    private function defaultPaymentFilters(): array
    {
        [$start, $end] = PaymentReportFilters::dateRange(['period' => 'monthly']);

        return [
            'transaction_type' => 'all',
            'payment_status' => 'all',
            'payment_method' => 'all',
            'date_basis' => 'created_at',
            'period' => 'monthly',
            'start_date' => $start->toDateString(),
            'end_date' => $end->toDateString(),
        ];
    }

    /**
     * Places the date-aware payment summary above the detailed payment table.
     *
     * @return array<class-string>
     */
    protected function getHeaderWidgets(): array
    {
        return [PaymentReportStats::class];
    }

    public function getHeaderWidgetsColumns(): int|array
    {
        return 1;
    }

    /**
     * Renders the filter form above the existing resource table.
     */
    public function content(Schema $schema): Schema
    {
        return $schema->components([
            EmbeddedSchema::make('filtersForm'),
            $this->getTabsContentComponent(),
            RenderHook::make(PanelsRenderHook::RESOURCE_PAGES_LIST_RECORDS_TABLE_BEFORE),
            EmbeddedTable::make(),
            RenderHook::make(PanelsRenderHook::RESOURCE_PAGES_LIST_RECORDS_TABLE_AFTER),
        ]);
    }

    /**
     * Builds and returns header actions.
     */
    protected function getHeaderActions(): array
    {
        return [
            // CreateAction::make(),
        ];
    }
}
