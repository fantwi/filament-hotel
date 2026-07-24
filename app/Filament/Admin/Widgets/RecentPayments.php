<?php

namespace App\Filament\Admin\Widgets;

use App\Models\Payment;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;

class RecentPayments extends TableWidget
{
    protected static ?string $heading = 'Recent Payments';
    protected int|string|array $columnSpan = 'full';
    public static function canView(): bool { return auth()->user()?->hasAnyRole(['super_admin', 'admin', 'accountant']) ?? false; }
    public function table(Table $table): Table
    {
        return $table->query(Payment::query()->with('guest')->latest())->columns([
            TextColumn::make('transaction_reference')->label('Reference')->searchable()->copyable(),
            TextColumn::make('guest.email')->label('Guest')->placeholder('No guest'),
            TextColumn::make('amount')->money('GHS')->sortable(),
            TextColumn::make('method')->badge(),
            TextColumn::make('payment_status')->label('Status')->badge(),
            TextColumn::make('created_at')->label('Date')->dateTime('M d, Y g:i A')->sortable(),
        ])->defaultPaginationPageOption(10);
    }
}
