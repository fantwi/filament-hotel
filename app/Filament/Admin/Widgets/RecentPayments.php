<?php

namespace App\Filament\Admin\Widgets;

use App\Filament\Admin\Concerns\InteractsWithDashboardDateRange;
use App\Models\Payment;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;

/**
 * Provides the recent payments Filament dashboard widget.
 */
class RecentPayments extends TableWidget
{
    use InteractsWithDashboardDateRange;

    protected static ?string $heading = 'Recent Payments';

    protected int|string|array $columnSpan = 'full';

    /**
     * Determines whether the current user may view this feature.
     */
    public static function canView(): bool
    {
        return auth()->user()?->hasAnyRole(['super_admin', 'admin', 'accountant']) ?? false;
    }

    /**
     * Configures the table data source, columns, and actions.
     */
    public function table(Table $table): Table
    {
        return $table->query($this->forDashboardDateRange(Payment::query())->with('guest')->latest())->columns([
            TextColumn::make('transaction_reference')->label('Reference')->searchable()->copyable(),
            TextColumn::make('guest.email')->label('Guest')->placeholder('No guest'),
            TextColumn::make('amount')->money('GHS')->sortable(),
            TextColumn::make('method')->badge(),
            TextColumn::make('payment_status')->label('Status')->badge(),
            TextColumn::make('created_at')->label('Date')->dateTime('M d, Y g:i A')->sortable(),
        ])->defaultPaginationPageOption(10);
    }
}
