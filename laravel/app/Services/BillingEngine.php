<?php

namespace App\Services;

use App\Models\Account;
use App\Models\Adjustment;
use App\Models\Bill;
use App\Models\BillingCycle;
use App\Models\Meter;
use App\Models\MeterReadings;
use App\Models\BillingReconciliation;
use App\Models\RegionsAccountTypeCost;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Collection;

/**
 * BillResult - A simple data class for billing calculation results.
 */
class BillResult
{
    public float $consumption;
    public float $tieredCharge;
    public float $fixedCostsTotal;
    public float $vatAmount;
    public float $totalAmount;
    public bool $isProvisional;
    public ?MeterReadings $openingReading;
    public ?MeterReadings $closingReading;
    public array $tierBreakdown;
    public array $fixedCostsBreakdown;

    public function __construct(
        float $consumption = 0,
        float $tieredCharge = 0,
        float $fixedCostsTotal = 0,
        float $vatAmount = 0,
        float $totalAmount = 0,
        bool $isProvisional = false,
        ?MeterReadings $openingReading = null,
        ?MeterReadings $closingReading = null,
        array $tierBreakdown = [],
        array $fixedCostsBreakdown = []
    ) {
        $this->consumption = $consumption;
        $this->tieredCharge = $tieredCharge;
        $this->fixedCostsTotal = $fixedCostsTotal;
        $this->vatAmount = $vatAmount;
        $this->totalAmount = $totalAmount;
        $this->isProvisional = $isProvisional;
        $this->openingReading = $openingReading;
        $this->closingReading = $closingReading;
        $this->tierBreakdown = $tierBreakdown;
        $this->fixedCostsBreakdown = $fixedCostsBreakdown;
    }

    public function toArray(): array
    {
        return [
            'consumption' => $this->consumption,
            'tiered_charge' => $this->tieredCharge,
            'fixed_costs_total' => $this->fixedCostsTotal,
            'vat_amount' => $this->vatAmount,
            'total_amount' => $this->totalAmount,
            'is_provisional' => $this->isProvisional,
            'opening_reading_id' => $this->openingReading?->id,
            'closing_reading_id' => $this->closingReading?->id,
            'tier_breakdown' => $this->tierBreakdown,
            'fixed_costs_breakdown' => $this->fixedCostsBreakdown,
        ];
    }
}

/**
 * BillingEngine - The unified billing calculation service for MyCities.
 * 
 * This service handles both Monthly and Date-to-Date billing calculations,
 * tiered rate calculations, estimations, and reconciliations.
 */
class BillingEngine
{
    /**
     * Default VAT rate (15%).
     */
    const DEFAULT_VAT_RATE = 15.0;

    /**
     * Default days before billing date for reading submission.
     */
    const DEFAULT_READ_DAYS_BEFORE = 5;

    /**
     * Default billing day of the month (20th).
     */
    const DEFAULT_BILLING_DAY = 20;

    /**
     * Conversion factor from liters to kiloliters.
     */
    const LITERS_TO_KILOLITERS = 1000;

    /**
     * Main entry point - routes to correct billing mode.
     *
     * @param Account $account
     * @param MeterReadings $from Opening reading
     * @param MeterReadings $to Closing reading
     * @return BillResult
     */
    public function calculateCharge(Account $account, MeterReadings $from, MeterReadings $to): BillResult
    {
        $tariff = $this->getTariffForAccount($account, Carbon::parse($to->reading_date));
        
        if (!$tariff) {
            return new BillResult();
        }

        if ($tariff->isDateToDateBilling()) {
            return $this->calculateDateToDate($tariff, $from, $to);
        }
        
        return $this->calculateMonthly($tariff, $account, $from, $to);
    }

    /**
     * Get the tariff template for an account valid for a specific date.
     *
     * @param Account $account
     * @param Carbon $date
     * @return RegionsAccountTypeCost|null
     */
    public function getTariffForAccount(Account $account, Carbon $date): ?RegionsAccountTypeCost
    {
        $tariff = $account->tariffTemplate;
        
        if (!$tariff) {
            return null;
        }

        // Check if the current tariff is effective for the given date
        if ($tariff->isEffectiveFor($date)) {
            return $tariff;
        }

        // If not, look for historical tariffs that were effective on that date
        return RegionsAccountTypeCost::where('region_id', $tariff->region_id)
            ->where('is_active', true)
            ->where(function ($query) use ($date) {
                $query->whereNull('effective_from')
                      ->orWhere('effective_from', '<=', $date);
            })
            ->where(function ($query) use ($date) {
                $query->whereNull('effective_to')
                      ->orWhere('effective_to', '>=', $date);
            })
            ->orderBy('effective_from', 'desc')
            ->first();
    }

    /**
     * Simple date-to-date calculation: (Reading2 - Reading1) ÃƒÆ’Ã¢â‚¬â€ tiered rates.
     *
     * @param RegionsAccountTypeCost $tariff
     * @param MeterReadings $from
     * @param MeterReadings $to
     * @return BillResult
     */
    public function calculateDateToDate(
        RegionsAccountTypeCost $tariff, 
        MeterReadings $from, 
        MeterReadings $to
    ): BillResult {
        $consumption = $to->reading_value - $from->reading_value;
        
        if ($consumption < 0) {
            $consumption = 0;
        }

        $tieredResult = $this->applyTieredRates($tariff, $consumption);
        $fixedCostsResult = $this->calculateFixedCosts($tariff);
        
        $subtotal = $tieredResult['total'] + $fixedCostsResult['total'];
        $vatableAmount = $tieredResult['total'] + $fixedCostsResult['vatable_total'];
        $vatAmount = $this->calculateVat($vatableAmount, $tariff->getVatRate());
        $totalAmount = $subtotal + $vatAmount;

        return new BillResult(
            consumption: $consumption,
            tieredCharge: $tieredResult['total'],
            fixedCostsTotal: $fixedCostsResult['total'],
            vatAmount: $vatAmount,
            totalAmount: $totalAmount,
            isProvisional: false,
            openingReading: $from,
            closingReading: $to,
            tierBreakdown: $tieredResult['breakdown'],
            fixedCostsBreakdown: $fixedCostsResult['breakdown']
        );
    }

