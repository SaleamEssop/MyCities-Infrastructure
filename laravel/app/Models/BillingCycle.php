<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BillingCycle extends Model
{
    use HasFactory;

    protected $table = 'billing_cycles';

    protected $fillable = [
        'account_id',
        'cycle_start',
        'cycle_end',
        'status',
    ];

    protected $casts = [
        'cycle_start' => 'date',
        'cycle_end' => 'date',
    ];

    /**
     * Get the account that this billing cycle belongs to.
     */
    public function account()
    {
        return $this->belongsTo(Account::class);
    }

    /**
     * Get all bills for this billing cycle.
     */
    public function bills()
    {
        return $this->hasMany(Bill::class);
    }

    /**
     * Check if the billing cycle is provisional.
     */
    public function isProvisional()
    {
        return $this->status === 'provisional';
    }

    /**
     * Check if the billing cycle is final.
     */
    public function isFinal()
    {
        return $this->status === 'final';
    }

    /**
     * Mark the billing cycle as final.
     */
    public function markAsFinal()
    {
        $this->status = 'final';
        $this->save();
        return $this;
    }
}
