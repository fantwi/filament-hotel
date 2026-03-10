<?php

namespace App\Filament\Exports;

use App\Models\ActivityLog;
use App\Models\Activity;
use Filament\Actions\Exports\ExportColumn;
use Filament\Actions\Exports\Exporter;
use Filament\Actions\Exports\Models\Export;
use Illuminate\Support\Number;

class ActivityLogExporter extends Exporter
{
    protected static ?string $model = Activity::class;

    public static function getColumns(): array
    {
        return [
            //
            ExportColumn::make('id')
                ->label('Log ID'),

            ExportColumn::make('causer.name')
                ->label('User'),

            ExportColumn::make('description')
                ->label('Action'),

            ExportColumn::make('subject_type')
                ->label('Model'),

            ExportColumn::make('properties.ip_address')
                ->label('IP Address'),

            ExportColumn::make('created_at')
                ->label('Date & Time')
                ->formatStateUsing(fn ($state) =>
                    $state?->format('Y-m-d H:i:s')
                ),
        ];
    }

    public static function getCompletedNotificationBody(Export $export): string
    {
        $body = 'Your activity log export has completed and ' . Number::format($export->successful_rows) . ' ' . str('row')->plural($export->successful_rows) . ' exported.';

        if ($failedRowsCount = $export->getFailedRowsCount()) {
            $body .= ' ' . Number::format($failedRowsCount) . ' ' . str('row')->plural($failedRowsCount) . ' failed to export.';
        }

        return $body;
    }
}