    /**
     * Complex monthly billing with estimation & reconciliation support.
     *
     * @param RegionsAccountTypeCost $tariff
     * @param Account $account
     * @param MeterReadings $from
     * @param MeterReadings $to
     * @return BillResult
     */
    public function calculateMonthly(
        RegionsAccountTypeCost $tariff, 
        Account $account, 
        MeterReadings $from, 
        MeterReadings $to
    ): BillResult {
        $consumption = $to->reading_value - $from->reading_value;
        
        if ($consumption < 0) {
            $consumption = 0;
        }

        $tieredResult = $this->applyTieredRates($tariff, $consumption);
        $fixedCostsResult = $this->calculateFixedCosts($tariff);
        
        $subtotal = $tieredResult['total'] + $fixedCostsResult['total'];
        $vatableAmount = $tieredResult['total'] + $fixedCostsResult['vatable_total'];
        $vatAmount = $this->calculateVat($vatableAmount, $tariff->getVatRate());
        $totalAmount = $subtotal + $vatAmount;

        // Determine if this is a provisional bill
        $isProvisional = $to->isEstimated() || !$to->isFinal();

        return new BillResult(
            consumption: $consumption,
            tieredCharge: $tieredResult['total'],
            fixedCostsTotal: $fixedCostsResult['total'],
            vatAmount: $vatAmount,
            totalAmount: $totalAmount,
            isProvisional: $isProvisional,
            openingReading: $from,
            closingReading: $to,
            tierBreakdown: $tieredResult['breakdown'],
            fixedCostsBreakdown: $fixedCostsResult['breakdown']
        );
    }

    /**
     * Apply tiered/block rates to consumption.
     * 
     * Tiers are cumulative - each tier covers a range of consumption.
     * For example, with tiers 0-6000, 6000-15000, 15000+:
     * - A consumption of 12500 uses 6000 units from tier 1 and 6500 from tier 2
     *
     * @param RegionsAccountTypeCost $tariff
     * @param float $consumption Consumption in liters (for water) or kWh (for electricity)
     * @return array ['total' => float, 'breakdown' => array]
     */
    public function applyTieredRates(RegionsAccountTypeCost $tariff, float $consumption): array
    {
        $tiers = $tariff->tiers()->orderBy('tier_number')->get();
        
        // If no tiers defined, fall back to legacy calculation
        if ($tiers->isEmpty()) {
            return $this->applyLegacyRates($tariff, $consumption);
        }

        $consumedSoFar = 0;
        $totalCharge = 0;
        $breakdown = [];

        foreach ($tiers as $tier) {
            if ($consumedSoFar >= $consumption) {
                break;
            }

            $tierMin = (float) $tier->min_units;
            $tierMax = $tier->max_units !== null ? (float) $tier->max_units : PHP_FLOAT_MAX;
            
            // Calculate how many units fall within this tier
            // The effective start is the max of (tier min, already consumed)
            $effectiveStart = max($tierMin, $consumedSoFar);
            // The effective end is the min of (tier max, total consumption)
            $effectiveEnd = min($tierMax, $consumption);
            
            // Units in this tier
            $unitsInTier = max(0, $effectiveEnd - $effectiveStart);
            
            if ($unitsInTier <= 0) {
                continue;
            }
            
            // For water, rates are typically per kL (1000 liters)
            // For electricity, rates might be per kWh (no conversion needed)
            // The rate_per_unit in the database should be configured appropriately
            // We apply the conversion factor for water meters
            $convertedUnits = $this->convertUnitsForRateCalculation($tariff, $unitsInTier);
            $tierCharge = $convertedUnits * (float) $tier->rate_per_unit;
            
            $totalCharge += $tierCharge;
            $breakdown[] = [
                'tier_number' => $tier->tier_number,
                'min_units' => $tierMin,
                'max_units' => $tierMax === PHP_FLOAT_MAX ? null : $tierMax,
                'units_in_tier' => $unitsInTier,
                'rate_per_unit' => (float) $tier->rate_per_unit,
                'charge' => round($tierCharge, 2),
            ];

            $consumedSoFar = $effectiveEnd;
        }

        return [
            'total' => round($totalCharge, 2),
            'breakdown' => $breakdown,
        ];
    }

    /**
     * Convert units for rate calculation based on tariff type.
     * For water tariffs, converts liters to kiloliters.
     * For electricity tariffs, no conversion is needed (already in kWh).
     *
     * @param RegionsAccountTypeCost $tariff
     * @param float $units
     * @return float
     */
    protected function convertUnitsForRateCalculation(RegionsAccountTypeCost $tariff, float $units): float
    {
        // If this is a water tariff, convert liters to kiloliters
        if ($tariff->is_water) {
            return $units / self::LITERS_TO_KILOLITERS;
        }
        
        // For electricity or other utilities, use units directly
        return $units;
    }

