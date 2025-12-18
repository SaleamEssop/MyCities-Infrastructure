<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Site extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id', 
        'title', 
        'lat', 
        'lng', 
        'address', 
        'email', 
        'region_id', 
        'billing_type', 
        'site_username'
        // REMOVED: use_custom_costs, custom_electricity_cost, custom_water_cost
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function region()
    {
        return $this->belongsTo(Regions::class);
    }
    
    public function accounts()
    {
        return $this->hasMany(Account::class);
    }

    protected static function booted()
    {
        static::deleting(function ($site) {
            // Delete all accounts for this site (which will cascade to meters and readings)
            foreach ($site->accounts as $account) {
                $account->delete();
            }
        });
    }
}
