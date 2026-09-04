<?php

namespace App\Models;

use App\Models\Concerns\HasPublicationState;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

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
     * Store facility names in a consistent form, including known legacy aliases.
     */
    protected function name(): Attribute
    {
        return Attribute::make(
            set: fn (mixed $value): string => self::canonicalName((string) $value),
        );
    }

    /**
     * Return the guest-facing canonical form of a facility name.
     */
    public static function canonicalName(string $name): string
    {
        $name = Str::squish($name);

        return match (Str::lower($name)) {
            'air conditioned' => 'Air Conditioning',
            default => $name,
        };
    }

    /**
     * Return a case-insensitive comparison key for a facility name.
     */
    public static function normalizedName(string $name): string
    {
        return Str::lower(self::canonicalName($name));
    }

    /**
     * Determine whether another facility already uses the same normalized name.
     */
    public static function hasEquivalentName(string $name, ?int $exceptId = null): bool
    {
        $normalizedName = self::normalizedName($name);

        return self::query()
            ->when($exceptId, fn ($query) => $query->whereKeyNot($exceptId))
            ->get(['id', 'name'])
            ->contains(fn (self $facility): bool => self::normalizedName($facility->name) === $normalizedName);
    }

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
