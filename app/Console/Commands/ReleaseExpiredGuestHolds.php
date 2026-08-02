<?php

namespace App\Console\Commands;

use App\Models\ConferenceBooking;
use App\Models\RestaurantReservation;
use Illuminate\Console\Command;

class ReleaseExpiredGuestHolds extends Command
{
    protected $signature = 'app:release-expired-guest-holds';
    protected $description = 'Release expired conference and restaurant table holds.';

    public function handle(): int
    {
        $conference = ConferenceBooking::query()
            ->where('status', 'pending')->whereNotNull('hold_until')->where('hold_until', '<=', now())
            ->update(['status' => 'expired', 'payment_status' => 'expired', 'hold_until' => null]);

        $tables = RestaurantReservation::query()
            ->where('status', 'pending')->whereNotNull('hold_until')->where('hold_until', '<=', now())
            ->update(['status' => 'cancelled', 'payment_status' => 'cancelled', 'hold_status' => 'expired', 'hold_until' => null]);

        $this->info("Released {$conference} conference hold(s) and {$tables} table hold(s).");

        return self::SUCCESS;
    }
}
