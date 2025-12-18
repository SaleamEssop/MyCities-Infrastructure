<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TariffFixedCost extends Model
{
    use HasFactory;

    protected $table = 'tariff_fixed_costs';

    protected $fillable = [
        'tariff_template_id',
        'name',
        'amount',
        'is_vatable',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'is_vatable' => 'boolean',
    ];

    /**
     * Get the tariff template that this fixed cost belongs to.
     */
    public function tariffTemplate()
    {
        return $this->belongsTo(RegionsAccountTypeCost::class, 'tariff_template_id');
    }
}
