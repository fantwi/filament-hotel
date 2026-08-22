<?php

namespace App\Filament\Admin\Resources\Bookings\Pages;

use App\Filament\Admin\Resources\Bookings\BookingResource;
use Filament\Resources\Pages\CreateRecord;

/**
 * Configures Filament administration for create booking.
 */
class CreateBooking extends CreateRecord
{
    protected static string $resource = BookingResource::class;

    /**
     * Configures after create for the Filament administration interface.
     */
    protected function afterCreate(): void
    {
        $booking = $this->record;

        // If receptionist selects "Check-in Now"
        if ($booking->status === 'checked_in') {

            $booking->room->update([
                'status' => 'occupied',
            ]);

        }
    }
}
