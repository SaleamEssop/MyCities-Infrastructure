<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FixedCost extends Model
{
    use HasFactory;

    protected $fillable = [
        'account_id',
        'title',
        'value',
        'is_default',
        'added_by',
        'is_active',
        'created_at'
    ];

    public function account()
    {
        return $this->belongsTo(Account::class);
    }

    public function defaultCosts()
    {
        return $this->hasMany(AccountFixedCost::class);
    }

    protected static function booted()
    {
        static::deleted(function ($fixedCost) {
            foreach($fixedCost->defaultCosts as $accFixedCost) {
                AccountFixedCost::where('id', $accFixedCost->id)->first()->delete();
            }
        });
    }
}
