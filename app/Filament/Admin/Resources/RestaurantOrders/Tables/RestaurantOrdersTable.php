<?php

namespace App\Filament\Admin\Resources\RestaurantOrders\Tables;

use App\Models\RestaurantOrder;
use App\Services\RestaurantKitchenService;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\Summarizers\Average;
use Filament\Tables\Columns\Summarizers\Sum;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class RestaurantOrdersTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->poll('10s')
            ->columns([
                TextColumn::make('order_number')->label('Order')->searchable()->sortable(),
                TextColumn::make('guest.email')->label('Guest')->placeholder('Walk-in Guest')->searchable(),
                TextColumn::make('table.table_number')->label('Table')->placeholder('No Table')->badge()->searchable()->sortable(),
                TextColumn::make('ordering_channel')->label('Order Channel')->badge()
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'qr' => 'Table QR', 'web' => 'Website', 'staff' => 'Staff Entry', default => ucfirst($state),
                    }),
                TextColumn::make('items_count')->label('Items')->counts('items')->badge(),
                TextColumn::make('total')
                    ->money('GHS')
                    ->sortable()
                    ->summarize([
                        Sum::make()->label('Total Revenue')->money('GHS'),
                        Average::make()->label('Average Order')->money('GHS'),
                    ]),
                TextColumn::make('status')->badge()->color(fn (string $state): string => match ($state) {
                    'pending' => 'warning', 'confirmed' => 'info', 'preparing' => 'warning',
                    'ready' => 'success', 'served' => 'gray', 'cancelled' => 'danger', default => 'gray',
                }),
                TextColumn::make('payment_status')->label('Payment')->badge()->color(fn (string $state): string => match ($state) {
                    'completed' => 'success', 'pending' => 'warning', 'failed' => 'danger',
                    'refunded' => 'gray', default => 'gray',
                }),
                TextColumn::make('preparing_at')->label('Started')->dateTime('M d, Y g:i A')->placeholder('Not started')->toggleable(),
                TextColumn::make('ready_at')->label('Ready At')->dateTime('M d, Y g:i A')->placeholder('Not ready')->toggleable(),
                TextColumn::make('preparedBy.name')->label('Prepared By')->placeholder('Unassigned')->toggleable(),
                TextColumn::make('created_at')->label('Ordered')->since()->sortable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                SelectFilter::make('status')->options([
                    'pending' => 'Pending',
                    'confirmed' => 'Confirmed',
                    'preparing' => 'Preparing',
                    'ready' => 'Ready',
                    'served' => 'Served',
                    'cancelled' => 'Cancelled',
                ]),
                SelectFilter::make('payment_status')
                    ->label('Payment Status')
                    ->options([
                        'pending' => 'Pending',
                        'completed' => 'Completed',
                        'failed' => 'Failed',
                        'refunded' => 'Refunded',
                    ]),
                SelectFilter::make('prepared_by')
                    ->label('Prepared By')
                    ->relationship('preparedBy', 'name')
                    ->searchable()
                    ->preload(),
                Filter::make('created_at')
                    ->schema([
                        DatePicker::make('created_from')->label('Created from'),
                        DatePicker::make('created_until')->label('Created until'),
                    ])
                    ->columns(2)
                    ->query(fn (Builder $query, array $data): Builder => $query
                        ->when(
                            $data['created_from'] ?? null,
                            fn (Builder $query, string $date): Builder => $query->whereDate('created_at', '>=', $date),
                        )
                        ->when(
                            $data['created_until'] ?? null,
                            fn (Builder $query, string $date): Builder => $query->whereDate('created_at', '<=', $date),
                        )),
            ])
            ->filtersFormColumns(3)
            ->recordActions([
                ActionGroup::make([
                    EditAction::make(),
                    Action::make('confirm')
                        ->label('Confirm Order')
                        ->icon('heroicon-o-check-circle')
                        ->color('info')
                        ->visible(fn (RestaurantOrder $record): bool => $record->status === 'pending' && $record->payment_status === 'completed')
                        ->requiresConfirmation()
                        ->action(function (RestaurantOrder $record, RestaurantKitchenService $kitchen): void {
                            $kitchen->confirm($record);
                            Notification::make()->title('Order confirmed')->body("Order {$record->order_number} has entered the kitchen queue.")->success()->send();
                        }),
                    Action::make('start_preparing')
                        ->label('Start Preparing')
                        ->icon('heroicon-o-fire')
                        ->color('warning')
                        ->visible(fn (RestaurantOrder $record): bool => $record->status === 'confirmed' && $record->payment_status === 'completed')
                        ->schema([Textarea::make('kitchen_notes')->label('Kitchen Notes')->rows(4)])
                        ->modalHeading('Start Preparing Order')
                        ->modalDescription('Assign this order to yourself and move it into preparation.')
                        ->action(function (RestaurantOrder $record, array $data, RestaurantKitchenService $kitchen): void {
                            $kitchen->startPreparing($record, $data['kitchen_notes'] ?? null);
                            Notification::make()->title('Preparation started')->body("Order {$record->order_number} is now being prepared.")->success()->send();
                        }),
                    Action::make('mark_ready')
                        ->label('Mark Ready')
                        ->icon('heroicon-o-bell-alert')
                        ->color('success')
                        ->visible(fn (RestaurantOrder $record): bool => $record->status === 'preparing')
                        ->requiresConfirmation()
                        ->modalDescription('Confirm that every item in this order is ready for collection or serving.')
                        ->action(function (RestaurantOrder $record, RestaurantKitchenService $kitchen): void {
                            $kitchen->markReady($record);
                            Notification::make()->title('Order ready')->body("Order {$record->order_number} is ready to be served.")->success()->send();
                        }),
                    Action::make('mark_served')
                        ->label('Mark Served')
                        ->icon('heroicon-o-hand-raised')
                        ->color('gray')
                        ->visible(fn (RestaurantOrder $record): bool => $record->status === 'ready')
                        ->requiresConfirmation()
                        ->action(function (RestaurantOrder $record, RestaurantKitchenService $kitchen): void {
                            $kitchen->markServed($record);
                            Notification::make()->title('Order served')->body("Order {$record->order_number} has been marked as served.")->success()->send();
                        }),
                    Action::make('cancel')
                        ->label('Cancel Order')
                        ->icon('heroicon-o-x-circle')
                        ->color('danger')
                        ->visible(fn (RestaurantOrder $record): bool => in_array($record->status, ['pending', 'confirmed', 'preparing'], true))
                        ->schema([Textarea::make('reason')->label('Cancellation Reason')->required()->rows(4)])
                        ->modalHeading('Cancel Restaurant Order')
                        ->requiresConfirmation()
                        ->action(function (RestaurantOrder $record, array $data, RestaurantKitchenService $kitchen): void {
                            $kitchen->cancel($record, $data['reason']);
                            Notification::make()->title('Order cancelled')->body("Order {$record->order_number} was cancelled.")->danger()->send();
                        }),
                ])->label('Kitchen Actions')->button(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([DeleteBulkAction::make()]),
            ]);
    }
}
