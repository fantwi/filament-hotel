<?php

namespace App\Filament\Admin\Resources\ActivityLogs\Tables;

use App\Filament\Exports\ActivityLogExporter;
use Filament\Actions\ExportAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class ActivityLogsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('id')
                    ->label('Log ID')
                    ->sortable(),

                TextColumn::make('causer.name')
                    ->label('User')
                    ->formatStateUsing(
                        fn ($state, $record) => $state
                            ?? $record
                                ->subject?->name
                            ?? 'System'
                    )
                    ->searchable(),

                TextColumn::make('description')
                    ->badge()
                    ->color(fn ($state) => match ($state) {
                        'created' => 'success',
                        'updated' => 'warning',
                        'deleted' => 'danger',
                        'checked_in' => 'primary',
                        'checked_out' => 'gray',
                        'payment_added' => 'success',
                        'User logged in' => 'success',
                        'User logged out' => 'danger',
                        default => 'secondary',
                    }),

                // TextColumn::make('subject_type')
                //     ->label('Model')
                //     ->formatStateUsing(fn ($state) => class_basename($state)),

                TextColumn::make('created_at')
                    ->label('Date & Time')
                    // ->since()
                    ->dateTime('M d, Y H:i')
                    ->sortable(),

                // TextColumn::make('properties.ip_address')
                //     ->label('IP Address'),
            ])

            ->filters([
                SelectFilter::make('description')
                    ->options([
                        'created' => 'Created',
                        'updated' => 'Updated',
                        'deleted' => 'Deleted',
                    ]),

                Filter::make('today')
                    ->query(fn ($query) => $query->whereDate('created_at', today())
                    ),
            ])

            ->recordActions([
                ViewAction::make(),
            ])

            ->toolbarActions([
                ExportAction::make()
                    ->exporter(ActivityLogExporter::class)
                    ->fileName(fn () => 'activity_logs_'.now()->format('Y-m-d_H-i-s')
                    ),
            ]);
    }
}
