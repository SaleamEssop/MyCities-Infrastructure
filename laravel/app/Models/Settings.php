<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Settings extends Model
{
    use HasFactory;

    protected $fillable = [
        'terms_condition',
        'landing_background',
        'landing_title',
        'landing_subtitle',
        'demo_mode',
    ];

    protected $casts = [
        'demo_mode' => 'boolean',
    ];
}
