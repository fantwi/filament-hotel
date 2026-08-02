<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class BillingSetting extends Model { protected $fillable = ['vat_rate','nhil_rate','service_charge_rate']; protected $casts = ['vat_rate'=>'decimal:2','nhil_rate'=>'decimal:2','service_charge_rate'=>'decimal:2']; }
