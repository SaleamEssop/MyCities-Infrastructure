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
        'db_mode',
        'external_db_host',
        'external_db_port',
        'external_db_database',
        'external_db_username',
        'external_db_password',
    ];

    protected $casts = [
        'demo_mode' => 'boolean',
        'external_db_port' => 'integer',
    ];

    protected $hidden = [
        'external_db_password',
    ];
}
