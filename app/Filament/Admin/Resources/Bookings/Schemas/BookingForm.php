<?php

namespace App\Filament\Admin\Resources\Bookings\Schemas;

use App\Filament\Forms\Components\StrictDatePicker;
use App\Models\Room;
use App\Models\User;
use App\Services\RoomAvailabilityService;
use Carbon\Carbon;
use Carbon\CarbonInterface;
use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

/**
 * Configures Filament administration for booking form.
 */
class BookingForm
{
    /**
     * Configures configure for the Filament administration interface.
     */
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('guest_id')
                    ->relationship('guest', 'first_name')
                    ->getOptionLabelFromRecordUsing(fn ($record) => $record->full_name)
                    ->searchable(['first_name', 'last_name'])
                    ->preload()
                    ->createOptionAction(fn (Action $action): Action => $action
                        ->label('Create walk-in guest account')
                        ->visible(fn (): bool => auth()->user()?->hasAnyRole(['super_admin', 'admin', 'manager', 'receptionist']) ?? false))
                    ->createOptionForm([
                        TextInput::make('first_name')->required()->maxLength(255),
                        TextInput::make('last_name')->required()->maxLength(255),
                        TextInput::make('email')->email()->required()->unique(User::class, 'email'),
                        TextInput::make('phone_number')->tel()->maxLength(50),
                        TextInput::make('id_number')->maxLength(100),
                        TextInput::make('password')->password()->required()->minLength(8)->confirmed(),
                        TextInput::make('password_confirmation')->password()->required()->dehydrated(false),
                    ])
                    ->createOptionUsing(function (array $data): int {
                        return DB::transaction(function () use ($data): int {
                            $user = User::create([
                                'first_name' => $data['first_name'],
                                'last_name' => $data['last_name'],
                                'email' => $data['email'],
                                'phone_number' => $data['phone_number'] ?? null,
                                'id_number' => $data['id_number'] ?? null,
                                'department' => 'guest',
                                'password' => Hash::make($data['password']),
                            ]);

                            return $user->guest()->firstOrFail()->id;
                        });
                    })
                    ->required(),

                // Select::make('guest_id')
                //     ->relationship('guest', 'first_name')
                //     ->getOptionLabelFromRecordUsing(fn ($record) => $record->full_name)
                //     ->searchable(['first_name', 'last_name'])
                //     ->required(),

                Select::make('room_id')
                    ->options(function (Get $get, $record): array {
                        $dates = self::validStayDates($get);

                        if (! $dates) {
                            return [];
                        }

                        return app(RoomAvailabilityService::class)
                            ->query($dates['checkIn'], $dates['checkOut'], $record?->id)
                            ->orderBy('room_number')
                            ->pluck('room_number', 'id')
                            ->all();
                    })
                    ->default(
                        request('room_id')
                    )
                    ->searchable()
                    ->required()
                    ->disabled(fn ($record) => $record?->status === 'checked_in') // disable the room selection if the status is "Check-in Now"
                    ->live()
                    ->afterStateUpdated(fn (Get $get, Set $set, $record) => self::updateTotal($get, $set, $record))
                    ->rule(function (Get $get, $record) {
                        return function (string $attribute, $value, \Closure $fail) use ($get, $record): void {
                            $dates = self::validStayDates($get);

                            if (! $dates) {
                                return;
                            }

                            $roomId = self::effectiveRoomId($get, $record);

                            if (! $roomId || ! app(RoomAvailabilityService::class)->isAvailable($roomId, $dates['checkIn'], $dates['checkOut'], $record?->id)) {
                                $fail('This room is unavailable for the selected dates.');
                            }
                        };
                    }),

                StrictDatePicker::make('check_in')
                    ->minDate(today())
                    ->live()
                    ->required()
                    ->native(false)
                    ->afterStateUpdated(fn (Get $get, Set $set, $record) => self::refreshRoomSelection($get, $set, $record)),

                StrictDatePicker::make('check_out')
                    ->minDate(fn (Get $get) => self::dateFromState($get('check_in'))?->addDay() ?? today()->addDay())
                    ->after('check_in')
                    ->live()
                    ->required()
                    ->native(false)
                    ->afterStateUpdated(fn (Get $get, Set $set, $record) => self::refreshRoomSelection($get, $set, $record)),

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
                    ->dehydrateStateUsing(fn (Get $get, $record): float => self::calculateTotal($get, $record) ?? 0)
                    ->required(),
            ]);
    }

    private static function refreshRoomSelection(Get $get, Set $set, $record): void
    {
        if (filled($get('check_in')) && ! self::dateFromState($get('check_in'))) {
            $set('check_in', null);
        }

        if (filled($get('check_out')) && ! self::dateFromState($get('check_out'))) {
            $set('check_out', null);
        }

        $roomId = self::effectiveRoomId($get, $record);
        $dates = self::validStayDates($get);

        if ($record?->status === 'checked_in') {
            $set('room_id', $roomId);
        } elseif (! $roomId || ! $dates
            || ! app(RoomAvailabilityService::class)->isAvailable($roomId, $dates['checkIn'], $dates['checkOut'], $record?->id)) {
            $set('room_id', null);
        }

        self::updateTotal($get, $set, $record);
    }

    private static function updateTotal(Get $get, Set $set, $record): void
    {
        if (($total = self::calculateTotal($get, $record)) === null) {
            return;
        }

        $set('total_price', $total);
    }

    private static function calculateTotal(Get $get, $record): ?float
    {
        $roomId = self::effectiveRoomId($get, $record);
        $dates = self::validStayDates($get);

        if (! $roomId || ! $dates) {
            return null;
        }

        $room = Room::query()->with('roomType')->find($roomId);

        if (! $room?->roomType) {
            return null;
        }

        return $dates['checkIn']->diffInDays($dates['checkOut']) * $room->roomType->price_per_night;
    }

    private static function effectiveRoomId(Get $get, $record): ?int
    {
        $roomId = $record?->status === 'checked_in'
            ? $record->room_id
            : $get('room_id');

        return filled($roomId) ? (int) $roomId : null;
    }

    /**
     * @return array{checkIn: Carbon, checkOut: Carbon}|null
     */
    private static function validStayDates(Get $get): ?array
    {
        $checkIn = self::dateFromState($get('check_in'));
        $checkOut = self::dateFromState($get('check_out'));

        if (! $checkIn || ! $checkOut || ! $checkOut->greaterThan($checkIn)) {
            return null;
        }

        return compact('checkIn', 'checkOut');
    }

    private static function dateFromState(mixed $value): ?Carbon
    {
        if ($value instanceof CarbonInterface) {
            return Carbon::instance($value)->startOfDay();
        }

        if (! is_string($value)) {
            return null;
        }

        try {
            $date = Carbon::createFromFormat('!Y-m-d', $value);
        } catch (\Throwable) {
            return null;
        }

        return $date->format('Y-m-d') === $value ? $date : null;
    }
}
