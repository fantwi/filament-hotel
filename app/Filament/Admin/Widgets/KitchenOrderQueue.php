<?php

namespace App\Filament\Admin\Widgets;

use App\Filament\Admin\Concerns\InteractsWithDashboardDateRange;
use App\Models\RestaurantOrder;
use App\Services\RestaurantKitchenService;
use App\Services\StaffAccountAccess;
use Filament\Actions\Action;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;

/**
 * Provides the kitchen order queue Filament dashboard widget.
 */
class KitchenOrderQueue extends TableWidget
{
    use InteractsWithDashboardDateRange;

    private const OPERATIONAL_ACTIONS = ['start_preparing', 'ready', 'served'];

    protected static ?string $heading = 'Live Kitchen Order Queue';

    protected static ?int $sort = 5;

    protected int|string|array $columnSpan = 'full';

    /**
     * Re-authorizes an already-mounted operational action before Filament can
     * short-circuit it as hidden after the staff account status changes.
     */
    public function callMountedAction(array $arguments = []): mixed
    {
        $actionName = $this->getMountedAction()?->getName();

        if (in_array($actionName, self::OPERATIONAL_ACTIONS, true)) {
            $this->authorizeOperationalActions();
        }

        return parent::callMountedAction($arguments);
    }

    /**
     * Configures the table data source, columns, and actions.
     */
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
            ->emptyStateHeading(fn (): string => $this->hasTableSearch()
                ? 'No orders match your search'
                : 'No active kitchen orders')
            ->emptyStateDescription(fn (): string => $this->hasTableSearch()
                ? 'No active kitchen orders match the current search. Clear the search to return to the full live queue.'
                : 'Only eligible orders in Confirmed, Preparing, or Ready status appear here. The queue refreshes automatically every 10 seconds.')
            ->emptyStateIcon(fn (): string => $this->hasTableSearch()
                ? 'heroicon-o-magnifying-glass'
                : 'heroicon-o-check-circle')
            ->emptyStateActions([
                Action::make('clearSearch')
                    ->label('Clear search')
                    ->icon('heroicon-o-x-mark')
                    ->color('gray')
                    ->visible(fn (): bool => $this->hasTableSearch())
                    ->action(function (): void {
                        $this->resetTableSearch();
                    }),
                Action::make('refreshQueue')
                    ->label('Refresh queue')
                    ->icon('heroicon-o-arrow-path')
                    ->color('gray')
                    ->visible(fn (): bool => ! $this->hasTableSearch())
                    ->action(function (): void {
                        $this->resetTable();
                    }),
            ])
            ->columns([
                TextColumn::make('mobile_order_context')
                    ->label('Order')
                    ->state(fn (RestaurantOrder $record): string => $record->order_number)
                    ->description(function (RestaurantOrder $record): string {
                        $tableNumber = $record->table?->table_number ?? $record->reservation?->table?->table_number;
                        $waiting = $record->created_at?->shortAbsoluteDiffForHumans() ?? 'unknown';

                        return (filled($tableNumber) ? 'Table '.$tableNumber : 'No table').' · Waiting '.$waiting;
                    })
                    ->wrap()
                    ->weight('bold')
                    ->hiddenFrom('md'),
                TextColumn::make('order_number')
                    ->label('Order')
                    ->searchable()
                    ->weight('bold')
                    ->visibleFrom('md'),
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
                    ->color(fn (string $state): string => $state === 'No Table' ? 'gray' : 'success')
                    ->toggleable()
                    ->visibleFrom('md'),
                TextColumn::make('ordering_channel')
                    ->label('Channel')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'qr' => 'Table QR', 'web' => 'Website', 'staff' => 'Staff', default => ucfirst($state),
                    })
                    ->color(fn (string $state): string => match ($state) {
                        'qr' => 'success', 'web' => 'info', 'staff' => 'warning', default => 'gray',
                    })
                    ->toggleable()
                    ->visibleFrom('lg'),
                TextColumn::make('status')->badge()->color(fn (string $state): string => match ($state) {
                    'confirmed' => 'info', 'preparing' => 'warning', 'ready' => 'success', default => 'gray',
                }),
                TextColumn::make('preparedBy.name')
                    ->label('Chef')
                    ->placeholder('Unassigned')
                    ->toggleable()
                    ->visibleFrom('lg'),
                TextColumn::make('created_at')
                    ->label('Waiting')
                    ->since()
                    ->toggleable()
                    ->visibleFrom('md'),
                TextColumn::make('kitchen_notes')
                    ->label('Notes')
                    ->placeholder('No kitchen notes')
                    ->wrap()
                    ->toggleable()
                    ->visibleFrom('xl'),
            ])
            ->recordActions([
                Action::make('start_preparing')
                    ->label('Prepare')
                    ->icon('heroicon-o-fire')
                    ->color('warning')
                    ->visible(fn (RestaurantOrder $record): bool => $record->status === 'confirmed' && $this->allowsOperationalActions())
                    ->schema([Textarea::make('kitchen_notes')->label('Kitchen Notes')->rows(3)])
                    ->action(function (RestaurantOrder $record, array $data, RestaurantKitchenService $kitchen): void {
                        $kitchen->startPreparing($record, $data['kitchen_notes'] ?? null);
                        Notification::make()->title('Preparation started')->success()->send();
                    }),
                Action::make('ready')
                    ->label('Ready')
                    ->icon('heroicon-o-bell-alert')
                    ->color('success')
                    ->visible(fn (RestaurantOrder $record): bool => $record->status === 'preparing' && $this->allowsOperationalActions())
                    ->requiresConfirmation()
                    ->action(function (RestaurantOrder $record, RestaurantKitchenService $kitchen): void {
                        $kitchen->markReady($record);
                        Notification::make()->title('Order marked ready')->success()->send();
                    }),
                Action::make('served')
                    ->label('Served')
                    ->icon('heroicon-o-check')
                    ->color('gray')
                    ->visible(fn (RestaurantOrder $record): bool => $record->status === 'ready' && $this->allowsOperationalActions())
                    ->requiresConfirmation()
                    ->action(function (RestaurantOrder $record, RestaurantKitchenService $kitchen): void {
                        $kitchen->markServed($record);
                        Notification::make()->title('Order marked served')->success()->send();
                    }),
            ])
            ->paginated([10, 25, 50]);
    }

    /**
     * Determines whether the current user may view this feature.
     */
    public static function canView(): bool
    {
        return auth()->user()?->can('view kitchen dashboard') ?? false;
    }

    private function allowsOperationalActions(): bool
    {
        $user = auth()->user();

        return ($user?->can('manage kitchen orders') ?? false)
            && app(StaffAccountAccess::class)->allowsOperationalActions($user);
    }

    private function authorizeOperationalActions(): void
    {
        $user = auth()->user();

        app(StaffAccountAccess::class)->authorizeOperationalActions($user);
        abort_unless($user?->can('manage kitchen orders') ?? false, 403);
    }
}
