<?php

namespace App\Filament\Admin\Resources\RestaurantTables\Tables;

use App\Models\RestaurantTable;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Notifications\Notification;
use Illuminate\Support\Str;
use Filament\Tables\Table;
use Filament\Tables\Columns\TextColumn;

class RestaurantTablesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('table_number'),
                TextColumn::make('restaurant.name'),
                TextColumn::make('capacity'),
                TextColumn::make('location'),
                TextColumn::make('status')
                    ->badge()
                    ->color(fn ($state) => match ($state) {
                        'available' => 'success',
                        'reserved' => 'warning',
                        'occupied' => 'danger',
                        'cleaning' => 'info',
                        'maintenance' => 'gray',
                        default => 'secondary',
                    })
            ])
            ->filters([
                //
            ])
            ->recordActions([
                ActionGroup::make([
                    EditAction::make(),
                    Action::make('print_qr')
                        ->label('Print QR Code')
                        ->icon('heroicon-o-qr-code')
                        ->color('success')
                        ->visible(fn (RestaurantTable $record): bool => filled($record->qr_token))
                        ->url(fn (RestaurantTable $record): string => route('restaurant.tables.qr.print', $record))
                        ->openUrlInNewTab(),
                    Action::make('regenerate_qr_token')
                        ->label('Regenerate QR Code')
                        ->icon('heroicon-o-arrow-path')
                        ->color('warning')
                        ->requiresConfirmation()
                        ->modalHeading('Regenerate Table QR Code')
                        ->modalDescription('The existing printed QR code will stop working. You must print and replace it.')
                        ->action(function (RestaurantTable $record): void {
                            $record->update(['qr_token' => Str::random(48)]);
                            Notification::make()->title('QR code regenerated')
                                ->body("Print a new QR code for table {$record->table_number}.")->warning()->send();
                        }),
                ])->label('Actions')->button(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
