<?php

namespace App\Mail;

use App\Models\RestaurantReservation;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class RestaurantPaymentReceived extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(public RestaurantReservation $reservation) {}

    public function build(): static
    {
        return $this
            ->subject('Restaurant Payment Received')
            ->view('emails.restaurant.payment');
    }
}
