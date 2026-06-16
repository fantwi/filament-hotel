<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ConferenceBooking extends Model
{
    //
    protected $fillable = [

        'conference_room_id',

        'guest_id',

        'booking_date',

        'start_time',

        'end_time',

        'attendees',

        'total_price',

        'status',

        'payment_status',

        'hold_until',

    ];

    protected $casts = [

        'booking_date' => 'date',

        'hold_until' => 'datetime',

    ];

    public function room()
    {
        return $this->belongsTo(ConferenceRoom::class, 'conference_room_id');
    }

    public function guest()
    {
        return $this->belongsTo(Guest::class);
    }
}
