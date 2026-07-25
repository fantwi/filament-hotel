<?php

namespace App\Filament\Admin\Resources\ConferenceFacilities;

use App\Filament\Admin\Resources\ConferenceFacilities\Pages\CreateConferenceFacility;
use App\Filament\Admin\Resources\ConferenceFacilities\Pages\EditConferenceFacility;
use App\Filament\Admin\Resources\ConferenceFacilities\Pages\ListConferenceFacilities;
use App\Filament\Admin\Resources\ConferenceFacilities\Schemas\ConferenceFacilityForm;
use App\Filament\Admin\Resources\ConferenceFacilities\Tables\ConferenceFacilitiesTable;
use App\Models\ConferenceFacility;
use BackedEnum;
use Illuminate\Database\Eloquent\Builder;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class ConferenceFacilityResource extends Resource
{
    protected static ?string $model = ConferenceFacility::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    protected static ?string $recordTitleAttribute = 'name';

    protected static string|\UnitEnum|null $navigationGroup = 'Conference';

    protected static ?int $navigationSort = 20;

    public static function form(Schema $schema): Schema
    {
        return ConferenceFacilityForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return ConferenceFacilitiesTable::configure($table);
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
            'index' => ListConferenceFacilities::route('/'),
            'create' => CreateConferenceFacility::route('/create'),
            'edit' => EditConferenceFacility::route('/{record}/edit'),
        ];
    }
}
