<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Schema;

/**
 * Represents hotel setting and its persisted business behavior.
 */
class HotelSetting extends Model
{
    protected $fillable = [
        'hotel_name',
        'logo',
        'primary_color',
        'secondary_color',
        'footer_color',
    ];

    /**
     * Defines the color relationship or domain behavior for this model.
     */
    public function color(string $attribute, string $fallback): string
    {
        $color = $this->getAttribute($attribute);

        return is_string($color) && preg_match('/^#[0-9A-Fa-f]{6}$/', $color)
            ? $color
            : $fallback;
    }

    /**
     * Defines the current relationship or domain behavior for this model.
     */
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
