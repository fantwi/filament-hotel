<?php

namespace App\Filament\Admin\Resources\KitchenProductions;

use Closure;
use App\Filament\Admin\Resources\KitchenProductions\Pages\CreateKitchenProduction;
use App\Filament\Admin\Resources\KitchenProductions\Pages\EditKitchenProduction;
use App\Filament\Admin\Resources\KitchenProductions\Pages\ListKitchenProductions;
use App\Models\KitchenProduction;
use App\Models\MenuItem;
use BackedEnum;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Actions\EditAction;
use Filament\Actions\DeleteAction;
use Illuminate\Database\Eloquent\Builder;

class KitchenProductionResource extends Resource
{
    protected static ?string $model = KitchenProduction::class;
    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-clipboard-document-list';
    protected static string|\UnitEnum|null $navigationGroup = 'Restaurant';
    protected static ?string $navigationLabel = 'Kitchen Production';

    protected static ?int $navigationSort = 80;
    public static function canViewAny(): bool { return auth()->user()?->can('manage kitchen production') ?? false; }
    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Grid::make(['default' => 1, 'xl' => 3])->schema([
                Section::make('Kitchen Production Batch')
                    ->description('Record the finished food prepared by the kitchen.')
                    ->schema([
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
                            ->helperText('Loaded from Menu Items. Enable “Track Kitchen Production” on a menu item to make it selectable here.')
                            ->required(),
                        DatePicker::make('production_date')->default(today())->maxDate(today())->required(),
                        TextInput::make('quantity_produced')->numeric()->minValue(.001)->step(.001)->required(),
                        TextInput::make('quantity_wasted')
                            ->numeric()
                            ->minValue(0)
                            ->step(.001)
                            ->default(0)
                            ->rules([
                                fn (callable $get): Closure => function (string $attribute, mixed $value, Closure $fail) use ($get): void {
                                    $produced = $get('quantity_produced');

                                    if ($produced !== null && $produced !== '' && (float) $value > (float) $produced) {
                                        $fail('Quantity wasted cannot be greater than quantity produced.');
                                    }
                                },
                            ])
                            ->required(),
                        Textarea::make('notes')->rows(4)->columnSpanFull(),
                        Hidden::make('produced_by')->default(fn (): ?int => auth()->id()),
                    ])
                    ->columns(2)
                    ->columnSpan(['default' => 1, 'xl' => 2]),
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
                    ->columnSpan(1),
            ]),
        ]);
    }
    public static function table(Table $table): Table { return $table->columns([
        TextColumn::make('batch_reference')->searchable()->copyable(), TextColumn::make('menuItem.name')->label('Menu Item')->searchable(),
        TextColumn::make('production_date')->date()->sortable(), TextColumn::make('quantity_produced')->numeric(decimalPlaces: 3),
        TextColumn::make('quantity_wasted')->numeric(decimalPlaces: 3), TextColumn::make('producer.name')->label('Produced By')->toggleable(),
    ])->defaultSort('production_date', 'desc')->recordActions([EditAction::make(), DeleteAction::make()]); }
    public static function getPages(): array { return ['index' => ListKitchenProductions::route('/'), 'create' => CreateKitchenProduction::route('/create'), 'edit' => EditKitchenProduction::route('/{record}/edit')]; }
}
