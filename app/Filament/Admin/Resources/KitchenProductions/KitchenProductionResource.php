<?php

namespace App\Filament\Admin\Resources\KitchenProductions;

use App\Filament\Admin\Resources\KitchenProductions\Pages\CreateKitchenProduction;
use App\Filament\Admin\Resources\KitchenProductions\Pages\EditKitchenProduction;
use App\Filament\Admin\Resources\KitchenProductions\Pages\ListKitchenProductions;
use App\Filament\Admin\Resources\KitchenProductions\Pages\ViewKitchenProduction;
use App\Filament\Admin\Resources\KitchenProductions\Schemas\KitchenProductionForm;
use App\Filament\Admin\Resources\KitchenProductions\Schemas\KitchenProductionInfolist;
use App\Filament\Admin\Resources\KitchenProductions\Tables\KitchenProductionsTable;
use App\Filament\Admin\Resources\SecureResource;
use App\Models\KitchenProduction;
use BackedEnum;
use Filament\Schemas\Schema;
use Filament\Tables\Table;

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
        return KitchenProductionsTable::configure($table);
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
