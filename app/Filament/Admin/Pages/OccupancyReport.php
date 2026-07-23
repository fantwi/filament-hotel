<?php

namespace App\Filament\Admin\Pages;

use App\Models\ConferenceRoom;
use App\Models\RestaurantTable;
use App\Models\Room;
use Filament\Pages\Page;

class OccupancyReport extends Page
{
    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-building-office-2';

    protected static string|\UnitEnum|null $navigationGroup = 'Reports';

    protected static ?string $navigationLabel = 'Occupancy Report';

    protected string $view = 'filament.admin.pages.occupancy-report';

    public function report(): array
    {
        return [
            'occupiedRooms' => Room::where('status', 'occupied')->count(),
            'availableRooms' => Room::where('status', 'available')->count(),
            'maintenanceRooms' => Room::where('status', 'maintenance')->count(),
            'availableConferenceRooms' => ConferenceRoom::where('is_available', true)->count(),
            'unavailableConferenceRooms' => ConferenceRoom::where('is_available', false)->count(),
            'reservedTables' => RestaurantTable::where('status', 'reserved')->count(),
            'occupiedTables' => RestaurantTable::where('status', 'occupied')->count(),
            'availableTables' => RestaurantTable::where('status', 'available')->count(),
        ];
    }

    public static function canAccess(): bool
    {
        return auth()->user()?->hasAnyRole(['super_admin', 'admin', 'manager', 'receptionist']) ?? false;
    }
}
