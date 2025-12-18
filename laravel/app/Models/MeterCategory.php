<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MeterCategory extends Model
{
    use HasFactory;

    public function meters()
    {
        return $this->hasMany(Meter::class);
    }
}
