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
use App\Filament\Admin\Resources\SecureResource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

/**
 * Configures Filament administration for room resource.
 */
class RoomResource extends SecureResource
{
    protected static string|\UnitEnum|null $navigationGroup = 'Accommodation';

    protected static ?int $navigationSort = 20;

    protected static ?string $model = Room::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    protected static ?string $recordTitleAttribute = 'room_number';

    // Admins and receptionists can view Rooms in the navigation
    /**
     * Controls whether this feature appears in the Filament navigation.
     */
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

    // public static function canView($record): bool
    // {
    //     return auth()->user()?->hasAnyRole([
    //         'super_admin',
    //         'admin',
    //         'receptionist',
    //     ]);
    // }

    // Only admins can create rooms
    /**
     * Determines whether the current user may create records.
     */
    public static function canCreate(): bool
    {
        return auth()->user()?->hasRole('admin');
    }

    // Only admins can edit rooms
    /**
     * Determines whether the current user may edit the supplied record.
     */
    public static function canEdit($record): bool
    {
        return auth()->user()?->hasRole('admin');
    }

    // Only admins can delete rooms
    /**
     * Determines whether the current user may delete the supplied record.
     */
    public static function canDelete($record): bool
    {
        return auth()->user()?->hasRole('admin');
    }

    /**
     * Configures the form schema and input behavior.
     */
    public static function form(Schema $schema): Schema
    {
        return RoomForm::configure($schema);
    }

    /**
     * Configures the read-only record details shown in the admin panel.
     */
    public static function infolist(Schema $schema): Schema
    {
        return RoomInfolist::configure($schema);
    }

    /**
     * Configures the table data source, columns, and actions.
     */
    public static function table(Table $table): Table
    {
        return RoomsTable::configure($table);
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
            'index' => ListRooms::route('/'),
            'create' => CreateRoom::route('/create'),
            'view' => ViewRoom::route('/{record}'),
            'edit' => EditRoom::route('/{record}/edit'),
        ];
    }

    /**
     * Displays the index interface or response.
     */
    public function index()
    {
        $rooms = Room::where('status', 'available')->get();

        return view('rooms.index', compact('rooms'));
    }
}
