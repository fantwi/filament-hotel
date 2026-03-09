<?php

namespace App\Filament\Admin\Resources\ActivityLogs;

use Spatie\Activitylog\Models\Activity;
use App\Filament\Admin\Resources\ActivityLogs\Pages\ListActivityLogs;
// use App\Filament\Admin\Resources\ActivityLogs\Schemas\ActivityLogForm;
use App\Filament\Admin\Resources\ActivityLogs\Tables\ActivityLogsTable;
// use App\Models\ActivityLog;
use BackedEnum;
use Filament\Resources\Resource;
// use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class ActivityLogResource extends Resource
{
    // use Spatie\Activitylog\Models\Activity;

    protected static ?string $model = Activity::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedDocumentText;

    protected static ?string $navigationLabel = 'Activity Logs';

    protected static ?string $pluralModelLabel = 'Activity Logs';

    // public static function form(Schema $schema): Schema
    // {
    //     return ActivityLogForm::configure($schema);
    // }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->with(['causer', 'subject']);
    }

    public static function table(Table $table): Table
    {
        return ActivityLogsTable::configure($table);
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
            'index' => ListActivityLogs::route('/'),
            // 'create' => CreateActivityLog::route('/create'),
            // 'edit' => EditActivityLog::route('/{record}/edit'),
        ];
    }
}
