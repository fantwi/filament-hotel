<?php

namespace App\Console\Commands;

use App\Models\Booking;
use Illuminate\Console\Command;

class ReleaseExpiredBookings extends Command
{
    protected $signature = 'app:release-expired-bookings';

    protected $description = 'Release unpaid hotel room holds that have expired.';

    public function handle(): int
    {
        $released = Booking::query()
            ->where('status', 'pending')
            ->whereNotNull('hold_until')
            ->where('hold_until', '<=', now())
            ->whereDoesntHave('payments', fn ($query) => $query->where('payment_status', 'paid'))
            ->update([
                'status' => 'expired',
                'payment_status' => 'expired',
                'hold_status' => 'expired',
                'hold_until' => null,
            ]);

        $this->info("Released {$released} expired hotel room hold(s).");

        return self::SUCCESS;
    }
}
