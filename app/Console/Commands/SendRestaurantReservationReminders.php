<?php

namespace App\Console\Commands;

use App\Mail\RestaurantReservationReminder;
use App\Models\RestaurantReservation;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;

class SendRestaurantReservationReminders extends Command
{
    protected $signature = 'restaurant:reminders';

    protected $description = 'Send reminders for confirmed restaurant reservations scheduled tomorrow.';

    public function handle(): int
    {
        $reservations = RestaurantReservation::query()
            ->whereDate('reservation_date', today()->addDay())
            ->where('status', 'confirmed')
            ->get();

        foreach ($reservations as $reservation) {
            Mail::to($reservation->guest_email)
                ->send(new RestaurantReservationReminder($reservation));
        }

        $this->info("Queued {$reservations->count()} restaurant reservation reminder(s).");

        return self::SUCCESS;
    }
}
