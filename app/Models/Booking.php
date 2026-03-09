<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Carbon\Carbon;
use App\Models\Payment;
use App\Models\Guest;
use App\Models\Room;
use App\Services\RoomAssignmentService;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;

class Booking extends Model
{
    //
    use HasFactory;
    use LogsActivity;

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
        return $this->hasMany(Payment::class);
    }

    public function getTotalPaidAttribute()
    {
        return $this->payments()->sum('amount');
    }

    public function getBalanceAttribute()
    {
        $paid = $this->payments()->sum('amount');

        return $this->total_price - $paid;
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

                $booking->total_price = max($days,1) * $price;
            }
        });

        static::creating(function ($booking) {

            if (!$booking->room_id && $booking->room_type_id) {

                $room = RoomAssignmentService::assignRoom(
                    $booking->room_type_id,
                    $booking->check_in,
                    $booking->check_out
                );

                if ($room) {
                    $booking->room_id = $room->id;
                }
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

    protected function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logAll()
            ->useLogName('booking')
            ->logOnlyDirty();
    }
}
