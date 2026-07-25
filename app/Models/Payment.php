<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;
use App\Models\ConferenceBooking;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

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
            ->setDescriptionForEvent(fn(string $eventName) => "Payment {$eventName}");
    }
}
