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

    public static function formatChanges($record): string
    {
        $old = $record->properties['old'] ?? [];
        $new = $record->properties['attributes'] ?? [];

        $output = '';

        foreach ($new as $field => $value) {

            $oldValue = $old[$field] ?? '—';
            $newValue = $value;

            if ($oldValue != $newValue) {

                $output .= strtoupper($field) . "\n";
                $output .= "Old: {$oldValue}\n";
                $output .= "New: {$newValue}\n\n";
            }
        }

        return $output ?: 'No changes recorded';
    }

    public static function generateDiff($record): string
    {
        $old = $record->properties['old'] ?? [];
        $new = $record->properties['attributes'] ?? [];

        $html = '';

        foreach ($new as $field => $newValue) {

            $oldValue = $old[$field] ?? null;

            if ($oldValue != $newValue) {

                $label = str_replace('_', ' ', ucfirst($field));

                $html .= "
                <div class='mb-4'>
                    <div class='font-bold text-gray-700'>{$label}</div>

                    <div class='bg-red-50 text-red-700 px-3 py-1 rounded'>
                        - {$oldValue}
                    </div>

                    <div class='bg-green-50 text-green-700 px-3 py-1 rounded mt-1'>
                        + {$newValue}
                    </div>
                </div>";
            }
        }

        return $html ?: '<span class="text-gray-500">No changes recorded</span>';
    }
}