    /**
     * Apply legacy rates from the existing tariff structure.
     * This is used when no tariff_tiers are defined.
     * Uses water_in/water_out/electricity arrays stored in the tariff template.
     *
     * @param RegionsAccountTypeCost $tariff
     * @param float $consumption Consumption in liters (for water) or kWh (for electricity)
     * @return array
     */
    protected function applyLegacyRates(RegionsAccountTypeCost $tariff, float $consumption): array
    {
        $totalCharge = 0;
        $breakdown = [];

        // Determine which rate array to use based on tariff type
        $tiers = [];
        if ($tariff->is_water && !empty($tariff->water_in)) {
            $tiers = $tariff->water_in;
        } elseif ($tariff->is_electricity && !empty($tariff->electricity)) {
            $tiers = $tariff->electricity;
        }

        if (empty($tiers)) {
            return [
                'total' => 0,
                'breakdown' => [],
            ];
        }

        $consumedSoFar = 0;
        $tierNumber = 0;

        foreach ($tiers as $tier) {
            $tierNumber++;
            
            if ($consumedSoFar >= $consumption) {
                break;
            }

            $tierMin = (float) ($tier['min'] ?? 0);
            $tierMax = isset($tier['max']) && $tier['max'] !== '' && $tier['max'] !== null 
                ? (float) $tier['max'] 
                : PHP_FLOAT_MAX;
            $costPerUnit = (float) ($tier['cost'] ?? 0);

            // Calculate how many units fall within this tier
            $effectiveStart = max($tierMin, $consumedSoFar);
            $effectiveEnd = min($tierMax, $consumption);
            $unitsInTier = max(0, $effectiveEnd - $effectiveStart);

            if ($unitsInTier <= 0) {
                continue;
            }

            // For water, convert liters to kiloliters (rates are per kL)
            $convertedUnits = $tariff->is_water 
                ? $unitsInTier / self::LITERS_TO_KILOLITERS 
                : $unitsInTier;
            
            $tierCharge = $convertedUnits * $costPerUnit;
            $totalCharge += $tierCharge;

            $breakdown[] = [
                'tier_number' => $tierNumber,
                'min_units' => $tierMin,
                'max_units' => $tierMax === PHP_FLOAT_MAX ? null : $tierMax,
                'units_in_tier' => $unitsInTier,
                'rate_per_unit' => $costPerUnit,
                'charge' => round($tierCharge, 2),
            ];

            $consumedSoFar = $effectiveEnd;
        }

        return [
            'total' => round($totalCharge, 2),
            'breakdown' => $breakdown,
        ];
    }

    /**
     * Calculate fixed costs for a tariff.
     *
     * @param RegionsAccountTypeCost $tariff
     * @return array
     */
    public function calculateFixedCosts(RegionsAccountTypeCost $tariff): array
    {
        $fixedCosts = $tariff->tariffFixedCosts;
        
        // If no fixed costs in the new table, try legacy fixed_costs array
        if ($fixedCosts->isEmpty() && !empty($tariff->fixed_costs)) {
            return $this->calculateLegacyFixedCosts($tariff);
        }

        $total = 0;
        $vatableTotal = 0;
        $breakdown = [];

        foreach ($fixedCosts as $cost) {
            $amount = (float) $cost->amount;
            $total += $amount;
            
            if ($cost->is_vatable) {
                $vatableTotal += $amount;
            }

            $breakdown[] = [
                'name' => $cost->name,
                'amount' => $amount,
                'is_vatable' => $cost->is_vatable,
            ];
        }

        return [
            'total' => round($total, 2),
            'vatable_total' => round($vatableTotal, 2),
            'breakdown' => $breakdown,
        ];
    }

    /**
     * Calculate fixed costs from legacy fixed_costs JSON array.
     *
     * @param RegionsAccountTypeCost $tariff
     * @return array
     */
    protected function calculateLegacyFixedCosts(RegionsAccountTypeCost $tariff): array
    {
        $fixedCosts = $tariff->fixed_costs ?? [];
        $total = 0;
        $vatableTotal = 0;
        $breakdown = [];

        foreach ($fixedCosts as $cost) {
            $amount = (float) ($cost['value'] ?? $cost['amount'] ?? 0);
            $isVatable = $cost['is_vatable'] ?? true;
            
            $total += $amount;
            
            if ($isVatable) {
                $vatableTotal += $amount;
            }

            $breakdown[] = [
                'name' => $cost['name'] ?? 'Fixed Cost',
                'amount' => $amount,
                'is_vatable' => $isVatable,
            ];
        }

        return [
            'total' => round($total, 2),
            'vatable_total' => round($vatableTotal, 2),
            'breakdown' => $breakdown,
        ];
    }

    /**
     * Calculate VAT amount.
     *
     * @param float $amount
     * @param float $vatRate
     * @return float
     */
    public function calculateVat(float $amount, float $vatRate): float
    {
        return round($amount * ($vatRate / 100), 2);
    }

    /**
     * Generate an estimated reading when user misses cycle end.
     *
     * @param Meter $meter
     * @param Carbon $cycleEnd
     * @return MeterReadings
     */
    public function generateEstimate(Meter $meter, Carbon $cycleEnd): MeterReadings
    {
        // Get the last two actual readings to calculate average daily consumption
        $lastReadings = $meter->readings()
            ->whereIn('reading_type', [MeterReadings::TYPE_ACTUAL, MeterReadings::TYPE_FINAL_ACTUAL])
            ->orderBy('reading_date', 'desc')
            ->limit(2)
            ->get();

        $estimatedValue = $this->estimateReadingValue($lastReadings, $cycleEnd);

        return MeterReadings::create([
            'meter_id' => $meter->id,
            'reading_value' => $estimatedValue,
            'reading_date' => $cycleEnd,
            'reading_type' => MeterReadings::TYPE_ESTIMATED,
            'is_locked' => false,
        ]);
    }

