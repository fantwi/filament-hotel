<?php

namespace App\Filament\Admin\Resources\KitchenStockMovements;

use App\Filament\Admin\Resources\KitchenStockMovements\Pages\ListKitchenStockMovements;
use App\Filament\Admin\Resources\KitchenStockMovements\Tables\KitchenStockMovementsTable;
use App\Filament\Admin\Resources\SecureResource;
use App\Models\KitchenStockMovement;
use BackedEnum;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;

/**
 * Configures Filament administration for kitchen stock movement resource.
 */
class KitchenStockMovementResource extends SecureResource
{
    protected static ?string $model = KitchenStockMovement::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-arrow-path';

    protected static string|\UnitEnum|null $navigationGroup = 'Restaurant';

    protected static ?string $navigationLabel = 'Stock Movements';

    protected static ?int $navigationSort = 71;

    /**
     * Determines whether the current user may view these records.
     */
    public static function canViewAny(): bool
    {
        return auth()->user()?->can('view kitchen stock movements') ?? false;
    }

    /**
     * Determines whether the current user may create records.
     */
    public static function canCreate(): bool
    {
        return false;
    }

    /**
     * Determines whether the current user may edit the supplied record.
     */
    public static function canEdit(Model $record): bool
    {
        return false;
    }

    /**
     * Determines whether the current user may delete the supplied record.
     */
    public static function canDelete(Model $record): bool
    {
        return false;
    }

    /**
     * Configures the table data source, columns, and actions.
     */
    public static function table(Table $table): Table
    {
        return KitchenStockMovementsTable::configure($table);
    }

    /**
     * Builds and returns pages.
     */
    public static function getPages(): array
    {
        return ['index' => ListKitchenStockMovements::route('/')];
    }
}
