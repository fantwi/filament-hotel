<?php

namespace App\Filament\Admin\Resources\KitchenProductions;

use App\Filament\Admin\Resources\KitchenProductions\Pages\CreateKitchenProduction;
use App\Filament\Admin\Resources\KitchenProductions\Pages\EditKitchenProduction;
use App\Filament\Admin\Resources\KitchenProductions\Pages\ListKitchenProductions;
use App\Filament\Admin\Resources\KitchenProductions\Pages\ViewKitchenProduction;
use App\Filament\Admin\Resources\KitchenProductions\Schemas\KitchenProductionForm;
use App\Filament\Admin\Resources\KitchenProductions\Schemas\KitchenProductionInfolist;
use App\Filament\Admin\Resources\SecureResource;
use App\Models\KitchenProduction;
use App\Models\MenuCategory;
use App\Models\MenuItem;
use App\Models\User;
use App\Services\KitchenStockService;
use BackedEnum;
use Carbon\CarbonImmutable;
use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use Filament\Schemas\Components\Actions;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Enums\RecordActionsPosition;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Str;

/**
 * Configures Filament administration for kitchen production resource.
 */
class KitchenProductionResource extends SecureResource
{
    protected static ?string $model = KitchenProduction::class;

    protected static ?string $recordTitleAttribute = 'batch_reference';

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-clipboard-document-list';

    protected static string|\UnitEnum|null $navigationGroup = 'Kitchen & Inventory';

    protected static ?string $navigationLabel = 'Kitchen Production';

    protected static ?int $navigationSort = 30;

    /**
     * Determines whether the current user may view these records.
     */
    public static function canViewAny(): bool
    {
        return auth()->user()?->can('manage kitchen production') ?? false;
    }

    /**
     * Determines whether the current user may create records.
     */
    public static function canCreate(): bool
    {
        return auth()->user()?->can('manage kitchen production') ?? false;
    }

    /**
     * Determines whether the current user may edit the supplied record.
     */
    public static function canEdit($record): bool
    {
        return auth()->user()?->can('manage kitchen production') ?? false;
    }

    /**
     * Determines whether the current user may reverse and void a production batch.
     */
    public static function canVoid(KitchenProduction $record): bool
    {
        $user = auth()->user();

        return ! $record->voided_at
            && (bool) $user?->hasAnyRole(['super_admin', 'admin', 'manager', 'kitchen_manager'])
            && (bool) $user?->can('void kitchen production');
    }

    /**
     * Configures the form schema and input behavior.
     */
    public static function form(Schema $schema): Schema
    {
        return KitchenProductionForm::configure($schema);
    }

    /**
     * Configures the read-only batch and inventory details page.
     */
    public static function infolist(Schema $schema): Schema
    {
        return KitchenProductionInfolist::configure($schema);
    }

