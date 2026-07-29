<?php

namespace App\Models;

use App\Models\Concerns\HasPublicationState;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

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

    public function rooms()
    {
        return $this->hasMany(Room::class);
    }

    public function facilities()
    {
        return $this->belongsToMany(
            Facility::class
        );
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logAll()
            ->logOnlyDirty()
            ->setDescriptionForEvent(fn (string $eventName) => "RoomType {$eventName}");
    }
}
