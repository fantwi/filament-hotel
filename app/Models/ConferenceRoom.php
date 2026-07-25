<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ConferenceRoom extends Model
{
    //
    protected $fillable = [

        'name',

        'description',

        'capacity',

        'price_per_hour',

        'location',

        'image',
        'gallery',

        'is_available',

    ];

    protected $casts = ['gallery' => 'array', 'is_available' => 'boolean'];

    public function bookings()
    {
        return $this->hasMany(
            ConferenceBooking::class
        );
    }

    public function facilities()
    {
        return $this->belongsToMany(
            ConferenceFacility::class,
            'conference_facility_conference_room'
        );
    }
}
