<?php

namespace App\Models;

use App\Models\Concerns\HasPublicationState;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class MenuCategory extends Model
{
    use HasPublicationState;

    protected $fillable = [
        'name',
        'slug',
        'description',
        'icon',
        'is_active',
        'is_published',
        'created_by',
        'sort_order',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'is_published' => 'boolean',
    ];

    public function menuItems(): HasMany
    {
        return $this->hasMany(MenuItem::class)->orderBy('sort_order');
    }
}
