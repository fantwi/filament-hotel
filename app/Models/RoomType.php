<?php

namespace App\Models;

use App\Models\Concerns\HasPublicationState;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

/**
 * Represents room type and its persisted business behavior.
 */
class RoomType extends Model
{
    //
    use HasFactory;
    use HasPublicationState;
    use LogsActivity;

    protected $fillable = [
        'name',
        'price_per_night',
        'image',
        'gallery',
        'capacity',
        'description',
        'is_published',
        'created_by',
    ];

    protected $casts = ['gallery' => 'array', 'is_published' => 'boolean'];

    /**
     * Defines the rooms relationship or domain behavior for this model.
     */
    public function rooms()
    {
        return $this->hasMany(Room::class);
    }

    /**
     * Defines the facilities relationship or domain behavior for this model.
     */
    public function facilities()
    {
        return $this->belongsToMany(
            Facility::class
        );
    }

    /**
     * Identifies the staff member who created this room type.
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Lists the guest-facing information that must be completed before publication.
     *
     * @return array<int, string>
     */
    public function publicationReadinessIssues(): array
    {
        $issues = [];

        if (blank(trim((string) $this->name))) {
            $issues[] = 'room name';
        }

        if (! is_numeric($this->price_per_night) || (float) $this->price_per_night <= 0) {
            $issues[] = 'positive nightly price';
        }

        if (! is_numeric($this->capacity) || (int) $this->capacity < 1) {
            $issues[] = 'guest capacity';
        }

        if (blank(trim((string) $this->description))) {
            $issues[] = 'description';
        }

        if (blank($this->image)) {
            $issues[] = 'cover image';
        }

        return $issues;
    }

    /**
     * Determines whether this room type has enough information to be shown to guests.
     */
    public function isReadyForPublication(): bool
    {
        return $this->publicationReadinessIssues() === [];
    }

    /**
     * Builds and returns activitylog options.
     */
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logAll()
            ->logOnlyDirty()
            ->setDescriptionForEvent(fn (string $eventName) => "RoomType {$eventName}");
    }
}
