<?php

namespace App\Filament\Admin\Resources\Rooms;

use App\Filament\Admin\Resources\Rooms\Pages\CreateRoom;
use App\Filament\Admin\Resources\Rooms\Pages\EditRoom;
use App\Filament\Admin\Resources\Rooms\Pages\ListRooms;
use App\Filament\Admin\Resources\Rooms\Pages\ViewRoom;
use App\Filament\Admin\Resources\Rooms\Schemas\RoomForm;
use App\Filament\Admin\Resources\Rooms\Schemas\RoomInfolist;
use App\Filament\Admin\Resources\Rooms\Tables\RoomsTable;
use App\Models\Room;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class RoomResource extends Resource
{
    protected static string|\UnitEnum|null $navigationGroup = 'Accommodation';

    protected static ?int $navigationSort = 20;

    protected static ?string $model = Room::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    protected static ?string $recordTitleAttribute = 'room_number';

    // Admins and receptionists can view Rooms in the navigation
    public static function shouldRegisterNavigation(): bool
    {
        return auth()->user()?->hasAnyRole([
            'super_admin',
            'admin',
            'manager',
            'receptionist',
        ]);
        // return false;
    }

    // Admins and receptionists can view rooms
    public static function canViewAny(): bool
    {
        return auth()->user()?->hasAnyRole([
            'super_admin',
            'admin',
            'receptionist',
        ]);
    }

    // public static function canView($record): bool
    // {
    //     return auth()->user()?->hasAnyRole([
    //         'super_admin',
    //         'admin',
    //         'receptionist',
    //     ]);
    // }

    // Only admins can create rooms
    public static function canCreate(): bool
    {
        return auth()->user()?->hasRole('admin');
    }

    // Only admins can edit rooms
    public static function canEdit($record): bool
    {
        return auth()->user()?->hasRole('admin');
    }

    // Only admins can delete rooms
    public static function canDelete($record): bool
    {
        return auth()->user()?->hasRole('admin');
    }

    public static function form(Schema $schema): Schema
    {
        return RoomForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return RoomInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return RoomsTable::configure($table);
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
            'index' => ListRooms::route('/'),
            'create' => CreateRoom::route('/create'),
            'view' => ViewRoom::route('/{record}'),
            'edit' => EditRoom::route('/{record}/edit'),
        ];
    }

    public function index()
    {
        $rooms = Room::where('status', 'available')->get();

        return view('rooms.index', compact('rooms'));
    }
}
