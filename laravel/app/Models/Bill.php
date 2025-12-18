<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Bill extends Model
{
    use HasFactory;

    protected $table = 'bills';

    protected $fillable = [
        'billing_cycle_id',
        'account_id',
        'meter_id',
        'tariff_template_id',
        'opening_reading_id',
        'closing_reading_id',
        'consumption',
        'tiered_charge',
        'fixed_costs_total',
        'vat_amount',
        'total_amount',
        'is_provisional',
    ];

    protected $casts = [
        'consumption' => 'decimal:4',
        'tiered_charge' => 'decimal:2',
        'fixed_costs_total' => 'decimal:2',
        'vat_amount' => 'decimal:2',
        'total_amount' => 'decimal:2',
        'is_provisional' => 'boolean',
    ];

    /**
     * Get the billing cycle for this bill.
     */
    public function billingCycle()
    {
        return $this->belongsTo(BillingCycle::class);
    }

    /**
     * Get the account for this bill.
     */
    public function account()
    {
        return $this->belongsTo(Account::class);
    }

    /**
     * Get the meter for this bill.
     */
    public function meter()
    {
        return $this->belongsTo(Meter::class);
    }

    /**
     * Get the tariff template used for this bill.
     */
    public function tariffTemplate()
    {
        return $this->belongsTo(RegionsAccountTypeCost::class, 'tariff_template_id');
    }

    /**
     * Get the opening reading.
     */
    public function openingReading()
    {
        return $this->belongsTo(MeterReadings::class, 'opening_reading_id');
    }

    /**
     * Get the closing reading.
     */
    public function closingReading()
    {
        return $this->belongsTo(MeterReadings::class, 'closing_reading_id');
    }

    /**
     * Get all adjustments for this bill.
     */
    public function adjustments()
    {
        return $this->hasMany(Adjustment::class);
    }

    /**
     * Get adjustments that were applied to this bill.
     */
    public function appliedAdjustments()
    {
        return $this->hasMany(Adjustment::class, 'applied_to_bill_id');
    }
}
