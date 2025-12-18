<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MeterType extends Model
{
    use HasFactory;

    protected $fillable = ['title'];

    public function meters()
    {
        return $this->hasMany(Meter::class, 'id');
    }
}
