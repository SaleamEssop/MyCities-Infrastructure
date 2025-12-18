<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Payment extends Model
{
    use HasFactory;

    protected $fillable = [
        'account_id',
        'amount',
        'payment_date',
        'payment_method',
        'reference',
        'notes',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'payment_date' => 'date',
    ];

    /**
     * Get the account this payment belongs to.
     */
    public function account()
    {
        return $this->belongsTo(Account::class);
    }

    /**
     * Scope to get payments for a specific period.
     */
    public function scopeForPeriod($query, $startDate, $endDate)
    {
        return $query->whereBetween('payment_date', [$startDate, $endDate]);
    }

    /**
     * Get formatted amount.
     */
    public function getFormattedAmountAttribute()
    {
        return 'R ' . number_format($this->amount, 2);
    }
}






