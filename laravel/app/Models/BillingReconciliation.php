<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BillingReconciliation extends Model
{
    protected $fillable = [
        'meter_id',
        'account_id',
        'billing_date',
        'original_estimate',
        'calculated_actual',
        'adjustment_units',
        'adjustment_type',
        'triggered_by_reading_id',
        'triggered_date',
        'status',
        'applied_to_bill_id',
        'notes',
    ];

    protected $casts = [
        'billing_date' => 'date',
        'triggered_date' => 'date',
        'original_estimate' => 'decimal:2',
        'calculated_actual' => 'decimal:2',
        'adjustment_units' => 'decimal:2',
    ];

    public function meter(): BelongsTo
    {
        return $this->belongsTo(Meter::class);
    }

    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class);
    }

    public function triggeredByReading(): BelongsTo
    {
        return $this->belongsTo(MeterReadings::class, 'triggered_by_reading_id');
    }

    /**
     * Check if this is an amount owing (under-estimated)
     */
    public function isOwing(): bool
    {
        return $this->adjustment_type === 'OWING';
    }

    /**
     * Check if this is a credit (over-estimated)
     */
    public function isCredit(): bool
    {
        return $this->adjustment_type === 'CREDIT';
    }

    /**
     * Get the signed adjustment value (positive for owing, negative for credit)
     */
    public function getSignedAdjustment(): float
    {
        return $this->isOwing() 
            ? abs($this->adjustment_units) 
            : -abs($this->adjustment_units);
    }
}