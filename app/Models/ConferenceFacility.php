<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ConferenceFacility extends Model
{
    //
    protected $fillable = [

        'name',

        'icon',

    ];

    public function rooms()
    {
        return $this->belongsToMany(
            ConferenceRoom::class,
            'conference_facility_conference_room'
        );
    }
}
