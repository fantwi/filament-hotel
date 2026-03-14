<?php

namespace App\Filament\Admin\Resources\Bookings\Tables;

use Filament\Tables\Table;
use Filament\Tables\Columns\TextColumn;

use Filament\Actions\Action;
use Filament\Actions\ViewAction;
use Filament\Actions\EditAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;

use Filament\Notifications\Notification;

use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Select;

use App\Models\Payment;
use App\Services\InvoiceService;


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
                        $record->check_in->diffInDays($record->check_out)
                        // \Illuminate\Support\Carbon::parse($record->check_in)
                        //     ->diffInDays($record->check_out)
                    ),

                TextColumn::make('total_price')
                    ->label('Price')
                    ->money('GHS')
                    ->sortable(),

                TextColumn::make('total_paid')
                    ->label('Paid')
                    ->money('GHS')
                    ->color('success'),

                TextColumn::make('balance')
                    ->label('Balance')
                    ->money('GHS')
                    ->color(fn ($record) => $record->balance > 0 ? 'danger' : 'success'),

                TextColumn::make('status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'pending'  => 'warning',
                        'checked_in' => 'success',
                        'checked_out' => 'gray',
                    }),

                TextColumn::make('payment_status')
                    ->badge()
                    ->color(fn ($state) => match ($state) {
                        'paid' => 'success',
                        'partial' => 'warning',
                        'unpaid' => 'danger',
                    }),
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
                            ->performedOn($record)
                            ->causedBy(auth()->user())
                            ->log("Checked in guest {$record->guest->full_name} to room {$record->room->room_number}");
                    }),

                Action::make('check_out')
                    ->label('Check Out')
                    ->icon('heroicon-o-arrow-left-on-rectangle')
                    ->color('danger')
                    ->visible(fn ($record) => $record->status === 'checked_in')
                    ->requiresConfirmation()
                    ->disabled(fn ($record) => $record->balance > 0)
                    ->tooltip(fn ($record) => $record->balance > 0 
                        ? 'Guest still has an unpaid balance'
                        : null)
                    ->action(function ($record) {

                        // Prevent checkout if balance exists
                        if ($record->balance > 0) {
                            Notification::make()
                                ->title('Checkout not allowed')
                                ->body('Guest still has an outstanding balance.')
                                ->danger()
                                ->send();

                            return;
                        }

                        // Update booking status
                        $invoiceNumber = InvoiceService::generateInvoiceNumber();

                        $record->update([
                            'status' => 'checked_out',
                            'invoice_number' => $invoiceNumber,
                        ]);

                        // Update room room
                        $record->room->update([
                            'status' => 'available',
                        ]);

                        // Log activity
                        activity()
                            ->performedOn($record)
                            ->causedBy(auth()->user())
                            ->log("Checked out guest {$record->guest->full_name} from room {$record->room->room_number}");

                        // Success notification
                        Notification::make()
                            ->title('Guest Checked Out')
                            ->success()
                            ->send();

                        // Generate invoice
                        InvoiceService::generate($record);
                        // return InvoiceService::generate($record);
                        // return redirect()->to(
                        //     asset('storage/invoices/invoice-booking-'.$record->id.'.pdf')
                        // );
                    }), 
                    
                Action::make('pay')
                    ->label('Pay')
                    ->icon('heroicon-o-banknotes')
                    ->color('success')
                    ->visible(fn ($record) => 
                        $record->status !== 'checked_out' && $record->balance > 0
                    )
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

                        activity()
                            ->causedBy(auth()->user())
                            ->performedOn($record)
                            ->log('Payment received: GHS '.$data['amount']);

                        Notification::make()
                            ->title('Payment Recorded')
                            ->success()
                            ->send();
                    })
                    ->successNotificationTitle('Payment Recorded')
                    ->after(fn () => $this->dispatch('refresh')),
                    // ->after(fn () => redirect(request()->header('Referer'))),

                Action::make('invoice')
                    ->label('Invoice')
                    ->icon('heroicon-o-document-arrow-down')
                    ->color('gray')
                    ->visible(fn ($record) => 
                        $record->status === 'checked_out' && $record->invoice_number
                    )
                    ->action(function ($record) {

                        if (!$record->invoice_number) {

                            $record->update([
                                'invoice_number' => InvoiceService::generateInvoiceNumber()
                            ]);

                            InvoiceService::generate($record);
                        }
                    })
                    ->url(fn ($record) =>
                        asset('storage/invoices/'.$record->invoice_number.'.pdf')
                    )
                    ->openUrlInNewTab(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
 