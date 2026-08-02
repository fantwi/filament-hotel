<?php

namespace App\Console\Commands;

use App\Models\ConferenceBooking;
use App\Models\RestaurantOrder;
use App\Models\RestaurantReservation;
use Illuminate\Console\Command;

class ReleaseExpiredGuestHolds extends Command
{
    protected $signature = 'app:release-expired-guest-holds';
    protected $description = 'Release expired conference and table holds and stale unpaid food orders.';

    public function handle(): int
    {
        $conference = ConferenceBooking::query()->where('status', 'pending')->whereNotNull('hold_until')->where('hold_until', '<=', now())->update(['status' => 'expired', 'payment_status' => 'expired', 'hold_until' => null]);
        $tables = RestaurantReservation::query()->where('status', 'pending')->whereNotNull('hold_until')->where('hold_until', '<=', now())->update(['status' => 'cancelled', 'payment_status' => 'cancelled', 'hold_status' => 'expired', 'hold_until' => null]);
        $orders = RestaurantOrder::query()->where('status', 'pending')->where('payment_status', 'pending')->where('created_at', '<=', now()->subMinutes(15))->update(['status' => 'cancelled', 'cancelled_at' => now()]);

        $this->info("Released {$conference} conference hold(s), {$tables} table hold(s), and {$orders} unpaid food order(s).");

        return self::SUCCESS;
    }
}
