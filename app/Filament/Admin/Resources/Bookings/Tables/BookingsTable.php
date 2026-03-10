<?php

namespace App\Filament\Admin\Resources\Bookings\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Tables\Columns\TextColumn;
use Filament\Notifications\Notification;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Select;
use App\Models\Payment;
use Spatie\Activitylog\Models\Activity;


class BookingsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                //
                TextColumn::make('id')
                    ->label('Booking ID')
                    ->sortable(),

                TextColumn::make('guest.full_name')
                    ->label('Guest')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('room.room_number')
                    ->label('Room')
                    ->sortable(),

                TextColumn::make('check_in')
                    ->label('Check In')
                    ->date()
                    ->sortable(),

                TextColumn::make('check_out')
                    ->label('Check Out')
                    ->date()
                    ->sortable(),

                TextColumn::make('nights')
                    ->label('Nights')
                    ->state(fn ($record) =>
                        \Carbon\Carbon::parse($record->check_in)
                            ->diffInDays($record->check_out)
                    ),

                TextColumn::make('total_price')
                    ->label('Total Price')
                    ->money('GHS')
                    ->sortable(),

                TextColumn::make('total_paid')
                    ->label('Total Paid')
                    ->money('GHS')
                    ->color('success'),

                TextColumn::make('balance')
                    ->label('Balance')
                    ->money('GHS')
                    ->color(fn ($record) => $record->balance > 0 ? 'danger' : 'success'),

                TextColumn::make('status')
                    ->badge()
                    ->color(fn (String $state): string => match ($state) {
                        'pending'  => 'warning',
                        'checked_in' => 'success',
                        'checked_out' => 'gray',
                    })
                    // ->colors([
                    //     'warning' => 'pending',
                    //     'primary' => 'checked_in',
                    //     'success' => 'checked_out',
                    // ]),



                // TextColumn::make('status')
                //     ->badge()
                //     ->color(fn (String $state): string => match ($state) {                        arning
                //         'pending'  => 'warning',
                //         'checked_in' => 'success',
                //         'checked_out' => 'gray',
                //     })
            ])
            ->filters([
                //
            ])
            ->recordActions([
                ViewAction::make(),
                EditAction::make()
                    ->visible(fn ($record) => $record->status === 'pending'),
                
                Action::make('check_in')
                    ->label('Check In')
                    ->color('success')
                    ->icon('heroicon-o-arrow-right-on-rectangle')
                    ->visible(fn ($record) => $record->status === 'pending')
                    ->action(function ($record) {
                        $record->update([
                            'status' => 'checked_in',
                        ]);

                        $record->room->update([
                            'status' => 'occupied',
                        ]);

                        activity()
                            ->performedOn($booking)
                            ->causedBy(auth()->user())
                            ->log("Checked in guest {$record->guest->full_name} to room {$record->room->room_number}");
                    }),

                Action::make('check_out')
                    ->label('Check Out')
                    ->icon('heroicon-o-arrow-left-on-rectangle')
                    ->color('danger')
                    ->visible(fn ($record) => $record->status === 'checked_in')
                    ->disabled(fn ($record) => $record->balance > 0)
                    ->tooltip(fn ($record) => $record->balance > 0 
                        ? 'Guest still has an unpaid balance'
                        : null)
                    ->action(function ($record) {

                        if ($record->balance > 0) {
                            Notification::make()
                                ->title('Outstanding Balance')
                                ->body('Guest must settle payment before checkout.')
                                ->danger()
                                ->send();

                            activity()
                                ->performedOn($booking)
                                ->causedBy(auth()->user())
                                ->log("Checked out guest {$record->guest->full_name} from room {$record->room->room_number}");

                            return;
                        }

                        $record->update([
                            'status' => 'checked_out',
                        ]);

                        $record->room->update([
                            'status' => 'available',
                        ]);

                        Notification::make()
                            ->title('Guest Checked Out')
                            ->success()
                            ->send();
                    }), 
                    
                Action::make('pay')
                    ->label('Pay')
                    ->icon('heroicon-o-banknotes')
                    ->color('success')
                    ->visible(fn ($record) => $record->balance > 0)
                    ->form([

                        TextInput::make('amount')
                            ->numeric()
                            ->required()
                            ->minValue(1)
                            ->maxValue(fn ($record) => $record->balance),

                        Select::make('method')
                            ->options([
                                'cash' => 'Cash',
                                'momo' => 'Mobile Money',
                                'card' => 'Card',
                            ])
                            ->required(),

                        TextInput::make('transaction_reference')
                            ->label('Reference')
                            ->placeholder('Optional'),

                    ])
                    ->action(function ($record, array $data) {

                        Payment::create([
                            'booking_id' => $record->id,
                            'amount' => $data['amount'],
                            'method' => $data['method'],
                            'transaction_reference' => $data['transaction_reference'] ?? null,
                        ]);

                        Notification::make()
                            ->title('Payment Recorded')
                            ->success()
                            ->send();

                        activity()
                            ->causedBy(auth()->user())
                            ->performedOn($record)
                            ->log('Payment received: GHS '.$data['amount']);
                    }),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
 