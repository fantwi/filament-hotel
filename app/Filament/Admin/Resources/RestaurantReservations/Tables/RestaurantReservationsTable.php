<?php

namespace App\Filament\Admin\Resources\RestaurantReservations\Tables;

use App\Mail\RestaurantReservationCancelled;
use App\Mail\RestaurantReservationConfirmed;
use App\Mail\RestaurantReservationRefunded;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Mail;

class RestaurantReservationsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                //
                TextColumn::make('id'),

                TextColumn::make('guest_name')
                    ->searchable(),

                TextColumn::make('table.table_number'),

                TextColumn::make('reservation_date')
                    ->date(),

                TextColumn::make('reservation_time'),

                TextColumn::make('number_of_guests'),

                TextColumn::make('status')
                    ->badge()
                    ->color(fn ($state) => match ($state) {

                        'pending' => 'warning',

                        'confirmed' => 'info',

                        'checked_in' => 'success',

                        'completed' => 'success',

                        'cancelled' => 'danger',

                        'no_show' => 'gray',

                        default => 'secondary',

                    }),

                TextColumn::make('payment_status')
                    ->badge()
                    ->color(fn ($state) => match ($state) {

                        'pending' => 'warning',

                        'partial' => 'info',

                        'completed' => 'success',

                        'cancelled' => 'danger',

                        'refunded' => 'gray',

                        default => 'secondary',

                    }),

                TextColumn::make('created_at')
                    ->since()
            ])
            ->filters([
                //
                SelectFilter::make('status')

                ->options([

                    'pending' => 'Pending',

                    'confirmed' => 'Confirmed',

                    'checked_in' => 'Checked In',

                    'completed' => 'Completed',

                    'cancelled' => 'Cancelled',

                    'no_show' => 'No Show',

                ]),

                SelectFilter::make('payment_status')

                    ->options([

                        'pending' => 'Pending',

                        'partial' => 'Partial',

                        'completed' => 'Completed',

                        'cancelled' => 'Cancelled',

                        'refunded' => 'Refunded',

                    ]),
            ])
            ->recordActions([
                ActionGroup::make([
                    ViewAction::make(),
                    EditAction::make(),

                    Action::make('confirm')
                        ->label('Confirm Reservation')
                        ->icon('heroicon-o-check-circle')
                        ->color('success')
                        ->visible(fn ($record) => $record->status === 'pending'
                            && $record->payment_status === 'completed')
                        ->requiresConfirmation()
                        ->action(function ($record): void {
                            $record->update([
                                'status' => 'confirmed',
                                'hold_status' => 'confirmed',
                                'hold_until' => null,
                            ]);

                            activity()
                                ->performedOn($record)
                                ->causedBy(auth()->user())
                                ->event('confirmed')
                                ->log('Restaurant reservation confirmed.');

                            Mail::to($record->guest_email)
                                ->send(new RestaurantReservationConfirmed($record));

                            Notification::make()
                                ->success()
                                ->title('Reservation confirmed.')
                                ->send();
                        }),

                    Action::make('walk_in')
                        ->label('Walk-in Check-In')
                        ->icon('heroicon-o-user-plus')
                        ->color('warning')
                        ->visible(fn ($record) => $record->status === 'pending')
                        ->requiresConfirmation()
                        ->action(function ($record): void {
                            $record->update([
                                'status' => 'checked_in',
                                'payment_status' => 'completed',
                                'hold_status' => 'confirmed',
                                'hold_until' => null,
                            ]);

                            activity()
                                ->performedOn($record)
                                ->causedBy(auth()->user())
                                ->event('checked_in')
                                ->log('Restaurant guest checked in.');

                            $record->table()->update(['status' => 'occupied']);

                            Notification::make()
                                ->success()
                                ->title('Guest checked in.')
                                ->send();
                        }),

                    Action::make('check_in')
                        ->label('Check-In')
                        ->icon('heroicon-o-arrow-right-circle')
                        ->visible(fn ($record) => $record->status === 'confirmed')
                        ->requiresConfirmation()
                        ->action(function ($record): void {
                            $record->update(['status' => 'checked_in']);

                            activity()
                                ->performedOn($record)
                                ->causedBy(auth()->user())
                                ->event('checked_in')
                                ->log('Restaurant guest checked in.');

                            $record->table()->update(['status' => 'occupied']);

                            Notification::make()
                                ->success()
                                ->title('Guest checked in.')
                                ->send();
                        }),

                    Action::make('complete')
                        ->label('Complete Reservation')
                        ->icon('heroicon-o-check-badge')
                        ->color('success')
                        ->visible(fn ($record) => $record->status === 'checked_in')
                        ->requiresConfirmation()
                        ->action(function ($record): void {
                            $record->update(['status' => 'completed']);

                            activity()
                                ->performedOn($record)
                                ->causedBy(auth()->user())
                                ->event('completed')
                                ->log('Restaurant reservation completed.');

                            $record->table()->update(['status' => 'available']);

                            Notification::make()
                                ->success()
                                ->title('Reservation completed.')
                                ->send();
                        }),

                    Action::make('no_show')
                        ->label('Mark No Show')
                        ->icon('heroicon-o-user-minus')
                        ->color('gray')
                        ->visible(fn ($record) => in_array($record->status, ['pending', 'confirmed'], true))
                        ->requiresConfirmation()
                        ->action(function ($record): void {
                            $record->update([
                                'status' => 'no_show',
                                'hold_status' => 'expired',
                                'hold_until' => null,
                            ]);

                            activity()
                                ->performedOn($record)
                                ->causedBy(auth()->user())
                                ->event('no_show')
                                ->log('Guest marked as no show.');

                            $record->table()->update(['status' => 'available']);

                            Notification::make()
                                ->success()
                                ->title('Reservation marked as no-show.')
                                ->send();
                        }),

                    Action::make('cancel')
                        ->label('Cancel Reservation')
                        ->icon('heroicon-o-x-circle')
                        ->color('danger')
                        ->visible(fn ($record) => ! in_array($record->status, ['completed', 'cancelled'], true))
                        ->requiresConfirmation()
                        ->action(function ($record): void {
                            $record->update([
                                'status' => 'cancelled',
                                'hold_status' => 'expired',
                                'hold_until' => null,
                            ]);

                            activity()
                                ->performedOn($record)
                                ->causedBy(auth()->user())
                                ->event('cancelled')
                                ->log('Restaurant reservation cancelled.');

                            Mail::to($record->guest_email)
                                ->send(new RestaurantReservationCancelled($record));

                            $record->table()->update(['status' => 'available']);

                            Notification::make()
                                ->success()
                                ->title('Reservation cancelled.')
                                ->send();
                        }),

                    Action::make('refund')
                        ->label('Refund Payment')
                        ->icon('heroicon-o-banknotes')
                        ->color('warning')
                        ->visible(fn ($record) => $record->payment_status === 'completed')
                        ->requiresConfirmation()
                        ->action(function ($record): void {
                            $record->update(['payment_status' => 'refunded']);

                            activity()
                                ->performedOn($record)
                                ->causedBy(auth()->user())
                                ->event('refund')
                                ->log('Restaurant payment refunded.');

                            Mail::to($record->guest_email)
                                ->send(new RestaurantReservationRefunded($record));

                            Notification::make()
                                ->success()
                                ->title('Payment marked as refunded.')
                                ->send();
                        }),

                    Action::make('print')
                        ->label('Print Reservation')
                        ->icon('heroicon-o-printer')
                        ->url(fn ($record): string => route('restaurant.reservations.print', $record))
                        ->openUrlInNewTab(),

                    DeleteAction::make(),
                ])
                    ->label('Action')
                    ->button(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
