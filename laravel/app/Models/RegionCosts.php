<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RegionCost extends Model
{
    use HasFactory;

    protected $table = 'region_costs'; 

    protected $fillable = [
        'meter_type_id', 
        'region_id', 
        'min', 
        'max', 
        'amount'
    ];

    public function region()
    {
        return $this->belongsTo(Regions::class, 'region_id');
    }

    public function meterType()
    {
        return $this->belongsTo(MeterType::class, 'meter_type_id');
    }
}
