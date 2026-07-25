<?php

namespace App\Models;

use App\Models\Concerns\HasPublicationState;
use Illuminate\Database\Eloquent\Model;

class Facility extends Model
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

    public function roomTypes()
    {
        return $this->belongsToMany(
            RoomType::class
        );
    }

    public function restaurants()
    {
        return $this->belongsToMany(Restaurant::class)->withTimestamps();
    }
}