    /**
     * Estimate reading value based on historical consumption.
     *
     * @param \Illuminate\Support\Collection $lastReadings
     * @param Carbon $targetDate
     * @return float
     */
    protected function estimateReadingValue($lastReadings, Carbon $targetDate): float
    {
        if ($lastReadings->count() < 2) {
            // Not enough data to estimate, use last reading
            return $lastReadings->first()?->reading_value ?? 0;
        }

        $latestReading = $lastReadings->first();
        $previousReading = $lastReadings->last();

        $daysBetweenReadings = Carbon::parse($previousReading->reading_date)
            ->diffInDays(Carbon::parse($latestReading->reading_date));

        if ($daysBetweenReadings == 0) {
            return $latestReading->reading_value;
        }

        $consumptionBetweenReadings = $latestReading->reading_value - $previousReading->reading_value;
        $dailyConsumption = $consumptionBetweenReadings / $daysBetweenReadings;

        $daysToTarget = Carbon::parse($latestReading->reading_date)->diffInDays($targetDate);
        
        return round($latestReading->reading_value + ($dailyConsumption * $daysToTarget), 2);
    }

    /**
     * Reconcile when a late actual reading arrives.
     *
     * @param Bill $provisionalBill
     * @param MeterReadings $lateActual
     * @return Adjustment
     */
    public function reconcile(Bill $provisionalBill, MeterReadings $lateActual): Adjustment
    {
        $account = $provisionalBill->account;
        $tariff = $provisionalBill->tariffTemplate;
        $openingReading = $provisionalBill->openingReading;

        // Recalculate with actual reading
        $newResult = $this->calculateMonthly($tariff, $account, $openingReading, $lateActual);

        $adjustmentAmount = $newResult->totalAmount - $provisionalBill->total_amount;

        // Create adjustment record
        $adjustment = Adjustment::create([
            'bill_id' => $provisionalBill->id,
            'original_charge' => $provisionalBill->total_amount,
            'final_charge' => $newResult->totalAmount,
            'adjustment_amount' => $adjustmentAmount,
            'reason' => 'Reconciliation: Late actual reading received',
        ]);

        // Lock the estimated reading
        $provisionalBill->closingReading?->lock();

        // Update the closing reading type
        $lateActual->reading_type = MeterReadings::TYPE_FINAL_ACTUAL;
        $lateActual->save();

        return $adjustment;
    }

    /**
     * Calculate daily consumption for app display.
     *
     * @param Meter $meter
     * @return float
     */
    public function getDailyConsumption(Meter $meter): float
    {
        $lastReadings = $meter->readings()
            ->orderBy('reading_date', 'desc')
            ->limit(2)
            ->get();

        if ($lastReadings->count() < 2) {
            return 0;
        }

        $latestReading = $lastReadings->first();
        $previousReading = $lastReadings->last();

        $daysBetween = Carbon::parse($previousReading->reading_date)
            ->diffInDays(Carbon::parse($latestReading->reading_date));

        if ($daysBetween == 0) {
            return 0;
        }

        $consumption = $latestReading->reading_value - $previousReading->reading_value;
        
        return round($consumption / $daysBetween, 2);
    }

    /**
     * Get projected monthly bill for app display.
     *
     * @param Account $account
     * @return array
     */
    public function getProjectedMonthlyBill(Account $account): array
    {
        $tariff = $account->tariffTemplate;
        
        if (!$tariff) {
            return [
                'projected_consumption' => 0,
                'projected_amount' => 0,
                'breakdown' => [],
            ];
        }

        $meters = $account->meters;
        $totalProjectedConsumption = 0;
        $totalProjectedAmount = 0;
        $breakdown = [];

        foreach ($meters as $meter) {
            $dailyConsumption = $this->getDailyConsumption($meter);
            $daysInMonth = Carbon::now()->daysInMonth;
            $projectedMonthlyConsumption = $dailyConsumption * $daysInMonth;

            $tieredResult = $this->applyTieredRates($tariff, $projectedMonthlyConsumption);
            $fixedCostsResult = $this->calculateFixedCosts($tariff);
            
            $subtotal = $tieredResult['total'] + $fixedCostsResult['total'];
            $vatableAmount = $tieredResult['total'] + $fixedCostsResult['vatable_total'];
            $vatAmount = $this->calculateVat($vatableAmount, $tariff->getVatRate());
            $projectedAmount = $subtotal + $vatAmount;

            $totalProjectedConsumption += $projectedMonthlyConsumption;
            $totalProjectedAmount += $projectedAmount;

            $breakdown[] = [
                'meter_id' => $meter->id,
                'meter_title' => $meter->meter_title,
                'daily_consumption' => $dailyConsumption,
                'projected_consumption' => round($projectedMonthlyConsumption, 2),
                'projected_amount' => round($projectedAmount, 2),
            ];
        }

        return [
            'projected_consumption' => round($totalProjectedConsumption, 2),
            'projected_amount' => round($totalProjectedAmount, 2),
            'breakdown' => $breakdown,
            'tariff_name' => $tariff->template_name,
            'billing_type' => $tariff->billing_type ?? 'MONTHLY',
        ];
    }

    /**
     * Get the read date (billing date minus configured days).
     *
     * @param Account $account
     * @return Carbon
     */
    public function getReadDate(Account $account): Carbon
    {
        $billingDay = $account->bill_day ?? $account->tariffTemplate?->billing_day ?? self::DEFAULT_BILLING_DAY;
        $readDaysBefore = $account->read_day ?? $account->tariffTemplate?->read_day ?? self::DEFAULT_READ_DAYS_BEFORE;

        $billingDate = Carbon::now()->startOfMonth()->addDays($billingDay - 1);
        
        // If we've passed the billing date this month, move to next month
        if (Carbon::now()->gt($billingDate)) {
            $billingDate = $billingDate->addMonth();
        }

        return $billingDate->subDays($readDaysBefore);
    }

