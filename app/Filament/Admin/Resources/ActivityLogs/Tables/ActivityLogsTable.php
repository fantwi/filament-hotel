<?php

namespace App\Filament\Admin\Resources\ActivityLogs\Tables;

use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\ViewAction;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\Filter;
use Filament\Forms\Components\DatePicker;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Support\Icons\Heroicon;
use Illuminate\Database\Eloquent\Builder;
use pxlrbt\FilamentExcel\Actions\Tables\ExportAction;
use pxlrbt\FilamentExcel\Actions\Tables\ExportBulkAction;
use App\Models\User;

class ActivityLogsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                //
                BadgeColumn::make('description')
                    ->label('Activity')
                    ->colors([
                        'success' => 'created',
                        'warning' => 'updated',
                        'danger' => 'deleted',
                    ])
                    ->icons([
                        'heroicon-o-plus-circle' => 'created',
                        'heroicon-o-pencil' => 'updated',
                        'heroicon-o-trash' => 'deleted',
                    ]),

                TextColumn::make('causer.name')
                    ->label('User'),

                TextColumn::make('subject_type')
                    ->label('Model')
                    ->formatStateUsing(fn ($state) => class_basename($state)),

                TextColumn::make('created_at')
                    ->dateTime()
                    ->label('Time')
                    ->sortable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                //
                SelectFilter::make('causer_id')
                    ->label('User')
                    ->options(User::pluck('name', 'id'))
                    ->searchable(),

                SelectFilter::make('subject_type')
                    ->label('Model')
                    ->options([
                        'App\Models\Booking' => 'Booking',
                        'App\Models\Payment' => 'Payment',
                        'App\Models\Guest' => 'Guest',
                    ]),

                Filter::make('created_at')
                    ->form([
                        DatePicker::make('from')
                            ->label('From'),

                        DatePicker::make('until')
                            ->label('Until'),
                    ])
                    ->query(function (Builder $query, array $data) {

                        return $query
                            ->when(
                                $data['from'],
                                fn (Builder $query, $date) => $query->whereDate('created_at', '>=', $date)
                            )
                            ->when(
                                $data['until'],
                                fn (Builder $query, $date) => $query->whereDate('created_at', '<=', $date)
                            );
                    }),
            ])
            ->headerActions([
                ExportAction::make()
                    ->label('Export Logs')
                    ->fileName(fn () => 'activity_logs_' . now()->format('Y_m_d_H_i')),
            ])
            ->recordActions([])
            ->toolbarActions([])
            ->bulkActions([]);
    }
}
