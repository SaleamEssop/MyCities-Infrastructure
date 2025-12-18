<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class RegionsAccountTypeCost extends Model
{
    use HasFactory;

    protected $table  = 'regions_account_type_cost';

    protected $casts = [
        'water_in' => 'array',
        'water_out' => 'array',
        'electricity' => 'array',
        'additional' => 'array',
        'waterin_additional' => 'array',
        'waterout_additional' => 'array',
        'electricity_additional' => 'array',
        'fixed_costs' => 'array',
        'customer_costs' => 'array',
        'effective_from' => 'date',
        'effective_to' => 'date',
    ];

    protected $fillable = [
        'template_name',
        'parent_id',
        'region_id',
        'start_date',
        'end_date',
        'is_water',
        'is_electricity',
        'water_used',
        'electricity_used',
        'water_in',
        'water_out',
        'electricity',
        'additional',
        'vat_rate',
        'vat_percentage',
        'ratable_value',
        'rates_rebate',
        'is_active',
        'billing_day',
        'read_day',
        'waterin_additional',
        'waterout_additional',
        'electricity_additional',
        'water_email',
        'electricity_email',
        'fixed_costs',
        'customer_costs',
        'billing_type',
        'effective_from',
        'effective_to',
        'replaced_by_id'
    ];

    /**
     * Parent tariff (for date child relationships)
     */
    public function parent()
    {
        return $this->belongsTo(RegionsAccountTypeCost::class, 'parent_id');
    }

    /**
     * Child tariffs (date children)
     */
    public function children()
    {
        return $this->hasMany(RegionsAccountTypeCost::class, 'parent_id');
    }

    /**
     * Get the full hierarchy (ancestors + self + descendants)
     */
    public function getHierarchy()
    {
        $ancestors = [];
        $current = $this;
        
        // Get ancestors
        while ($current->parent_id) {
            $parent = self::find($current->parent_id);
            if ($parent) {
                array_unshift($ancestors, $parent);
                $current = $parent;
            } else {
                break;
            }
        }
        
        // Return ancestors, self, and children
        return [
            'ancestors' => $ancestors,
            'current' => $this,
            'children' => $this->children()->get(),
        ];
    }

    public function region()
    {
        return $this->belongsTo(Regions::class);
    }

    public function meterType()
    {
        return $this->belongsTo(MeterType::class);
    }

    /**
     * Get all accounts using this tariff template.
     * New relationship in the simplified architecture.
     */
    public function accounts()
    {
        return $this->hasMany(Account::class, 'tariff_template_id');
    }

    /**
     * Get all tariff tiers for this template.
     */
    public function tiers()
    {
        return $this->hasMany(TariffTier::class, 'tariff_template_id')->orderBy('tier_number');
    }

    /**
     * Get all tariff fixed costs for this template.
     */
    public function tariffFixedCosts()
    {
        return $this->hasMany(TariffFixedCost::class, 'tariff_template_id');
    }

    /**
     * Get all billing cycles for accounts using this template.
     */
    public function billingCycles()
    {
        return $this->hasManyThrough(BillingCycle::class, Account::class, 'tariff_template_id', 'account_id');
    }

    /**
     * Get the replacement tariff template.
     */
    public function replacedBy()
    {
        return $this->belongsTo(self::class, 'replaced_by_id');
    }

    /**
     * Get the tariff template that this one replaced.
     */
    public function replaces()
    {
        return $this->hasOne(self::class, 'replaced_by_id');
    }

    /**
     * Check if this tariff is effective for a given date.
     */
    public function isEffectiveFor(Carbon $date): bool
    {
        if ($this->effective_from && $date->lt($this->effective_from)) {
            return false;
        }
        if ($this->effective_to && $date->gt($this->effective_to)) {
            return false;
        }
        return $this->is_active;
    }

    /**
     * Check if this is a monthly billing type template.
     */
    public function isMonthlyBilling(): bool
    {
        return ($this->billing_type ?? 'MONTHLY') === 'MONTHLY';
    }

    /**
     * Check if this is a date-to-date billing type template.
     */
    public function isDateToDateBilling(): bool
    {
        return ($this->billing_type ?? 'MONTHLY') === 'DATE_TO_DATE';
    }

    /**
     * Get the VAT rate (default 15%).
     */
    public function getVatRate(): float
    {
        return $this->vat_percentage ?? 15.0;
    }
}
