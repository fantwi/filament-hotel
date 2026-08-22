<?php

namespace App\Mail;

use App\Models\RestaurantReservation;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

/**
 * Builds the restaurant reservation confirmed email notification.
 */
class RestaurantReservationConfirmed extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    /**
     * Initializes the dependencies required by this component.
     */
    public function __construct(public RestaurantReservation $reservation) {}

    /**
     * Builds .
     */
    public function build(): static
    {
        return $this
            ->subject('Restaurant Reservation Confirmed')
            ->view('emails.restaurant.confirmed');
    }
}
