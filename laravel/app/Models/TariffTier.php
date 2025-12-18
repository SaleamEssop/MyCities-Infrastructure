<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TariffTier extends Model
{
    use HasFactory;

    protected $table = 'tariff_tiers';

    protected $fillable = [
        'tariff_template_id',
        'tier_number',
        'min_units',
        'max_units',
        'rate_per_unit',
    ];

    protected $casts = [
        'min_units' => 'decimal:4',
        'max_units' => 'decimal:4',
        'rate_per_unit' => 'decimal:4',
    ];

    /**
     * Get the tariff template that this tier belongs to.
     */
    public function tariffTemplate()
    {
        return $this->belongsTo(RegionsAccountTypeCost::class, 'tariff_template_id');
    }
}
