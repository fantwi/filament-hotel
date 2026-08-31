<?php

namespace App\Filament\Admin\Resources\ConferenceRooms;

use App\Filament\Admin\Resources\ConferenceRooms\Pages\CreateConferenceRoom;
use App\Filament\Admin\Resources\ConferenceRooms\Pages\EditConferenceRoom;
use App\Filament\Admin\Resources\ConferenceRooms\Pages\ListConferenceRooms;
use App\Filament\Admin\Resources\ConferenceRooms\Schemas\ConferenceRoomForm;
use App\Filament\Admin\Resources\ConferenceRooms\Tables\ConferenceRoomsTable;
use App\Filament\Admin\Resources\ContentResource;
use App\Models\ConferenceRoom;
use BackedEnum;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

/**
 * Configures Filament administration for conference room resource.
 */
class ConferenceRoomResource extends ContentResource
{
    protected static ?string $model = ConferenceRoom::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedPresentationChartBar;

    protected static ?string $recordTitleAttribute = 'name';

    protected static string|\UnitEnum|null $navigationGroup = 'Conferences';

    protected static ?int $navigationSort = 10;

    /**
     * Configures the form schema and input behavior.
     */
    public static function form(Schema $schema): Schema
    {
        return ConferenceRoomForm::configure($schema);
    }

    /**
     * Configures the table data source, columns, and actions.
     */
    public static function table(Table $table): Table
    {
        return ConferenceRoomsTable::configure($table);
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
            'index' => ListConferenceRooms::route('/'),
            'create' => CreateConferenceRoom::route('/create'),
            'edit' => EditConferenceRoom::route('/{record}/edit'),
        ];
    }
}
