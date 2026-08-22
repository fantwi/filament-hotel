<?php

namespace App\Models;

use App\Models\Concerns\HasPublicationState;
use Illuminate\Database\Eloquent\Model;

/**
 * Represents facility and its persisted business behavior.
 */
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

    /**
     * Defines the room types relationship or domain behavior for this model.
     */
    public function roomTypes()
    {
        return $this->belongsToMany(
            RoomType::class
        );
    }

    /**
     * Defines the restaurants relationship or domain behavior for this model.
     */
    public function restaurants()
    {
        return $this->belongsToMany(Restaurant::class)->withTimestamps();
    }
}
