<?php

namespace App\Filament\Admin\Resources\RoomTypes;

use App\Filament\Admin\Resources\RoomTypes\Pages\CreateRoomType;
use App\Filament\Admin\Resources\RoomTypes\Pages\EditRoomType;
use App\Filament\Admin\Resources\RoomTypes\Pages\ListRoomTypes;
use App\Filament\Admin\Resources\RoomTypes\Pages\ViewRoomType;
use App\Filament\Admin\Resources\RoomTypes\Schemas\RoomTypeForm;
use App\Filament\Admin\Resources\RoomTypes\Schemas\RoomTypeInfolist;
use App\Filament\Admin\Resources\RoomTypes\Tables\RoomTypesTable;
use App\Models\RoomType;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
// use Filament\Forms\Components\FileUpload;

class RoomTypeResource extends Resource
{
    protected static ?string $model = RoomType::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    protected static ?string $recordTitleAttribute = 'name';

    public static function shouldRegisterNavigation(): bool
    {
        return auth()->user()?->hasAnyRole([
            'super_admin',
            'admin',
            'receptionist',
        ]);
    }

    public static function canViewAny(): bool
    {
        return auth()->user()?->hasAnyRole([
            'super_admin',
            'admin',
            'receptionist',
        ]);
    }

    public static function form(Schema $schema): Schema
    {
        return RoomTypeForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return RoomTypeInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return RoomTypesTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

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
