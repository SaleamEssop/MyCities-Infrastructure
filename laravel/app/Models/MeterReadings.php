<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;

class MeterReadings extends Model
{
    use HasFactory;

    protected $fillable = [
        'meter_id',
        'reading_date',
        'reading_value',
        'reading_image',
        'reading_type',
        'is_locked',
        'notes',
    ];

    protected $casts = [
        'is_locked' => 'boolean',
        'reading_date' => 'date',
    ];

    /**
     * Available reading types.
     */
    const TYPE_ACTUAL = 'ACTUAL';
    const TYPE_FINAL_ACTUAL = 'FINAL_ACTUAL';
    const TYPE_ESTIMATED = 'ESTIMATED';
    const TYPE_FINAL_ESTIMATED = 'FINAL_ESTIMATED';
    const TYPE_CALCULATED = 'CALCULATED';

    public function meter() {
        return $this->belongsTo(Meter::class);
    }

    public function getReadingImageAttribute($value)
    {
        if (empty($value))
            return '';
        return URL::to(Storage::url($value));
    }

    /**
     * Check if this reading is an actual reading (user submitted).
     */
    public function isActual(): bool
    {
        return in_array($this->reading_type, [self::TYPE_ACTUAL, self::TYPE_FINAL_ACTUAL]);
    }

    /**
     * Check if this reading is estimated (system generated).
     */
    public function isEstimated(): bool
    {
        return in_array($this->reading_type, [self::TYPE_ESTIMATED, self::TYPE_FINAL_ESTIMATED]);
    }
    /**
     * Check if this reading is calculated (interpolated from actual readings).
     */
    public function isCalculated(): bool
    {
        return $this->reading_type === self::TYPE_CALCULATED;
    }

    /**
     * Check if this reading is a final reading (cycle end).
     */
    public function isFinal(): bool
    {
        return in_array($this->reading_type, [self::TYPE_FINAL_ACTUAL, self::TYPE_FINAL_ESTIMATED]);
    }

    /**
     * Lock this reading to prevent modifications.
     */
    public function lock(): self
    {
        $this->is_locked = true;
        $this->save();
        return $this;
    }
}
