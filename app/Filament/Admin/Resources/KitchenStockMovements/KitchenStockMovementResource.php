<?php

namespace App\Filament\Admin\Resources\KitchenStockMovements;

use App\Filament\Admin\Resources\KitchenStockMovements\Pages\ListKitchenStockMovements;
use App\Filament\Admin\Resources\KitchenStockMovements\Tables\KitchenStockMovementsTable;
use App\Models\KitchenStockMovement;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;

class KitchenStockMovementResource extends Resource
{
    protected static ?string $model = KitchenStockMovement::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-arrow-path';

    protected static string|\UnitEnum|null $navigationGroup = 'Restaurant';

    protected static ?string $navigationLabel = 'Stock Movements';

    protected static ?int $navigationSort = 71;

    public static function canViewAny(): bool
    {
        return auth()->user()?->can('view kitchen stock movements') ?? false;
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function canEdit(Model $record): bool
    {
        return false;
    }

    public static function canDelete(Model $record): bool
    {
        return false;
    }

    public static function table(Table $table): Table
    {
        return KitchenStockMovementsTable::configure($table);
    }

    public static function getPages(): array
    {
        return ['index' => ListKitchenStockMovements::route('/')];
    }
}
