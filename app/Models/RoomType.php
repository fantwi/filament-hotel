<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class RoomType extends Model
{
    //
    use HasFactory;

    protected $fillable = [
        'name',
        'price_per_night',
        'capacity',
        'description',
    ];
}
