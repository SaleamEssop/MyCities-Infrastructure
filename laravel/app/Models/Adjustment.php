<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Adjustment extends Model
{
    use HasFactory;

    protected $table = 'adjustments';

    protected $fillable = [
        'bill_id',
        'original_charge',
        'final_charge',
        'adjustment_amount',
        'applied_to_bill_id',
        'reason',
    ];

    protected $casts = [
        'original_charge' => 'decimal:2',
        'final_charge' => 'decimal:2',
        'adjustment_amount' => 'decimal:2',
    ];

    /**
     * Get the original bill that this adjustment relates to.
     */
    public function bill()
    {
        return $this->belongsTo(Bill::class);
    }

    /**
     * Get the bill that this adjustment was applied to.
     */
    public function appliedToBill()
    {
        return $this->belongsTo(Bill::class, 'applied_to_bill_id');
    }
}
