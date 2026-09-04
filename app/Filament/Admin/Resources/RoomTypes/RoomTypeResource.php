<?php

namespace App\Filament\Admin\Resources\RoomTypes;

use App\Filament\Admin\Resources\RoomTypes\Pages\CreateRoomType;
use App\Filament\Admin\Resources\RoomTypes\Pages\EditRoomType;
use App\Filament\Admin\Resources\RoomTypes\Pages\ListRoomTypes;
use App\Filament\Admin\Resources\RoomTypes\Pages\ViewRoomType;
use App\Filament\Admin\Resources\RoomTypes\Schemas\RoomTypeForm;
use App\Filament\Admin\Resources\RoomTypes\Schemas\RoomTypeInfolist;
use App\Filament\Admin\Resources\RoomTypes\Tables\RoomTypesTable;
use App\Filament\Admin\Resources\SecureResource;
use App\Models\RoomType;
use BackedEnum;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

// use Filament\Forms\Components\FileUpload;

/**
 * Configures Filament administration for room type resource.
 */
class RoomTypeResource extends SecureResource
{
    protected static ?string $model = RoomType::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleGroup;

    protected static ?string $recordTitleAttribute = 'name';

    protected static string|\UnitEnum|null $navigationGroup = 'Accommodation';

    protected static ?int $navigationSort = 10;

    /**
     * Controls whether this feature appears in the Filament navigation.
     */
    public static function shouldRegisterNavigation(): bool
    {
        return auth()->user()?->hasAnyRole([
            'super_admin',
            'admin',
            'receptionist',
        ]);
    }

    /**
     * Determines whether the current user may view these records.
     */
    public static function canViewAny(): bool
    {
        return auth()->user()?->hasAnyRole([
            'super_admin',
            'admin',
            'receptionist',
        ]);
    }

    /**
     * Determines whether the current user may create records.
     */
    public static function canCreate(): bool
    {
        return auth()->user()?->hasAnyRole(['super_admin', 'admin']) ?? false;
    }

    /**
     * Determines whether the current user may edit the supplied record.
     */
    public static function canEdit($record): bool
    {
        return auth()->user()?->hasAnyRole(['super_admin', 'admin']) ?? false;
    }

    /**
     * Determines whether the current user may delete the supplied record.
     */
    public static function canDelete($record): bool
    {
        return (auth()->user()?->hasAnyRole(['super_admin', 'admin']) ?? false)
            && ! $record->rooms()->exists();
    }

    /**
     * Determines whether the current user may delete these records.
     */
    public static function canDeleteAny(): bool
    {
        return auth()->user()?->hasAnyRole(['super_admin', 'admin']) ?? false;
    }

    /**
     * Configures the form schema and input behavior.
     */
    public static function form(Schema $schema): Schema
    {
        return RoomTypeForm::configure($schema);
    }

    /**
     * Configures the read-only record details shown in the admin panel.
     */
    public static function infolist(Schema $schema): Schema
    {
        return RoomTypeInfolist::configure($schema);
    }

    /**
     * Configures the table data source, columns, and actions.
     */
    public static function table(Table $table): Table
    {
        return RoomTypesTable::configure($table);
    }

    /**
     * Builds and returns eloquent query.
     */
    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->visibleTo(auth()->user());
    }

    /**
     * Builds and returns relations.
     */
    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    /**
     * Builds and returns pages.
     */
    public static function getPages(): array
    {
        return [
            'index' => ListRoomTypes::route('/'),
            'create' => CreateRoomType::route('/create'),
            'view' => ViewRoomType::route('/{record}'),
            'edit' => EditRoomType::route('/{record}/edit'),
        ];
    }
}
