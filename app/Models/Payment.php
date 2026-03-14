<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;

class Payment extends Model
{
    //
    use HasFactory;
    use LogsActivity;

    protected $fillable = [
        'booking_id',
        'amount',
        'method',
        // 'payment_date',
        'transaction_reference',
    ];

    public function booking()
    {
        return $this->belongsTo(Booking::class);
    }

    protected static function booted()
    {
        static::created(function ($payment) {
            $booking = $payment->booking;

            if ($booking->balance <= 0) {
                // $booking->update(['status' => 'checked_out']);
            }
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
