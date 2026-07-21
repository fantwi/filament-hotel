<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;
use App\Models\ConferenceBooking;

class Payment extends Model
{
    //
    use HasFactory;
    use LogsActivity;

    protected $fillable = [
        'booking_id',
        'conference_booking_id',
        'restaurant_reservation_id',
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

    protected static function booted()
    {
        static::created(function ($payment) {
            $booking = $payment->booking ?? $payment->conferenceBooking;

            if (!$booking) {
                return 0;
            }
            // if ($booking->balance <= 0) {
            //     // $booking->update(['status' => 'checked_out']);
            // }
            
            activity()
                ->causedBy(auth()->user() ?? $payment->booking->guest?->user)
                ->performedOn($payment)
                ->log('Payment created');

            return $booking->balance ?? 0;

        });

        static::updated(function ($payment) {
            activity()
                ->causedBy(
                    auth()->user() ?? $payment->booking->guest?->user
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
