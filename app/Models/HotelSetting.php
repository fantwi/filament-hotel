<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Schema;

class HotelSetting extends Model
{
    protected $fillable = [
        'hotel_name',
        'logo',
    ];

    public static function current(): self
    {
        if (! Schema::hasTable('hotel_settings')) {
            return new static(['hotel_name' => 'My Hotel']);
        }

        return static::query()->first() ?? new static([
            'hotel_name' => 'My Hotel',
        ]);
    }
}