    /**
     * Configures the table data source, columns, and actions.
     */
    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query): Builder => $query->with(['menuItem', 'restaurant']))
            ->columns([
                TextColumn::make('mobile_summary')
                    ->label('Production Batch')
                    ->state(fn (KitchenProduction $record): string => $record->menuItem?->name ?? 'Menu item unavailable')
                    ->description(fn (KitchenProduction $record): string => sprintf(
                        '%s · %s · %s',
                        $record->batch_reference ?: 'Batch not recorded',
                        $record->restaurant?->name ?: 'Legacy batch',
                        $record->production_date?->format('M d, Y') ?? 'Date not recorded',
                    ))
                    ->wrap()
                    ->weight('bold')
                    ->hiddenFrom('md'),
                TextColumn::make('menuItem.name')
                    ->label('Menu Item')
                    ->searchable()
                    ->weight('bold')
                    ->visibleFrom('md'),
                TextColumn::make('restaurant.name')
                    ->label('Restaurant')
                    ->placeholder('Legacy batch')
                    ->sortable()
                    ->toggleable()
                    ->visibleFrom('md'),
                TextColumn::make('batch_reference')
                    ->label('Batch')
                    ->searchable()
                    ->copyable()
                    ->visibleFrom('md'),
                TextColumn::make('production_date')
                    ->label('Production Date')
                    ->date('M d, Y')
                    ->sortable()
                    ->visibleFrom('md'),
                TextColumn::make('yield_summary')
                    ->label('Yield')
                    ->state(fn (KitchenProduction $record): array => static::productionTableYieldSummary($record))
                    ->badge()
                    ->color(fn (string $state, KitchenProduction $record): string => static::productionTableYieldColor($state, $record))
                    ->listWithLineBreaks()
                    ->wrap(),
                TextColumn::make('producer.name')
                    ->label('Produced By')
                    ->toggleable()
                    ->visibleFrom('lg'),
                TextColumn::make('inventory_status')
                    ->label('Status')
                    ->state(fn (KitchenProduction $record): string => $record->voided_at ? 'Voided' : 'Posted')
                    ->badge()
                    ->color(fn (string $state): string => $state === 'Voided' ? 'danger' : 'success')
                    ->description(fn (KitchenProduction $record): ?string => $record->voided_at ? $record->void_reason : null),
            ])
            ->filters([
                SelectFilter::make('restaurant_id')
                    ->label('Restaurant')
                    ->relationship('restaurant', 'name')
                    ->searchable()
                    ->preload(),
                SelectFilter::make('menu_item')
                    ->label('Menu item')
                    ->options(fn (): array => MenuItem::query()
                        ->whereHas('kitchenProductions')
                        ->orderBy('name')
                        ->pluck('name', 'id')
                        ->all())
                    ->searchable()
                    ->preload()
                    ->query(fn (Builder $query, array $data): Builder => $query->when(
                        filled($data['value'] ?? null),
                        fn (Builder $query): Builder => $query->where('menu_item_id', $data['value']),
                    )),
                SelectFilter::make('category')
                    ->label('Menu category')
                    ->options(fn (): array => MenuCategory::query()
                        ->whereHas('menuItems.kitchenProductions')
                        ->orderBy('name')
                        ->pluck('name', 'id')
                        ->all())
                    ->searchable()
                    ->preload()
                    ->query(fn (Builder $query, array $data): Builder => $query->when(
                        filled($data['value'] ?? null),
                        fn (Builder $query): Builder => $query->whereHas(
                            'menuItem',
                            fn (Builder $query): Builder => $query->where('menu_category_id', $data['value']),
                        ),
                    )),
                SelectFilter::make('produced_by')
                    ->label('Produced by')
                    ->options(fn (): array => User::query()
                        ->whereIn(
                            'id',
                            KitchenProduction::query()
                                ->select('produced_by')
                                ->whereNotNull('produced_by'),
                        )
                        ->orderBy('first_name')
                        ->orderBy('last_name')
                        ->get()
                        ->mapWithKeys(fn (User $user): array => [$user->id => $user->name])
                        ->all())
                    ->searchable()
                    ->preload()
                    ->query(fn (Builder $query, array $data): Builder => $query->when(
                        filled($data['value'] ?? null),
                        fn (Builder $query): Builder => $query->where('produced_by', $data['value']),
                    )),
                SelectFilter::make('inventory_status')
                    ->label('Batch status')
                    ->options([
                        'posted' => 'Posted',
                        'voided' => 'Voided',
                    ])
                    ->query(fn (Builder $query, array $data): Builder => match ($data['value'] ?? null) {
                        'posted' => $query->whereNull('voided_at'),
                        'voided' => $query->whereNotNull('voided_at'),
                        default => $query,
                    }),
                Filter::make('waste_only')
                    ->label('Has finished-food waste')
                    ->toggle()
                    ->query(fn (Builder $query): Builder => $query->where('quantity_wasted', '>', 0)),
                SelectFilter::make('date_preset')
                    ->label('Quick period')
                    ->options([
                        'today' => 'Today',
                        'last_7_days' => 'Last 7 days',
                        'this_month' => 'This month',
                        'previous_month' => 'Previous month',
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        [$start, $endExclusive] = static::productionDatePresetBounds($data['value'] ?? null);

                        if ($start === null || $endExclusive === null) {
                            return $query;
                        }

                        return $query
                            ->where('production_date', '>=', $start)
                            ->where('production_date', '<', $endExclusive);
                    }),
                Filter::make('production_date')
                    ->label('Production date')
                    ->schema([
                        DatePicker::make('from')
                            ->label('From')
                            ->maxDate(fn (Get $get): mixed => $get('until')),
                        DatePicker::make('until')
                            ->label('Until')
                            ->minDate(fn (Get $get): mixed => $get('from')),
                    ])
                    ->columns(2)
                    ->query(fn (Builder $query, array $data): Builder => $query
                        ->when(
                            $data['from'] ?? null,
                            fn (Builder $query, string $date): Builder => $query->whereDate('production_date', '>=', $date),
                        )
                        ->when(
                            $data['until'] ?? null,
                            fn (Builder $query, string $date): Builder => $query->whereDate('production_date', '<=', $date),
                        )),
            ])
            ->defaultSort(fn (Builder $query): Builder => $query
                ->orderByDesc('production_date')
                ->orderByDesc('id'))
            ->recordActions([
                ViewAction::make(),
                EditAction::make(),
                Action::make('void')
                    ->label('Void batch')
                    ->icon('heroicon-o-arrow-uturn-left')
                    ->color('danger')
                    ->visible(fn (KitchenProduction $record): bool => static::canVoid($record))
                    ->authorize(fn (KitchenProduction $record): bool => static::canVoid($record))
                    ->requiresConfirmation()
                    ->modalHeading(fn (KitchenProduction $record): string => "Void production batch {$record->batch_reference}?")
                    ->modalDescription('The consumed raw ingredients will be restored to stock. The batch and its ledger history will remain available for audit.')
                    ->modalSubmitActionLabel('Void batch and restore stock')
                    ->schema([
                        Textarea::make('reason')
                            ->label('Reason for voiding')
                            ->helperText('Explain why this posted batch must be reversed.')
                            ->rows(4)
                            ->maxLength(1000)
                            ->required(),
                    ])
                    ->action(function (KitchenProduction $record, array $data, KitchenStockService $stock): void {
                        $stock->voidProduction($record, $data['reason'], auth()->user());

                        Notification::make()
                            ->title('Production batch voided')
                            ->body('Consumed ingredients were restored to kitchen stock and the audit record was retained.')
                            ->success()
                            ->send();
                    }),
            ], RecordActionsPosition::BeforeColumns)
            ->stackedOnMobile();
    }

    /**
     * Returns index-friendly inclusive-start and exclusive-end period bounds.
     *
     * @return array{CarbonImmutable|null, CarbonImmutable|null}
     */
    private static function productionDatePresetBounds(?string $preset): array
    {
        $today = today()->toImmutable()->startOfDay();

        return match ($preset) {
            'today' => [$today, $today->addDay()],
            'last_7_days' => [$today->subDays(6), $today->addDay()],
            'this_month' => [$today->startOfMonth(), $today->startOfMonth()->addMonth()],
            'previous_month' => [$today->startOfMonth()->subMonth(), $today->startOfMonth()],
            default => [null, null],
        };
    }

    /**
     * Builds the compact produced, net, and waste values displayed in the register.
     *
     * @return list<string>
     */
    private static function productionTableYieldSummary(KitchenProduction $record): array
    {
        $produced = (float) $record->quantity_produced;
        $wasted = (float) $record->quantity_wasted;
        $invalidWaste = $wasted < 0 || $wasted > $produced;

        return [
            'Produced '.static::productionTableQuantity($record, $produced),
            $invalidWaste
                ? 'Net Invalid waste quantity'
                : 'Net '.static::productionTableQuantity($record, max($produced - $wasted, 0)),
            sprintf(
                'Waste %s (%s)',
                static::productionTableQuantity($record, $wasted),
                $invalidWaste
                    ? 'Check quantity'
                    : ($produced > 0 ? number_format(($wasted / $produced) * 100, 2).'%' : '—'),
            ),
        ];
    }

    /**
     * Formats a register quantity with the menu item's configured production unit.
     */
    private static function productionTableQuantity(KitchenProduction $record, float $quantity): string
    {
        $unit = $record->menuItem?->production_unit ?: 'unit';

        return sprintf(
            '%s %s',
            number_format($quantity, 3),
            Str::plural($unit, abs($quantity)),
        );
    }

    /**
     * Assigns a semantic color to each register yield badge.
     */
    private static function productionTableYieldColor(string $state, KitchenProduction $record): string
    {
        if (str_starts_with($state, 'Produced ')) {
            return 'info';
        }

        if (str_starts_with($state, 'Net ')) {
            return str_contains($state, 'Invalid waste quantity') ? 'danger' : 'success';
        }

        if ((float) $record->quantity_wasted < 0 || (float) $record->quantity_wasted > (float) $record->quantity_produced) {
            return 'danger';
        }

        return (float) $record->quantity_wasted > 0 ? 'warning' : 'gray';
    }

    /**
     * Builds and returns pages.
     */
    public static function getPages(): array
    {
        return [
            'index' => ListKitchenProductions::route('/'),
            'create' => CreateKitchenProduction::route('/create'),
            'view' => ViewKitchenProduction::route('/{record}'),
            'edit' => EditKitchenProduction::route('/{record}/edit'),
        ];
    }
}