    /**
     * Get the billing date for an account.
     *
     * @param Account $account
     * @return Carbon
     */
    public function getBillingDate(Account $account): Carbon
    {
        $billingDay = $account->bill_day ?? $account->tariffTemplate?->billing_day ?? self::DEFAULT_BILLING_DAY;
        
        $billingDate = Carbon::now()->startOfMonth()->addDays($billingDay - 1);
        
        // If we've passed the billing date this month, move to next month
        if (Carbon::now()->gt($billingDate)) {
            $billingDate = $billingDate->addMonth();
        }

        return $billingDate;
    }

    /**
     * Get the cycle end date for an account.
     *
     * @param Account $account
     * @return Carbon
     */
    public function getCycleEndDate(Account $account): Carbon
    {
        return $this->getBillingDate($account);
    }

    /**
     * Process a reading submission and determine its type.
     *
     * @param Meter $meter
     * @param float $value
     * @param Carbon $date
     * @return MeterReadings
     */
    public function processReading(Meter $meter, float $value, Carbon $date): MeterReadings
    {
        $account = $meter->account;
        $cycleEndDate = $this->getCycleEndDate($account);
        
        $readingType = MeterReadings::TYPE_ACTUAL;
        
        if ($date->isSameDay($cycleEndDate)) {
            $readingType = MeterReadings::TYPE_FINAL_ACTUAL;
        }

        return MeterReadings::create([
            'meter_id' => $meter->id,
            'reading_value' => $value,
            'reading_date' => $date,
            'reading_type' => $readingType,
            'is_locked' => false,
        ]);
    }

    /**
     * Create a bill record from a BillResult.
     *
     * @param BillResult $result
     * @param Account $account
     * @param Meter $meter
     * @param BillingCycle|null $billingCycle
     * @return Bill
     */
    public function createBill(
        BillResult $result, 
        Account $account, 
        Meter $meter, 
        ?BillingCycle $billingCycle = null
    ): Bill {
        return Bill::create([
            'billing_cycle_id' => $billingCycle?->id,
            'account_id' => $account->id,
            'meter_id' => $meter->id,
            'tariff_template_id' => $account->tariff_template_id,
            'opening_reading_id' => $result->openingReading?->id,
            'closing_reading_id' => $result->closingReading?->id,
            'consumption' => $result->consumption,
            'tiered_charge' => $result->tieredCharge,
            'fixed_costs_total' => $result->fixedCostsTotal,
            'vat_amount' => $result->vatAmount,
            'total_amount' => $result->totalAmount,
            'is_provisional' => $result->isProvisional,
        ]);
    }

    /**
     * Get billing history for an account.
     *
     * @param Account $account
     * @param int $limit
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getBillingHistory(Account $account, int $limit = 12)
    {
        return Bill::where('account_id', $account->id)
            ->with(['meter', 'openingReading', 'closingReading', 'billingCycle'])
            ->orderBy('created_at', 'desc')
            ->limit($limit)
            ->get();
    }

    /**
     * Calculate 6-month average daily consumption for a meter.
     * Used for estimating consumption when we have an opening reading but no closing reading.
     *
     * @param Meter $meter
     * @param int $months Number of months to consider (default 6)
     * @return float Average daily consumption
     */
    public function getSixMonthDailyAverage(Meter $meter, int $months = 6): float
    {
        $cutoffDate = Carbon::now()->subMonths($months);
        
        // Get readings from the last N months
        $readings = $meter->readings()
            ->where('reading_date', '>=', $cutoffDate)
            ->orderBy('reading_date', 'asc')
            ->get();

        if ($readings->count() < 2) {
            // Fall back to getDailyConsumption if not enough historical data
            return $this->getDailyConsumption($meter);
        }

        $firstReading = $readings->first();
        $lastReading = $readings->last();

        $daysBetween = Carbon::parse($firstReading->reading_date)
            ->diffInDays(Carbon::parse($lastReading->reading_date));

        if ($daysBetween == 0) {
            return 0;
        }

        $totalConsumption = $lastReading->reading_value - $firstReading->reading_value;
        
        return round(max(0, $totalConsumption) / $daysBetween, 2);
    }

    /**
     * Estimate current period consumption when we only have an opening reading.
     * Uses 6-month average daily consumption to project from opening to today/cycle end.
     *
     * @param Meter $meter
     * @param MeterReadings $openingReading The opening (start) reading for the period
     * @param Carbon|null $targetDate Date to estimate to (defaults to today)
     * @return array ['estimated_reading' => float, 'estimated_consumption' => float, 'days' => int, 'daily_average' => float, 'is_estimated' => bool]
     */
    public function estimateCurrentPeriod(Meter $meter, MeterReadings $openingReading, ?Carbon $targetDate = null): array
    {
        $targetDate = $targetDate ?? Carbon::now();
        $openingDate = Carbon::parse($openingReading->reading_date);
        
        // Days since opening reading
        $daysSinceOpening = $openingDate->diffInDays($targetDate);
        
        if ($daysSinceOpening <= 0) {
            return [
                'estimated_reading' => $openingReading->reading_value,
                'estimated_consumption' => 0,
                'days' => 0,
                'daily_average' => 0,
                'is_estimated' => true,
            ];
        }

        // Get 6-month daily average
        $dailyAverage = $this->getSixMonthDailyAverage($meter);
        
        // Estimate consumption
        $estimatedConsumption = $dailyAverage * $daysSinceOpening;
        $estimatedReading = $openingReading->reading_value + $estimatedConsumption;

        return [
            'estimated_reading' => round($estimatedReading, 2),
            'estimated_consumption' => round($estimatedConsumption, 2),
            'days' => $daysSinceOpening,
            'daily_average' => $dailyAverage,
            'is_estimated' => true,
        ];
    }

