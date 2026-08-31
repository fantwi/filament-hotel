<?php

namespace App\Filament\Admin\Resources\ConferenceFacilities;

use App\Filament\Admin\Resources\ConferenceFacilities\Pages\CreateConferenceFacility;
use App\Filament\Admin\Resources\ConferenceFacilities\Pages\EditConferenceFacility;
use App\Filament\Admin\Resources\ConferenceFacilities\Pages\ListConferenceFacilities;
use App\Filament\Admin\Resources\ConferenceFacilities\Schemas\ConferenceFacilityForm;
use App\Filament\Admin\Resources\ConferenceFacilities\Tables\ConferenceFacilitiesTable;
use App\Filament\Admin\Resources\ContentResource;
use App\Models\ConferenceFacility;
use BackedEnum;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

/**
 * Configures Filament administration for conference facility resource.
 */
class ConferenceFacilityResource extends ContentResource
{
    protected static ?string $model = ConferenceFacility::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedWrenchScrewdriver;

    protected static ?string $recordTitleAttribute = 'name';

    protected static string|\UnitEnum|null $navigationGroup = 'Conferences';

    protected static ?int $navigationSort = 20;

    /**
     * Configures the form schema and input behavior.
     */
    public static function form(Schema $schema): Schema
    {
        return ConferenceFacilityForm::configure($schema);
    }

    /**
     * Configures the table data source, columns, and actions.
     */
    public static function table(Table $table): Table
    {
        return ConferenceFacilitiesTable::configure($table);
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
            'index' => ListConferenceFacilities::route('/'),
            'create' => CreateConferenceFacility::route('/create'),
            'edit' => EditConferenceFacility::route('/{record}/edit'),
        ];
    }
}
