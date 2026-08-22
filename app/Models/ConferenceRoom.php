<?php

namespace App\Models;

use App\Models\Concerns\HasPublicationState;
use Illuminate\Database\Eloquent\Model;

/**
 * Represents conference room and its persisted business behavior.
 */
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

    /**
     * Defines the bookings relationship or domain behavior for this model.
     */
    public function bookings()
    {
        return $this->hasMany(
            ConferenceBooking::class
        );
    }

    /**
     * Defines the facilities relationship or domain behavior for this model.
     */
    public function facilities()
    {
        return $this->belongsToMany(
            ConferenceFacility::class,
            'conference_facility_conference_room'
        );
    }
}
