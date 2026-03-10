<?php

namespace App\Observers;

use App\Models\Booking;
use App\Models\ActivityLog;

class BookingObserver
{
    /**
     * Handle the Booking "created" event.
     */
    public function created(Booking $booking): void
    {
        //
    }

    /**
     * Handle the Booking "updated" event.
     */
    public function updated(Booking $booking): void
    {
        //
        ActivityLog::create([
            'user_id' => auth()->id(),
            'action' => 'updated',
            'model' => 'Booking',
            'record_id' => $booking->id,
            'old_values' => $booking->getOriginal(),
            'new_values' => $booking->getChanges(),
            'ip_address' => request()->ip(),
        ]);
    }

    /**
     * Handle the Booking "deleted" event.
     */
    public function deleted(Booking $booking): void
    {
        //
    }

    /**
     * Handle the Booking "restored" event.
     */
    public function restored(Booking $booking): void
    {
        //
    }

    /**
     * Handle the Booking "force deleted" event.
     */
    public function forceDeleted(Booking $booking): void
    {
        //
    }
}
