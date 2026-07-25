<?php

namespace App\Filament\Admin\Resources\ConferenceRooms;

use App\Filament\Admin\Resources\ConferenceRooms\Pages\CreateConferenceRoom;
use App\Filament\Admin\Resources\ConferenceRooms\Pages\EditConferenceRoom;
use App\Filament\Admin\Resources\ConferenceRooms\Pages\ListConferenceRooms;
use App\Filament\Admin\Resources\ConferenceRooms\Schemas\ConferenceRoomForm;
use App\Filament\Admin\Resources\ConferenceRooms\Tables\ConferenceRoomsTable;
use App\Models\ConferenceRoom;
use BackedEnum;
use Illuminate\Database\Eloquent\Builder;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class ConferenceRoomResource extends Resource
{
    protected static ?string $model = ConferenceRoom::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    protected static ?string $recordTitleAttribute = 'name';

    protected static string|\UnitEnum|null $navigationGroup = 'Conference';

    protected static ?int $navigationSort = 10;

    public static function form(Schema $schema): Schema
    {
        return ConferenceRoomForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return ConferenceRoomsTable::configure($table);
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->visibleTo(auth()->user());
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
            'index' => ListConferenceRooms::route('/'),
            'create' => CreateConferenceRoom::route('/create'),
            'edit' => EditConferenceRoom::route('/{record}/edit'),
        ];
    }
}
