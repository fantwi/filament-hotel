<?php

namespace App\Filament\Admin\Widgets;

use App\Models\RestaurantOrder;
use App\Services\RestaurantKitchenService;
use Filament\Actions\Action;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;

class KitchenOrderQueue extends TableWidget
{
    protected static ?string $heading = 'Live Kitchen Order Queue';

    protected static ?int $sort = 5;

    protected int|string|array $columnSpan = 'full';

    public function table(Table $table): Table
    {
        return $table
            ->poll('10s')
            ->query(
                RestaurantOrder::kitchenQueue()
                    ->with(['guest', 'items.menuItem', 'reservation.table', 'table', 'preparedBy'])
                    ->orderByRaw("CASE status WHEN 'ready' THEN 1 WHEN 'preparing' THEN 2 WHEN 'confirmed' THEN 3 ELSE 4 END")
                    ->oldest('created_at'),
            )
            ->columns([
                TextColumn::make('order_number')->label('Order')->searchable()->weight('bold'),
                TextColumn::make('items_summary')
                    ->label('Items')
                    ->state(fn (RestaurantOrder $record): string => $record->items
                        ->map(fn ($item): string => $item->quantity.'× '.($item->menuItem?->name ?? 'Deleted item'))
                        ->implode(', '))
                    ->wrap(),
                TextColumn::make('table_display')
                    ->label('Table')
                    ->state(fn (RestaurantOrder $record): string => $record->table?->table_number ?? $record->reservation?->table?->table_number ?? 'No Table')
                    ->badge()
                    ->color(fn (RestaurantOrder $record): string => $record->restaurant_table_id ? 'success' : 'gray'),
                TextColumn::make('ordering_channel')
                    ->label('Channel')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'qr' => 'Table QR', 'web' => 'Website', 'staff' => 'Staff', default => ucfirst($state),
                    })
                    ->color(fn (string $state): string => match ($state) {
                        'qr' => 'success', 'web' => 'info', 'staff' => 'warning', default => 'gray',
                    }),
                TextColumn::make('status')->badge()->color(fn (string $state): string => match ($state) {
                    'confirmed' => 'info', 'preparing' => 'warning', 'ready' => 'success', default => 'gray',
                }),
                TextColumn::make('preparedBy.name')->label('Chef')->placeholder('Unassigned'),
                TextColumn::make('created_at')->label('Waiting')->since(),
                TextColumn::make('kitchen_notes')->label('Notes')->placeholder('No kitchen notes')->wrap(),
            ])
            ->recordActions([
                Action::make('start_preparing')
                    ->label('Prepare')
                    ->icon('heroicon-o-fire')
                    ->color('warning')
                    ->visible(fn (RestaurantOrder $record): bool => $record->status === 'confirmed')
                    ->schema([Textarea::make('kitchen_notes')->label('Kitchen Notes')->rows(3)])
                    ->action(function (RestaurantOrder $record, array $data, RestaurantKitchenService $kitchen): void {
                        $kitchen->startPreparing($record, $data['kitchen_notes'] ?? null);
                        Notification::make()->title('Preparation started')->success()->send();
                    }),
                Action::make('ready')
                    ->label('Ready')
                    ->icon('heroicon-o-bell-alert')
                    ->color('success')
                    ->visible(fn (RestaurantOrder $record): bool => $record->status === 'preparing')
                    ->requiresConfirmation()
                    ->action(function (RestaurantOrder $record, RestaurantKitchenService $kitchen): void {
                        $kitchen->markReady($record);
                        Notification::make()->title('Order marked ready')->success()->send();
                    }),
                Action::make('served')
                    ->label('Served')
                    ->icon('heroicon-o-check')
                    ->color('gray')
                    ->visible(fn (RestaurantOrder $record): bool => $record->status === 'ready')
                    ->requiresConfirmation()
                    ->action(function (RestaurantOrder $record, RestaurantKitchenService $kitchen): void {
                        $kitchen->markServed($record);
                        Notification::make()->title('Order marked served')->success()->send();
                    }),
            ])
            ->paginated([10, 25, 50]);
    }

    public static function canView(): bool
    {
        return auth()->user()?->can('view kitchen dashboard') ?? false;
    }
}