    /**
     * Get period details including daily usage statistics.
     * Returns comprehensive data for displaying a billing period.
     *
     * @param MeterReadings $openingReading
     * @param MeterReadings|null $closingReading
     * @param Meter $meter
     * @return array
     */
    public function getPeriodDetails(MeterReadings $openingReading, ?MeterReadings $closingReading, Meter $meter): array
    {
        $openingDate = Carbon::parse($openingReading->reading_date);
        
        // If no closing reading, estimate current period
        if (!$closingReading) {
            $estimate = $this->estimateCurrentPeriod($meter, $openingReading);
            
            return [
                'opening_reading' => [
                    'value' => $openingReading->reading_value,
                    'date' => $openingReading->reading_date->format('Y-m-d'),
                    'type' => $openingReading->reading_type ?? 'ACTUAL',
                ],
                'closing_reading' => [
                    'value' => $estimate['estimated_reading'],
                    'date' => Carbon::now()->format('Y-m-d'),
                    'type' => 'ESTIMATED',
                ],
                'consumption' => $estimate['estimated_consumption'],
                'days_in_period' => $estimate['days'],
                'daily_usage' => $estimate['daily_average'],
                'total_used' => $estimate['estimated_consumption'],
                'is_estimated' => true,
                'estimation_method' => '6-month daily average',
            ];
        }

        // We have both readings - calculate actual
        $closingDate = Carbon::parse($closingReading->reading_date);
        $daysInPeriod = $openingDate->diffInDays($closingDate);
        $consumption = max(0, $closingReading->reading_value - $openingReading->reading_value);
        $dailyUsage = $daysInPeriod > 0 ? $consumption / $daysInPeriod : 0;

        return [
            'opening_reading' => [
                'value' => $openingReading->reading_value,
                'date' => $openingReading->reading_date->format('Y-m-d'),
                'type' => $openingReading->reading_type ?? 'ACTUAL',
            ],
            'closing_reading' => [
                'value' => $closingReading->reading_value,
                'date' => $closingReading->reading_date->format('Y-m-d'),
                'type' => $closingReading->reading_type ?? 'ACTUAL',
            ],
            'consumption' => round($consumption, 2),
            'days_in_period' => $daysInPeriod,
            'daily_usage' => round($dailyUsage, 2),
            'total_used' => round($consumption, 2),
            'is_estimated' => $closingReading->isEstimated(),
            'estimation_method' => null,
        ];
    }

    /**
     * Calculate billing date reading using interpolation when we have 
     * actual readings on BOTH sides of the billing date.
     * 
     * This converts an ESTIMATED reading to CALCULATED.
     *
     * @param MeterReadings $readingBefore Actual reading before billing date
     * @param MeterReadings $readingAfter Actual reading after billing date
     * @param Carbon $billingDate The billing date (e.g., 20th of month)
     * @return array ['value' => float, 'status' => string, 'daily_rate' => float]
     */
    public function interpolateBillingDateReading(
        MeterReadings $readingBefore,
        MeterReadings $readingAfter,
        Carbon $billingDate
    ): array {
        $dateBefore = Carbon::parse($readingBefore->reading_date);
        $dateAfter = Carbon::parse($readingAfter->reading_date);
        
        // Total days between the two actual readings
        $totalDays = $dateBefore->diffInDays($dateAfter);
        
        if ($totalDays == 0) {
            return [
                'value' => $readingBefore->reading_value,
                'status' => 'CALCULATED',
                'daily_rate' => 0,
            ];
        }
        
        // Total consumption between the two readings
        $totalConsumption = $readingAfter->reading_value - $readingBefore->reading_value;
        
        // Daily rate for this specific period (not historical average)
        $dailyRate = $totalConsumption / $totalDays;
        
        // Days from the "before" reading to the billing date
        $daysToBillingDate = $dateBefore->diffInDays($billingDate);
        
        // Interpolated value at billing date
        $interpolatedValue = $readingBefore->reading_value + ($dailyRate * $daysToBillingDate);
        
        return [
            'value' => round($interpolatedValue, 2),
            'status' => 'CALCULATED',  // Not ESTIMATED - this is derived from actuals
            'daily_rate' => round($dailyRate, 2),
        ];
    }

