<?php

namespace App\Filament\Admin\Resources\KitchenProductions;

use App\Filament\Admin\Resources\KitchenProductions\Pages\CreateKitchenProduction;
use App\Filament\Admin\Resources\KitchenProductions\Pages\EditKitchenProduction;
use App\Filament\Admin\Resources\KitchenProductions\Pages\ListKitchenProductions;
use App\Filament\Admin\Resources\SecureResource;
use App\Models\Ingredient;
use App\Models\KitchenProduction;
use App\Models\MenuItem;
use App\Models\Restaurant;
use App\Services\KitchenProductionRecipeService;
use App\Services\KitchenStockService;
use BackedEnum;
use Closure;
use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Infolists\Components\TextEntry;
use Filament\Notifications\Notification;
use Filament\Schemas\Components\Actions;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Validation\ValidationException;

/**
 * Configures Filament administration for kitchen production resource.
 */
class KitchenProductionResource extends SecureResource
{
    protected static ?string $model = KitchenProduction::class;

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
        return $schema->components([
            Grid::make(['default' => 1, 'lg' => 2])->schema([
                Section::make('Kitchen Production Batch')
                    ->description(fn (string $operation): string => $operation === 'edit'
                        ? 'Inventory-controlled fields are locked after the batch is posted. Only production notes can be updated.'
                        : 'Record the finished food prepared by the kitchen.')
                    ->schema([
                        Select::make('restaurant_id')
                            ->label('Restaurant')
                            ->relationship('restaurant', 'name', modifyQueryUsing: fn (Builder $query): Builder => $query->orderBy('name'))
                            ->searchable()
                            ->preload()
                            ->live()
                            ->default(function (): ?int {
                                $restaurantIds = Restaurant::query()->orderBy('id')->limit(2)->pluck('id');

                                return $restaurantIds->count() === 1 ? $restaurantIds->first() : null;
                            })
                            ->afterStateUpdated(fn (Set $set): mixed => $set('ingredients', []))
                            ->helperText('Ingredient choices are limited to this restaurant’s active stock.')
                            ->disabledOn('edit')
                            ->required(fn (string $operation): bool => $operation === 'create'),
                        Select::make('menu_item_id')
                            ->label('Menu Item')
                            ->relationship(
                                'menuItem',
                                'name',
                                modifyQueryUsing: fn (Builder $query): Builder => $query
                                    ->with('category:id,name')
                                    ->where('tracks_kitchen_production', true)
                                    ->orderBy('name'),
                            )
                            ->getOptionLabelFromRecordUsing(
                                fn (MenuItem $item): string => $item->category
                                    ? "{$item->name} ({$item->category->name})"
                                    : $item->name,
                            )
                            ->searchable(['name', 'description'])
                            ->preload()
                            ->live()
                            ->helperText('Loaded from Menu Items. Enable “Track Kitchen Production” on a menu item to make it selectable here.')
                            ->disabledOn('edit')
                            ->required(),
                        DatePicker::make('production_date')->default(today())->maxDate(today())->disabledOn('edit')->required(),
                        TextInput::make('quantity_produced')
                            ->label('Quantity Produced')
                            ->numeric()
                            ->minValue(.001)
                            ->step(.001)
                            ->live(debounce: 500)
                            ->helperText('Enter the finished quantity prepared in this batch.')
                            ->disabledOn('edit')
                            ->required(),
                        TextInput::make('quantity_wasted')
                            ->label('Quantity Wasted')
                            ->numeric()
                            ->minValue(0)
                            ->step(.001)
                            ->default(0)
                            ->helperText('Enter 0 if there was no finished-food waste.')
                            ->disabledOn('edit')
                            ->rules([
                                fn (callable $get): Closure => function (string $attribute, mixed $value, Closure $fail) use ($get): void {
                                    $produced = $get('quantity_produced');

                                    if ($produced !== null && $produced !== '' && (float) $value > (float) $produced) {
                                        $fail('Quantity wasted cannot be greater than quantity produced.');
                                    }
                                },
                            ])
                            ->required(),
                        Placeholder::make('recipe_prefill_status')
                            ->label('Recipe Estimate')
                            ->content(fn (Get $get): string => static::recipePrefillStatus($get))
                            ->visible(fn (Get $get, string $operation): bool => $operation === 'create'
                                && static::selectedInventoryConsumptionMode($get) === 'production_batch')
                            ->columnSpanFull(),
                        Actions::make([
                            Action::make('loadRecipeEstimate')
                                ->label('Load recipe estimate')
                                ->icon('heroicon-o-document-arrow-down')
                                ->color('info')
                                ->disabled(fn (Get $get): bool => ! static::canLoadRecipeEstimate($get))
                                ->requiresConfirmation()
                                ->modalHeading('Replace ingredient rows with the recipe estimate?')
                                ->modalDescription('This replaces the current ingredient entries. Review and adjust every estimated quantity to the actual amount used before saving the batch.')
                                ->modalSubmitActionLabel('Load recipe estimate')
                                ->action(function (Get $schemaGet, Set $schemaSet): void {
                                    try {
                                        $ingredients = app(KitchenProductionRecipeService::class)->estimate(
                                            (int) $schemaGet('menu_item_id'),
                                            (int) $schemaGet('restaurant_id'),
                                            (float) $schemaGet('quantity_produced'),
                                        );
                                    } catch (ValidationException $exception) {
                                        Notification::make()
                                            ->title('Recipe cannot be loaded')
                                            ->body(collect($exception->errors())->flatten()->join(' '))
                                            ->danger()
                                            ->send();

                                        return;
                                    }

                                    $schemaSet('ingredients', $ingredients);

                                    Notification::make()
                                        ->title('Recipe estimate loaded')
                                        ->body('Review the quantities and replace estimates with the actual ingredient usage before saving.')
                                        ->success()
                                        ->send();
                                }),
                        ])
                            ->key('recipe_estimate_actions')
                            ->visible(fn (Get $get, string $operation): bool => $operation === 'create'
                                && static::selectedInventoryConsumptionMode($get) === 'production_batch')
                            ->fullWidth()
                            ->columnSpanFull(),
                        Repeater::make('ingredients')
                            ->label('Raw Ingredients Consumed')
                            ->helperText('Record the actual quantity of each ingredient used for this batch. These amounts are deducted from kitchen stock when saved.')
                            ->schema([
                                Select::make('ingredient_id')
                                    ->options(fn (Get $get): array => Ingredient::query()
                                        ->where('restaurant_id', $get('../../restaurant_id'))
                                        ->where('is_active', true)
                                        ->orderBy('name')
                                        ->get()
                                        ->mapWithKeys(fn (Ingredient $ingredient): array => [
                                            $ingredient->id => sprintf(
                                                '%s — %s %s available',
                                                $ingredient->name,
                                                number_format((float) $ingredient->current_stock, 3),
                                                $ingredient->unit,
                                            ),
                                        ])
                                        ->all())
                                    ->searchable()
                                    ->live()
                                    ->disabled(fn (Get $get): bool => blank($get('../../restaurant_id')))
                                    ->helperText(fn (Get $get): ?string => blank($get('../../restaurant_id'))
                                        ? 'Select a restaurant before choosing ingredients.'
                                        : null)
                                    ->required()
                                    ->distinct()
                                    ->disableOptionsWhenSelectedInSiblingRepeaterItems(),
                                TextInput::make('quantity_used')
                                    ->label('Quantity Used')
                                    ->numeric()
                                    ->minValue(.001)
                                    ->step(.001)
                                    ->live(onBlur: true)
                                    ->helperText('Enter this amount in the selected ingredient’s stock unit.')
                                    ->rules([
                                        fn (Get $get): Closure => function (string $attribute, mixed $value, Closure $fail) use ($get): void {
                                            $ingredient = static::selectedProductionIngredient($get);

                                            if (! $ingredient || blank($value)) {
                                                return;
                                            }

                                            if ((float) $value > (float) $ingredient->current_stock) {
                                                $fail(sprintf(
                                                    '%s has only %s %s available.',
                                                    $ingredient->name,
                                                    number_format((float) $ingredient->current_stock, 3),
                                                    $ingredient->unit,
                                                ));
                                            }
                                        },
                                    ])
                                    ->required(),
                                TextEntry::make('stock_unit')
                                    ->label('Stock Unit')
                                    ->state(function (Get $get): string {
                                        $ingredientId = $get('ingredient_id');

                                        if (blank($ingredientId)) {
                                            return 'Select an ingredient';
                                        }

                                        return Ingredient::query()->whereKey($ingredientId)->value('unit')
                                            ?? 'Ingredient unavailable';
                                    })
                                    ->badge()
                                    ->color(fn (string $state): string => $state === 'Ingredient unavailable' ? 'danger' : 'info'),
                                TextEntry::make('stock_availability')
                                    ->label('Live Stock Availability')
                                    ->state(fn (Get $get): string => static::productionStockAvailability($get))
                                    ->badge()
                                    ->color(fn (string $state): string => match (true) {
                                        str_contains($state, 'Short by') => 'danger',
                                        str_starts_with($state, 'Select an ingredient') => 'gray',
                                        default => 'success',
                                    })
                                    ->helperText('The final balance is verified again under a database lock when the batch is saved.')
                                    ->columnSpanFull(),
                                Textarea::make('notes')
                                    ->label('Usage Notes')
                                    ->rows(2)
                                    ->columnSpanFull(),
                            ])
                            ->minItems(1)
                            ->defaultItems(1)
                            ->addActionLabel('Add ingredient')
                            ->columns(['default' => 1, 'sm' => 2])
                            ->columnSpanFull()
                            ->visible(fn (Get $get, string $operation): bool => $operation === 'create'
                                && static::selectedInventoryConsumptionMode($get) === 'production_batch')
                            ->dehydrated(fn (Get $get): bool => static::selectedInventoryConsumptionMode($get) === 'production_batch'),
                        Placeholder::make('ingredient_deduction_mode')
                            ->label('Ingredient Stock Deduction')
                            ->content(fn (Get $get): string => match (static::selectedInventoryConsumptionMode($get)) {
                                'per_order' => 'Raw ingredients are deducted when preparation starts for each customer order, not when this production record is saved.',
                                'none' => 'This menu item does not deduct raw ingredient stock. Saving this production record only updates finished-food reporting.',
                                default => '',
                            })
                            ->visible(fn (Get $get, string $operation): bool => $operation === 'create'
                                && filled($get('menu_item_id'))
                                && static::selectedInventoryConsumptionMode($get) !== 'production_batch')
                            ->columnSpanFull(),
                        TextEntry::make('recorded_ingredients')
                            ->label('Recorded ingredients consumed')
                            ->state(fn (?KitchenProduction $record): array => $record?->ingredients()
                                ->with('ingredient:id,name,unit')
                                ->get()
                                ->map(fn ($line): string => sprintf(
                                    '%s — %s %s',
                                    $line->ingredient?->name ?? 'Deleted ingredient',
                                    number_format((float) $line->quantity_used, 3),
                                    $line->unit ?: ($line->ingredient?->unit ?? 'unit'),
                                ))
                                ->all() ?? [])
                            ->helperText('These quantities have already been posted to the kitchen stock ledger. Use an audited correction workflow instead of changing them here.')
                            ->placeholder('No ingredient consumption was recorded for this batch.')
                            ->bulleted()
                            ->columnSpanFull()
                            ->visibleOn('edit'),
                        Textarea::make('notes')->rows(4)->columnSpanFull(),
                        Hidden::make('produced_by')->default(fn (): ?int => auth()->id()),
                    ])
                    ->columns(['default' => 1, 'sm' => 2])
                    ->columnSpan(['default' => 1, 'lg' => 2]),
                // Section::make('Production guide')
                //     ->description('Use this checklist before saving a new batch.')
                //     ->schema([
                //         Placeholder::make('select_menu_item')
                //             ->label('1. Select the prepared menu item')
                //             ->content('The list comes from Menu Items. If an item is missing, enable “Track Kitchen Production” on that menu item first.'),
                //         Placeholder::make('record_actual_quantity')
                //             ->label('2. Record actual finished quantity')
                //             ->content('Enter only food prepared in this batch, using the production unit configured for the selected menu item.'),
                //         Placeholder::make('production_units')
                //             ->label('Units of food produced')
                //             ->content('Use the menu item’s configured unit: portions for plated meals, pieces for individual items, trays for baked goods, kilograms or grams for weight, litres or millilitres for liquids, and bottles for bottled drinks.'),
                //         Placeholder::make('record_waste')
                //             ->label('3. Record waste separately')
                //             ->content('Enter any spoiled, burnt, or discarded finished food. Waste cannot be greater than the produced quantity.'),
                //         Placeholder::make('check_date')
                //             ->label('4. Check the production date')
                //             ->content('Use today or the correct past production date. Future production batches cannot be recorded.'),
                //     ])
                //     ->columnSpan(2),
            ]),
            Grid::make(['default' => 1, 'lg' => 2])->schema([
                // Section::make('Kitchen Production Batch')
                //     ->description('Record the finished food prepared by the kitchen.')
                //     ->schema([
                //         Select::make('menu_item_id')
                //             ->label('Menu Item')
                //             ->relationship(
                //                 'menuItem',
                //                 'name',
                //                 modifyQueryUsing: fn (Builder $query): Builder => $query
                //                     ->with('category:id,name')
                //                     ->where('tracks_kitchen_production', true)
                //                     ->orderBy('name'),
                //             )
                //             ->getOptionLabelFromRecordUsing(
                //                 fn (MenuItem $item): string => $item->category
                //                     ? "{$item->name} ({$item->category->name})"
                //                     : $item->name,
                //             )
                //             ->searchable(['name', 'description'])
                //             ->preload()
                //             ->helperText('Loaded from Menu Items. Enable “Track Kitchen Production” on a menu item to make it selectable here.')
                //             ->required(),
                //         DatePicker::make('production_date')->default(today())->maxDate(today())->required(),
                //         TextInput::make('quantity_produced')
                //             ->label('Quantity Produced')
                //             ->numeric()
                //             ->minValue(.001)
                //             ->step(.001)
                //             ->helperText('Enter the finished quantity prepared in this batch.')
                //             ->required(),
                //         TextInput::make('quantity_wasted')
                //             ->label('Quantity Wasted')
                //             ->numeric()
                //             ->minValue(0)
                //             ->step(.001)
                //             ->default(0)
                //             ->helperText('Enter 0 if there was no finished-food waste.')
                //             ->rules([
                //                 fn (callable $get): Closure => function (string $attribute, mixed $value, Closure $fail) use ($get): void {
                //                     $produced = $get('quantity_produced');

                //                     if ($produced !== null && $produced !== '' && (float) $value > (float) $produced) {
                //                         $fail('Quantity wasted cannot be greater than quantity produced.');
                //                     }
                //                 },
                //             ])
                //             ->required(),
                //         Textarea::make('notes')->rows(4)->columnSpanFull(),
                //         Hidden::make('produced_by')->default(fn (): ?int => auth()->id()),
                //     ])
                //     ->columns(['default' => 1, 'sm' => 2])
                //     ->columnSpan(['default' => 1, 'lg' => 2]),
                Section::make('Production guide')
                    ->description('Use this checklist before saving a new batch.')
                    ->schema([
                        Placeholder::make('select_menu_item')
                            ->label('1. Select the prepared menu item')
                            ->content('The list comes from Menu Items. If an item is missing, enable “Track Kitchen Production” on that menu item first.'),
                        Placeholder::make('record_actual_quantity')
                            ->label('2. Record actual finished quantity')
                            ->content('Enter only food prepared in this batch, using the production unit configured for the selected menu item.'),
                        Placeholder::make('production_units')
                            ->label('Units of food produced')
                            ->content('Use the menu item’s configured unit: portions for plated meals, pieces for individual items, trays for baked goods, kilograms or grams for weight, litres or millilitres for liquids, and bottles for bottled drinks.'),
                        Placeholder::make('record_waste')
                            ->label('3. Record waste separately')
                            ->content('Enter any spoiled, burnt, or discarded finished food. Waste cannot be greater than the produced quantity.'),
                        Placeholder::make('check_date')
                            ->label('4. Check the production date')
                            ->content('Use today or the correct past production date. Future production batches cannot be recorded.'),
                    ])
                    ->columnSpan(2),
            ]),
        ]);
    }

    /**
     * Configures the table data source, columns, and actions.
     */
    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('menuItem.name')
                    ->label('Menu Item')
                    ->searchable()
                    ->weight('bold')
                    ->description(fn (KitchenProduction $record): string => sprintf(
                        '%s · %s · Produced %s · Waste %s',
                        $record->batch_reference,
                        $record->production_date->format('M d, Y'),
                        number_format((float) $record->quantity_produced, 3),
                        number_format((float) $record->quantity_wasted, 3),
                    )),
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
                TextColumn::make('quantity_produced')
                    ->label('Produced')
                    ->numeric(decimalPlaces: 3)
                    ->visibleFrom('md'),
                TextColumn::make('quantity_wasted')
                    ->label('Wasted')
                    ->numeric(decimalPlaces: 3)
                    ->visibleFrom('md'),
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
                    ->relationship('menuItem', 'name')
                    ->searchable()
                    ->preload(),
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
                Filter::make('production_date')
                    ->label('Production date')
                    ->schema([
                        DatePicker::make('from')->label('From'),
                        DatePicker::make('until')->label('Until'),
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
            ->defaultSort('production_date', 'desc')
            ->recordActions([
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
            ]);
    }

    /**
     * Returns the stock-deduction mode configured for the selected menu item.
     */
    private static function selectedInventoryConsumptionMode(Get $get): ?string
    {
        $menuItemId = $get('menu_item_id');

        if (blank($menuItemId)) {
            return null;
        }

        return MenuItem::query()->whereKey($menuItemId)->value('inventory_consumption_mode');
    }

    /**
     * Describes whether the selected menu item can populate a recipe estimate.
     */
    private static function recipePrefillStatus(Get $get): string
    {
        $menuItemId = $get('menu_item_id');

        if (blank($menuItemId)) {
            return 'Select a production-batch menu item to check its recipe.';
        }

        $recipeCount = MenuItem::query()
            ->whereKey($menuItemId)
            ->withCount('recipeIngredients')
            ->value('recipe_ingredients_count') ?? 0;

        if ($recipeCount === 0) {
            return 'No recipe ingredients are configured for this menu item. Add its recipe from Menu Items, or enter actual usage manually.';
        }

        if (blank($get('restaurant_id'))) {
            return 'Select the restaurant whose stock will be used.';
        }

        if (blank($get('quantity_produced')) || (float) $get('quantity_produced') <= 0) {
            return 'Enter the finished quantity to scale the recipe estimate.';
        }

        return sprintf(
            'The configured recipe has %d ingredient%s. Loading it replaces the current rows; review the estimate against actual usage.',
            $recipeCount,
            $recipeCount === 1 ? '' : 's',
        );
    }

    /**
     * Determines whether all inputs needed to calculate a recipe estimate exist.
     */
    private static function canLoadRecipeEstimate(Get $get): bool
    {
        if (blank($get('restaurant_id')) || blank($get('menu_item_id'))) {
            return false;
        }

        $quantityProduced = $get('quantity_produced');

        if (blank($quantityProduced) || (float) $quantityProduced <= 0) {
            return false;
        }

        return MenuItem::query()
            ->whereKey($get('menu_item_id'))
            ->where('inventory_consumption_mode', 'production_batch')
            ->whereHas('recipeIngredients')
            ->exists();
    }

    /**
     * Returns the active ingredient selected from the production batch's restaurant.
     */
    private static function selectedProductionIngredient(Get $get): ?Ingredient
    {
        $ingredientId = $get('ingredient_id');
        $restaurantId = $get('../../restaurant_id');

        if (blank($ingredientId) || blank($restaurantId)) {
            return null;
        }

        return Ingredient::query()
            ->whereKey($ingredientId)
            ->where('restaurant_id', $restaurantId)
            ->where('is_active', true)
            ->first();
    }

    /**
     * Describes the selected ingredient balance before and after the requested usage.
     */
    private static function productionStockAvailability(Get $get): string
    {
        $ingredient = static::selectedProductionIngredient($get);

        if (! $ingredient) {
            return 'Select an ingredient to view its stock balance';
        }

        $available = (float) $ingredient->current_stock;
        $requested = $get('quantity_used');
        $balance = sprintf('%s %s available', number_format($available, 3), $ingredient->unit);

        if (blank($requested)) {
            return $balance;
        }

        $requested = (float) $requested;
        $requestedLabel = sprintf('%s %s requested', number_format($requested, 3), $ingredient->unit);

        if ($requested > $available) {
            return sprintf(
                '%s · %s · Short by %s %s',
                $balance,
                $requestedLabel,
                number_format($requested - $available, 3),
                $ingredient->unit,
            );
        }

        return sprintf(
            '%s · %s · %s %s remaining',
            $balance,
            $requestedLabel,
            number_format($available - $requested, 3),
            $ingredient->unit,
        );
    }

    /**
     * Builds and returns pages.
     */
    public static function getPages(): array
    {
        return ['index' => ListKitchenProductions::route('/'), 'create' => CreateKitchenProduction::route('/create'), 'edit' => EditKitchenProduction::route('/{record}/edit')];
    }
}
