<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RestaurantTable extends Model
{
    protected $fillable = [

        'restaurant_id',

        'table_number',

        'capacity',

        'location',

        'status',

        'description',

        'qr_code',

    ];

    public function restaurant()
    {
        return $this->belongsTo(
            Restaurant::class
        );
    }
}