    /**
     * Get or calculate the reading for a specific billing date.
     * 
     * Priority:
     * 1. ACTUAL - if reading exists exactly on billing date
     * 2. CALCULATED - if we have actual readings BEFORE and AFTER billing date
     * 3. ESTIMATED - if we only have reading BEFORE (no subsequent reading yet)
     *
     * @param Meter $meter
     * @param Carbon $billingDate
     * @return array ['value' => float, 'status' => string, 'source' => string]
     */
    public function getBillingDateReading(Meter $meter, Carbon $billingDate): array
    {
        // 1. Check for actual reading ON the billing date
        $exactReading = $meter->readings()
            ->whereDate('reading_date', $billingDate)
            ->first();
        
        if ($exactReading) {
            return [
                'value' => $exactReading->reading_value,
                'status' => 'ACTUAL',
                'source' => 'exact_match',
                'reading_id' => $exactReading->id,
            ];
        }
        
        // 2. Get the most recent reading BEFORE billing date
        $readingBefore = $meter->readings()
            ->where('reading_date', '<', $billingDate)
            ->orderBy('reading_date', 'desc')
            ->first();
        
        // 3. Get the first reading AFTER billing date
        $readingAfter = $meter->readings()
            ->where('reading_date', '>', $billingDate)
            ->orderBy('reading_date', 'asc')
            ->first();
        
        // No reading before = cannot calculate
        if (!$readingBefore) {
            return [
                'value' => 0,
                'status' => 'NO_DATA',
                'source' => 'no_prior_reading',
            ];
        }
        
        // Have readings on BOTH sides = CALCULATED (interpolate)
        if ($readingAfter) {
            $interpolated = $this->interpolateBillingDateReading(
                $readingBefore,
                $readingAfter,
                $billingDate
            );
            
            return [
                'value' => $interpolated['value'],
                'status' => 'CALCULATED',
                'source' => 'interpolated',
                'daily_rate' => $interpolated['daily_rate'],
                'reading_before' => [
                    'date' => $readingBefore->reading_date->format('Y-m-d'),
                    'value' => $readingBefore->reading_value,
                ],
                'reading_after' => [
                    'date' => $readingAfter->reading_date->format('Y-m-d'),
                    'value' => $readingAfter->reading_value,
                ],
            ];
        }
        
        // Only have reading BEFORE = ESTIMATED (project forward using 6-month average)
        $estimate = $this->estimateCurrentPeriod($meter, $readingBefore, $billingDate);
        
        return [
            'value' => $estimate['estimated_reading'],
            'status' => 'ESTIMATED',
            'source' => '6_month_average',
            'daily_rate' => $estimate['daily_average'],
            'last_actual' => [
                'date' => $readingBefore->reading_date->format('Y-m-d'),
                'value' => $readingBefore->reading_value,
            ],
        ];
    }

    /**
     * Build complete billing history with proper status for each period.
     * Converts ESTIMATED to CALCULATED when subsequent readings arrive.
     *
     * @param Meter $meter
     * @param Carbon $startDate Account start date
     * @param int $billingDay Day of month for billing (e.g., 20)
     * @return array
     */
    public function buildBillingHistory(Meter $meter, Carbon $startDate, int $billingDay = 20): array
    {
        $periods = [];
        $currentDate = $startDate->copy();
        $now = Carbon::now();
        
        // Set to billing day of start month
        if ($currentDate->day > $billingDay) {
            $currentDate->addMonth();
        }
        $currentDate->day($billingDay);
        
        $previousReading = null;
        $previousValue = 0;
        
        while ($currentDate->lte($now->copy()->addMonth())) {
            $billingDateReading = $this->getBillingDateReading($meter, $currentDate->copy());
            
            if ($previousReading !== null) {
                $consumption = $billingDateReading['value'] - $previousValue;
                $days = $previousReading['date']->diffInDays($currentDate);
                $dailyUsage = $days > 0 ? $consumption / $days : 0;
                
                $periods[] = [
                    'period_start' => $previousReading['date']->format('Y-m-d'),
                    'period_end' => $currentDate->format('Y-m-d'),
                    'days' => $days,
                    'opening_reading' => $previousValue,
                    'closing_reading' => $billingDateReading['value'],
                    'consumption' => round($consumption, 2),
                    'daily_usage' => round($dailyUsage, 2),
                    'status' => $billingDateReading['status'],  // ACTUAL, CALCULATED, or ESTIMATED
                    'source' => $billingDateReading['source'],
                ];
            }
            
            $previousReading = ['date' => $currentDate->copy(), 'value' => $billingDateReading['value']];
            $previousValue = $billingDateReading['value'];
            $currentDate->addMonth();
        }
        
        return $periods;
    }

    /**
     * Check if a billing period needs reconciliation and perform it.
     * Called automatically when a new reading is submitted.
     *
     * @param Meter $meter
     * @param MeterReadings $newReading The new reading that just arrived
     * @return array List of reconciliations performed
     */
    public function checkAndReconcile(Meter $meter, MeterReadings $newReading): array
    {
        $reconciliations = [];
        $account = $meter->account;
        
        if (!$account) {
            return $reconciliations;
        }
        
        // Get billing day (default 20th)
        $billingDay = $account->billing_day ?? 20;
        
        // Find all ESTIMATED billing dates that now have readings on both sides
        $estimatedPeriods = $this->findEstimatedPeriodsToReconcile($meter, $newReading, $billingDay);
        
        foreach ($estimatedPeriods as $period) {
            $result = $this->reconcilePeriod(
                $meter,
                $account,
                $period['billing_date'],
                $period['original_estimate'],
                $newReading
            );
            
            if ($result) {
                $reconciliations[] = $result;
            }
        }
        
        return $reconciliations;
    }

    /**
     * Find billing dates that were estimated and can now be calculated.
     *
     * @param Meter $meter
     * @param MeterReadings $newReading
     * @param int $billingDay
     * @return array
     */
    protected function findEstimatedPeriodsToReconcile(
        Meter $meter, 
        MeterReadings $newReading, 
        int $billingDay
    ): array {
        $periods = [];
        $newReadingDate = Carbon::parse($newReading->reading_date);
        
        // Look back up to 6 months for estimated periods
        $checkDate = $newReadingDate->copy()->subMonths(6)->day($billingDay);
        
        while ($checkDate->lt($newReadingDate)) {
            // Skip if already reconciled
            $alreadyReconciled = BillingReconciliation::where('meter_id', $meter->id)
                ->where('billing_date', $checkDate->format('Y-m-d'))
                ->exists();
            
            if (!$alreadyReconciled) {
                // Check if we now have readings on both sides
                $billingReading = $this->getBillingDateReading($meter, $checkDate->copy());
                
                if ($billingReading['status'] === 'CALCULATED') {
                    // This was previously estimated, now can be calculated
                    // We need to find what the original estimate was
                    $originalEstimate = $this->getOriginalEstimateForDate($meter, $checkDate->copy());
                    
                    if ($originalEstimate !== null && abs($originalEstimate - $billingReading['value']) > 0.01) {
                        $periods[] = [
                            'billing_date' => $checkDate->copy(),
                            'original_estimate' => $originalEstimate,
                            'calculated_value' => $billingReading['value'],
                        ];
                    }
                }
            }
            
            $checkDate->addMonth();
        }
        
        return $periods;
    }

