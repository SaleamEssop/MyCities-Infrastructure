<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RegionAlarms extends Model
{
    use HasFactory;

    protected $fillable = ['region_id', 'date', 'time', 'message', 'added_by'];

    public function region()
    {
        return $this->belongsTo(Regions::class);
    }
}
