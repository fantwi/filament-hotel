<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class Payment extends Model
{
    //
    use HasFactory;
    use LogsActivity;

    protected $fillable = [
        'booking_id',
        'conference_booking_id',
        'restaurant_reservation_id',
        'restaurant_order_id',
        'guest_id',
        'amount',
        'method',
        'payment_status',
        // 'payment_date',
        'transaction_reference',
    ];

    public function booking()
    {
        return $this->belongsTo(Booking::class);
    }

    public function guest(): BelongsTo
    {
        return $this->belongsTo(Guest::class);
    }

    public function conferenceBooking()
    {
        return $this->belongsTo(
            ConferenceBooking::class
        );
    }

    public function restaurantReservation()
    {
        return $this->belongsTo(
            RestaurantReservation::class
        );
    }

    public function restaurantOrder()
    {
        return $this->belongsTo(RestaurantOrder::class);
    }

    public function transactionLabel(): string
    {
        return match (true) {
            $this->booking_id !== null => 'Hotel booking #'.$this->booking_id,
            $this->conference_booking_id !== null => 'Conference booking #'.$this->conference_booking_id,
            $this->restaurant_reservation_id !== null => 'Table reservation #'.$this->restaurant_reservation_id,
            $this->restaurant_order_id !== null => 'Food order #'.$this->restaurant_order_id,
            default => 'Unlinked payment',
        };
    }

    public function transactionGuest(): ?Guest
    {
        return $this->guest
            ?? $this->booking?->guest
            ?? $this->conferenceBooking?->guest
            ?? $this->restaurantReservation?->guest
            ?? $this->restaurantOrder?->guest;
    }

    public function transactionGuestName(): string
    {
        $guest = $this->transactionGuest();

        return $guest ? trim($guest->full_name) : 'Guest not recorded';
    }

    protected static function booted()
    {
        static::created(function ($payment) {
            $booking = $payment->booking
                ?? $payment->conferenceBooking
                ?? $payment->restaurantReservation
                ?? $payment->restaurantOrder;

            if (! $booking) {
                return;
            }

            activity()
                ->causedBy(auth()->user() ?? $booking->guest?->user)
                ->performedOn($payment)
                ->log('Payment created');
        });

        static::updated(function ($payment) {
            $booking = $payment->booking
                ?? $payment->conferenceBooking
                ?? $payment->restaurantReservation
                ?? $payment->restaurantOrder;

            activity()
                ->causedBy(
                    auth()->user() ?? $booking?->guest?->user
                )
                ->performedOn(
                    $payment
                )
                ->log(
                    'Payment updated'
                );
        });
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logAll()
            ->logOnlyDirty()
            ->setDescriptionForEvent(fn (string $eventName) => "Payment {$eventName}");
    }
}