    /**
     * Get the original estimated reading that was used for billing.
     * This would typically come from a stored bill or reading record.
     *
     * @param Meter $meter
     * @param Carbon $billingDate
     * @return float|null
     */
    protected function getOriginalEstimateForDate(Meter $meter, Carbon $billingDate): ?float
    {
        // Check if there's a stored estimated reading for this date
        $estimatedReading = $meter->readings()
            ->whereDate('reading_date', $billingDate)
            ->where('reading_type', 'ESTIMATED')
            ->first();
        
        if ($estimatedReading) {
            return $estimatedReading->reading_value;
        }
        
        // If no stored estimate, we can't reconcile (nothing to compare against)
        return null;
    }

    /**
     * Perform reconciliation for a specific billing period.
     *
     * @param Meter $meter
     * @param Account $account
     * @param Carbon $billingDate
     * @param float $originalEstimate
     * @param MeterReadings $triggerReading
     * @return BillingReconciliation|null
     */
    public function reconcilePeriod(
        Meter $meter,
        Account $account,
        Carbon $billingDate,
        float $originalEstimate,
        MeterReadings $triggerReading
    ): ?BillingReconciliation {
        // Recalculate using interpolation
        $calculated = $this->getBillingDateReading($meter, $billingDate);
        
        if ($calculated['status'] !== 'CALCULATED') {
            return null; // Still can't calculate
        }
        
        $calculatedValue = $calculated['value'];
        $difference = $calculatedValue - $originalEstimate;
        
        // Don't create reconciliation for tiny differences (rounding)
        if (abs($difference) < 1) {
            return null;
        }
        
        // Create reconciliation record
        $reconciliation = BillingReconciliation::create([
            'meter_id' => $meter->id,
            'account_id' => $account->id,
            'billing_date' => $billingDate->format('Y-m-d'),
            'original_estimate' => $originalEstimate,
            'calculated_actual' => $calculatedValue,
            'adjustment_units' => abs($difference),
            'adjustment_type' => $difference > 0 ? 'OWING' : 'CREDIT',
            'triggered_by_reading_id' => $triggerReading->id,
            'triggered_date' => now(),
            'status' => 'PENDING',
            'notes' => sprintf(
                'Auto-reconciled: Original estimate %.2f, Calculated %.2f (daily rate: %.2f)',
                $originalEstimate,
                $calculatedValue,
                $calculated['daily_rate'] ?? 0
            ),
        ]);
        
        // Update the original estimated reading to CALCULATED
        $meter->readings()
            ->whereDate('reading_date', $billingDate)
            ->where('reading_type', 'ESTIMATED')
            ->update([
                'reading_type' => 'CALCULATED',
                'reading_value' => $calculatedValue,
                'notes' => 'Reconciled from estimate ' . $originalEstimate,
            ]);
        
        return $reconciliation;
    }

    /**
     * Store an estimated reading for a billing date.
     * This creates a record that can later be reconciled.
     *
     * @param Meter $meter
     * @param Carbon $billingDate
     * @param float $estimatedValue
     * @param float $dailyAverage
     * @return MeterReadings
     */
    public function storeEstimatedReading(
        Meter $meter,
        Carbon $billingDate,
        float $estimatedValue,
        float $dailyAverage
    ): MeterReadings {
        return MeterReadings::updateOrCreate(
            [
                'meter_id' => $meter->id,
                'reading_date' => $billingDate->format('Y-m-d'),
            ],
            [
                'reading_value' => $estimatedValue,
                'reading_type' => 'ESTIMATED',
                'notes' => sprintf('Estimated at %.2f/day average', $dailyAverage),
                'created_at' => now(),
                'updated_at' => now(),
            ]
        );
    }

    /**
     * Get all pending reconciliations for an account.
     *
     * @param Account $account
     * @return Collection
     */
    public function getPendingReconciliations(Account $account): Collection
    {
        return BillingReconciliation::where('account_id', $account->id)
            ->where('status', 'PENDING')
            ->orderBy('billing_date')
            ->get();
    }

    /**
     * Apply pending reconciliations to a bill.
     *
     * @param Account $account
     * @param int $billId
     * @return array Summary of applied reconciliations
     */
    public function applyReconciliationsToBill(Account $account, int $billId): array
    {
        $pending = $this->getPendingReconciliations($account);
        
        $totalOwing = 0;
        $totalCredit = 0;
        $applied = [];
        
        foreach ($pending as $reconciliation) {
            if ($reconciliation->isOwing()) {
                $totalOwing += $reconciliation->adjustment_units;
            } else {
                $totalCredit += $reconciliation->adjustment_units;
            }
            
            $reconciliation->update([
                'status' => 'APPLIED',
                'applied_to_bill_id' => $billId,
            ]);
            
            $applied[] = [
                'billing_date' => $reconciliation->billing_date->format('Y-m-d'),
                'type' => $reconciliation->adjustment_type,
                'units' => $reconciliation->adjustment_units,
            ];
        }
        
        return [
            'count' => count($applied),
            'total_owing' => $totalOwing,
            'total_credit' => $totalCredit,
            'net_adjustment' => $totalOwing - $totalCredit,
            'details' => $applied,
        ];
    }
}