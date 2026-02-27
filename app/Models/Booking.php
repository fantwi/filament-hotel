<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Carbon\Carbon;
use App\Models\Payment;

class Booking extends Model
{
    // 
    use HasFactory;

    protected $fillable = [
        'guest_id',
        'room_id',
        'check_in',
        'check_out',
        'total_price',
        'status',
    ];

    /*
    |--------------------------------------------------------------------------
    | Relationships
    |--------------------------------------------------------------------------
    */

    public function guest()
    {
        return $this->belongsTo(Guest::class);
    }

    public function room()
    {
        return $this->belongsTo(Room::class);
    }

    public function payments()
    {
        return $this->hasMany(\App\Models\Payment::class);
    }

    public function getTotalPaidAttribute()
    {
        return $this->payments()->sum('amount');
    }

    public function getBalanceAttribute()
    {
        return $this->total_price - $this->total_paid;
    }

    /*
    |--------------------------------------------------------------------------
    | Boot Logic (Business Logic)
    |--------------------------------------------------------------------------
    */

    protected static function booted()
    {
        // Calculate total price on create/update
        static::saving(function ($booking) {

            if ($booking->check_in && $booking->check_out) {

                $days = Carbon::parse($booking->check_in)
                    ->diffInDays(Carbon::parse($booking->check_out));

                $price = $booking->room->roomType->price_per_night;

                $booking->total_price = max($days, 1) * $price;
            }
        });

        // Mark room as occupied when booking is created
        static::created(function ($booking) {
            $booking->room->update(['status' => 'occupied']);
        });

        // Mark room available when checked out
        static::updated(function ($booking) {
            if ($booking->status === 'checked_out') {
                $booking->room->update(['status' => 'available']);
            }
        });
    }
}
