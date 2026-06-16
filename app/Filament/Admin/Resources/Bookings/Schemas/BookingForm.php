<?php

namespace App\Filament\Admin\Resources\Bookings\Schemas;

use App\Models\Booking;
use App\Models\Room;
use Carbon\Carbon;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;
use Filament\Forms\Get;
use Filament\Forms\Set;

class BookingForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('guest_id')
                    ->relationship('user', 'name')
                    ->getOptionLabelFromRecordUsing(fn ($record) => $record->full_name)
                    ->searchable(['first_name', 'last_name'])
                    ->required(),

                // Select::make('guest_id')
                //     ->relationship('guest', 'first_name')
                //     ->getOptionLabelFromRecordUsing(fn ($record) => $record->full_name)
                //     ->searchable(['first_name', 'last_name'])
                //     ->required(),

                Select::make('room_id')
                    // ->relationship(
                    //     name: 'room',
                    //     titleAttribute: 'room_number',
                    //     modifyQueryUsing: fn ($query) => $query->where('status', '!=', 'maintenance')
                    // )
                    ->relationship('room', 'room_number')
                    ->default(
                        request('room_id')
                    )
                    ->searchable()
                    ->reactive()
                    ->required()
                    ->disabled(fn ($record) => $record?->status === 'checked_in') // disable the room selection if the status is "Check-in Now"
                    ->live() // to update the total price when the room is changed
                    ->afterStateUpdated(function ($state, $get, $set) {
                        $checkIn = $get('check_in');
                        $checkOut = $get('check_out');

                        if (! $state || ! $checkIn || ! $checkOut) {
                            return;
                        }

                        $room = Room::find($state);

                        if (! $room) {
                            return;
                        }

                        $days = Carbon::parse($checkIn)
                            ->diffInDays(Carbon::parse($checkOut));

                        $set('total_price', max($days, 1) * $room->roomType->price_per_night);
                    })
                    ->rule(function ($get, $record) {
                        return function (string $attribute, $value, \Closure $fail) use ($get, $record) {
                            $checkIn = $get('check_in');
                            $checkOut = $get('check_out');

                            if (! $checkIn || ! $checkOut) {
                                return;
                            }

                            $query = Booking::where('room_id', $value)
                                // ->overlapping($checkIn, $checkOut);
                                ->where(function ($q) use ($checkIn, $checkOut) {

                                    $q->whereBetween('check_in', [$checkIn, $checkOut])
                                      ->orWhereBetween('check_out', [$checkIn, $checkOut])
                                      ->orWhere(function ($q2) use ($checkIn, $checkOut) {
                                          $q2->where('check_in', '<=', $checkIn)
                                             ->where('check_out', '>=', $checkOut);
                                      });
                                });

                            if ($record) {
                                $query->where('id', '!=', $record->id);
                            }

                            if ($query->exists()) {
                                $fail('This room is already booked for the selected dates.');
                            }
                        };
                    }),

                DatePicker::make('check_in')
                    ->reactive()
                    ->required()
                    ->native(false)
                    ->afterStateUpdated(function ($get, $set) {
                        $roomId = $get('room_id');
                        $checkOut = $get('check_out');

                        if (! $roomId || ! $checkOut) {
                            return;
                        }

                        $room = Room::find($roomId);

                        if (! $room) {
                            return;
                        }

                        $days = Carbon::parse($get('check_in'))
                            ->diffInDays(Carbon::parse($checkOut));

                        $set('total_price', max($days, 1) * $room->roomType->price_per_night);
                    }),

                DatePicker::make('check_out')
                    ->reactive()
                    ->required()
                    ->native(false)
                    ->afterStateUpdated(function ($get, $set) {
                        $roomId = $get('room_id');
                        $checkIn = $get('check_in');

                        if (! $roomId || ! $checkIn) {
                            return;
                        }

                        $room = Room::find($roomId);

                        if (! $room) {
                            return;
                        }

                        $days = Carbon::parse($checkIn)
                            ->diffInDays(Carbon::parse($get('check_out')));

                        $set('total_price', max($days, 1) * $room->roomType->price_per_night);
                    }),

                /**
                 * NEW: STATUS FIELD
                 */
                Select::make('status')
                    ->options([
                        'pending' => 'Reservation (Future)',
                        'checked_in' => 'Check-in Now',
                    ])
                    ->default(fn () => request('walkin') ? 'checked_in' : 'pending')
                    ->required(),    

                /**
                 * AUTO PRICE CALCULATION
                 */    
                TextInput::make('total_price')
                    ->numeric()
                    ->disabled()
                    ->dehydrated(true)
                    ->live() // to update the total price when the check in or check out is changed
                    ->required()
                    ->afterStateUpdated(function (Set $set, Get $get) {

                        $checkIn = $get('check_in');
                        $checkOut = $get('check_out');
                        $roomId = $get('room_id');
                
                        if (!$checkIn || !$checkOut || !$roomId) return;
                
                        $room = Room::with('roomType')->find($roomId);
                
                        if (!$room) return;
                
                        $nights = \Carbon\Carbon::parse($checkIn)
                            ->diffInDays($checkOut);
                
                        $price = $room->roomType->price_per_night * max($nights, 1);
                
                        $set('total_price', $price);
                    }),
            ]);
    }
}
