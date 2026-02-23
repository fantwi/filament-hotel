<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Guest extends Model
{
    //
    use HasFactory; 
    
    protected $fillable = [
        'first_name',
        'last_name',
        'phone',
        'email',
        'id_number',
    ];

    public function getFullNameAttribute()
    {
        return "{$this->first_name} {$this->last_name}";
    }
}
