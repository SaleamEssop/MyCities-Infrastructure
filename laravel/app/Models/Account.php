<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Account extends Model
{
    use HasFactory;

    protected $fillable = [
        'site_id',
        'tariff_template_id',
        'account_name',
        'account_number',
        'name_on_bill',
        'billing_date',
        'optional_information',
        'water_email',
        'electricity_email',
        'bill_day',
        'read_day',
        'bill_read_day_active',
        'customer_costs',
        'fixed_costs',
    ];
    
    protected $casts = [
        'customer_costs' => 'array',
    ];

    public function site()
    {
        return $this->belongsTo(Site::class);
    }

    /**
     * Get the tariff template associated with this account.
     * This replaces the old region_id + account_type_id lookup.
     */
    public function tariffTemplate()
    {
        return $this->belongsTo(RegionsAccountTypeCost::class, 'tariff_template_id');
    }

    /**
     * Helper method to get the region via the tariff template.
     * Account gets region via TariffTemplate in the new architecture.
     */
    public function getRegion()
    {
        return $this->tariffTemplate ? $this->tariffTemplate->region : null;
    }

    /**
     * Helper method to get the region_id via the tariff template.
     * Named explicitly to avoid confusion with the old region_id column.
     */
    public function getRegionIdFromTemplateAttribute()
    {
        return $this->tariffTemplate ? $this->tariffTemplate->region_id : null;
    }

    public function fixedCosts()
    {
        return $this->hasMany(FixedCost::class);
    }

    public function defaultFixedCosts()
    {
        return $this->hasMany(AccountFixedCost::class);
    }

    public function meters()
    {
        return $this->hasMany(Meter::class);
    }

    public function payments()
    {
        return $this->hasMany(Payment::class);
    }

    protected static function booted()
    {
        static::deleting(function ($account) {
            // Delete all payments for this account
            Payment::where('account_id', $account->id)->delete();
            
            // Delete all fixed costs
            FixedCost::where('account_id', $account->id)->delete();
            AccountFixedCost::where('account_id', $account->id)->delete();

            // Delete all meters (which will cascade to readings)
            foreach($account->meters as $meter) {
                $meter->delete();
            }
        });
    }

}
