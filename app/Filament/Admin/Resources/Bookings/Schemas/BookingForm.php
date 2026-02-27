<?php

namespace App\Filament\Admin\Resources\Bookings\Schemas;

use App\Models\Booking;
use App\Models\Room;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Illuminate\Validation\ValidationException;
use Filament\Schemas\Schema;

use Carbon\Carbon;

class BookingForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                //
                Select::make('guest_id')
                    ->relationship('guest', 'first_name')
                    ->searchable()
                    ->required(),

                Select::make('room_id')
                    ->relationship(
                        name: 'room', 
                        titleAttribute: 'room_number',
                        modifyQueryUsing: fn ($query) =>
                            $query->where('status', 'available')
                    )
                    ->searchable()
                    ->reactive()
                    ->required()

                    // 🔥 Live Price Calculation
                    ->afterStateUpdated(function ($state, $get, $set) {

                        $checkIn = $get('check_in');
                        $checkOut = $get('check_out');

                        if (!$state || !$checkIn || !$checkOut) {
                            return;
                        }

                        $room = Room::find($state);
                        if (!$room) return;

                        $days = Carbon::parse($checkIn)
                            ->diffInDays(Carbon::parse($checkOut));

                        $price = $room->roomType->price_per_night;

                        $set('total_price', max($days, 1) * $price);
                    })

                    // 🔥 Double Booking Protection
                    ->rule(function ($get, $record) {
                        return function (string $attribute, $value, \Closure $fail) use ($get, $record) {

                            $checkIn = $get('check_in');
                            $checkOut = $get('check_out');

                            if (!$checkIn || !$checkOut) {
                                return;
                            }

                            $query = Booking::where('room_id', $value)
                                ->where(function ($q) use ($checkIn, $checkOut) {
                                    $q->whereBetween('check_in', [$checkIn, $checkOut])
                                    ->orWhereBetween('check_out', [$checkIn, $checkOut])
                                    ->orWhere(function ($q2) use ($checkIn, $checkOut) {
                                        $q2->where('check_in', '<=', $checkIn)
                                            ->where('check_out', '>=', $checkOut);
                                    });
                                });

                            // Ignore current record when editing
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

                        if (!$roomId || !$checkOut) return;

                        $room = Room::find($roomId);
                        if (!$room) return;

                        $days = Carbon::parse($get('check_in'))
                            ->diffInDays(Carbon::parse($checkOut));

                        $price = $room->roomType->price_per_night;

                        $set('total_price', max($days, 1) * $price);
                    }),

                DatePicker::make('check_out')
                    ->reactive()
                    ->required()
                    ->native(false)
                    ->afterStateUpdated(function ($get, $set) {

                        $roomId = $get('room_id');
                        $checkIn = $get('check_in');

                        if (!$roomId || !$checkIn) return;

                        $room = Room::find($roomId);
                        if (!$room) return;

                        $days = Carbon::parse($checkIn)
                            ->diffInDays(Carbon::parse($get('check_out')));

                        $price = $room->roomType->price_per_night;

                        $set('total_price', max($days, 1) * $price);
                    }),

                TextInput::make('total_price')
                    ->numeric()
                    ->disabled()
                    ->dehydrated(true)
                    ->required(),
            ]);
    }
}
