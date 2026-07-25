<?php

namespace App\Filament\Admin\Resources\KitchenProductions;

use App\Filament\Admin\Resources\KitchenProductions\Pages\CreateKitchenProduction;
use App\Filament\Admin\Resources\KitchenProductions\Pages\EditKitchenProduction;
use App\Filament\Admin\Resources\KitchenProductions\Pages\ListKitchenProductions;
use App\Models\KitchenProduction;
use BackedEnum;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
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
    public static function form(Schema $schema): Schema { return $schema->components([Section::make('Kitchen Production Batch')->schema([
        Select::make('menu_item_id')->relationship('menuItem', 'name', modifyQueryUsing: fn (Builder $query): Builder => $query->where('tracks_kitchen_production', true))->searchable()->preload()->required(),
        DatePicker::make('production_date')->default(today())->maxDate(today())->required(),
        TextInput::make('quantity_produced')->numeric()->minValue(.001)->step(.001)->required(),
        TextInput::make('quantity_wasted')->numeric()->minValue(0)->step(.001)->default(0)->rules(['lte:quantity_produced'])->required(),
        Textarea::make('notes')->rows(4)->columnSpanFull(), Hidden::make('produced_by')->default(fn (): ?int => auth()->id()),
    ])->columns(2)]); }
    public static function table(Table $table): Table { return $table->columns([
        TextColumn::make('batch_reference')->searchable()->copyable(), TextColumn::make('menuItem.name')->label('Menu Item')->searchable(),
        TextColumn::make('production_date')->date()->sortable(), TextColumn::make('quantity_produced')->numeric(decimalPlaces: 3),
        TextColumn::make('quantity_wasted')->numeric(decimalPlaces: 3), TextColumn::make('producer.name')->label('Produced By')->toggleable(),
    ])->defaultSort('production_date', 'desc')->recordActions([EditAction::make(), DeleteAction::make()]); }
    public static function getPages(): array { return ['index' => ListKitchenProductions::route('/'), 'create' => CreateKitchenProduction::route('/create'), 'edit' => EditKitchenProduction::route('/{record}/edit')]; }
}
