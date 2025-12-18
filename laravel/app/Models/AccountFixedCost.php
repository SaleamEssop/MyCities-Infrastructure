<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AccountFixedCost extends Model
{
    use HasFactory;

    protected $fillable = [
        'account_id',
        'fixed_cost_id',
        'value',
        'is_active'
    ];


    public function fixedCost()
    {
        return $this->belongsTo(FixedCost::class);
    }

    public function account()
    {
        return $this->belongsTo(Account::class);
    }
    public function getFixedCosts() {
        return $this->hasOne(FixedCost::class, 'id', 'fixed_cost_id');
    }
}
