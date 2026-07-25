<?php

namespace App\Models;

use App\Models\Concerns\HasPublicationState;
use Illuminate\Database\Eloquent\Model;

class ConferenceFacility extends Model
{
    use HasPublicationState;

    //
    protected $fillable = [

        'name',

        'icon',
        'is_published',
        'created_by',

    ];

    protected $casts = ['is_published' => 'boolean'];

    public function rooms()
    {
        return $this->belongsToMany(
            ConferenceRoom::class,
            'conference_facility_conference_room'
        );
    }
}
