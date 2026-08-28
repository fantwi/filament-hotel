<?php

namespace App\Filament\Admin\Resources\Payments\Pages;

use App\Filament\Admin\Resources\Payments\PaymentResource;
use App\Filament\Admin\Widgets\PaymentReportStats;
use App\Services\PaymentReportFilters;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Pages\Dashboard\Concerns\HasFiltersForm;
use Filament\Resources\Pages\ListRecords;
use Filament\Schemas\Components\EmbeddedSchema;
use Filament\Schemas\Components\EmbeddedTable;
use Filament\Schemas\Components\RenderHook;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Filament\View\PanelsRenderHook;

/**
 * Configures Filament administration for list payments.
 */
class ListPayments extends ListRecords
{
    use HasFiltersForm;

    protected static string $resource = PaymentResource::class;

    /**
     * Provides the shared transaction and date controls used by the table and metrics.
     */
    public function filtersForm(Schema $schema): Schema
    {
        [$start, $end] = PaymentReportFilters::dateRange(['period' => 'monthly']);

        return $schema->components([
            Section::make('Payment filters')
                ->description('Choose a transaction type and reporting period. The table and summary cards update together.')
                ->schema([
                    Select::make('transaction_type')
                        ->label('Transaction type')
                        ->options(PaymentReportFilters::typeOptions())
                        ->default('all')
                        ->live(),

                    Select::make('period')
                        ->label('Breakdown')
                        ->options(PaymentReportFilters::periodOptions())
                        ->default('monthly')
                        ->live()
                        ->afterStateUpdated(function (?string $state, Set $set): void {
                            [$from, $until] = PaymentReportFilters::dateRange([
                                'period' => $state ?? 'monthly',
                            ]);

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
                ->columns(['default' => 1, 'md' => 4])
                ->columnSpanFull(),
        ]);
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
