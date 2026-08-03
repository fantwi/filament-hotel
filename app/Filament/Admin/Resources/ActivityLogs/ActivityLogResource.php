<?php

namespace App\Filament\Admin\Resources\ActivityLogs;

use App\Filament\Admin\Resources\ActivityLogs\Pages\ListActivityLogs;
use App\Filament\Admin\Resources\ActivityLogs\Tables\ActivityLogsTable;
// use App\Filament\Admin\Resources\ActivityLogs\Schemas\ActivityLogForm;
use BackedEnum;
// use App\Models\ActivityLog;
use Filament\Resources\Resource;
use Filament\Support\Icons\Heroicon;
// use Filament\Schemas\Schema;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Spatie\Activitylog\Models\Activity;

class ActivityLogResource extends Resource
{
    protected static ?string $model = Activity::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedDocumentText;

    protected static ?string $navigationLabel = 'Activity Logs';

    protected static string|\UnitEnum|null $navigationGroup = 'System';

    protected static ?int $navigationSort = 20;

    protected static ?string $pluralModelLabel = 'Activity Logs';

    // public static function form(Schema $schema): Schema
    // {
    //     return ActivityLogForm::configure($schema);
    // }

    public static function shouldRegisterNavigation(): bool
    {
        return auth()->user()?->hasAnyRole([
            'super_admin',
            'admin',
        ]);
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->with(['causer', 'subject'])
            ->latest('created_at');
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

    public static function formatChanges($record): string
    {
        $old = $record->properties['old'] ?? [];
        $new = $record->properties['attributes'] ?? [];

        $output = '';

        foreach ($new as $field => $value) {

            $oldValue = $old[$field] ?? '—';
            $newValue = $value;

            if ($oldValue != $newValue) {

                $output .= strtoupper($field)."\n";
                $output .= "Old: {$oldValue}\n";
                $output .= "New: {$newValue}\n\n";
            }
        }

        return $output ?: 'No changes recorded';
    }


}
