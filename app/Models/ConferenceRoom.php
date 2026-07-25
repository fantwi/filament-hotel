<?php

namespace App\Models;

use App\Models\Concerns\HasPublicationState;
use Illuminate\Database\Eloquent\Model;

class ConferenceRoom extends Model
{
    use HasPublicationState;

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
        'is_published',
        'created_by',

    ];

    protected $casts = ['gallery' => 'array', 'is_available' => 'boolean', 'is_published' => 'boolean'];

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
