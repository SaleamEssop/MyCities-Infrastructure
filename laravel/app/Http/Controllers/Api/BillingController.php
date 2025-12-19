<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Account;
use App\Models\Meter;
use App\Models\MeterReadings;
use App\Models\Payment;
use App\Models\Site;
use App\Services\BillingEngine;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class BillingController extends Controller
{
    protected BillingEngine $billingEngine;

    public function __construct(BillingEngine $billingEngine)
    {
        $this->billingEngine = $billingEngine;
    }

    /**
     * Get comprehensive dashboard data for the logged-in user.
     * This endpoint provides ALL data needed for the dashboard - no hardcoded values.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function getDashboard(Request $request): JsonResponse
    {
        $user = Auth::user();
        
        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'User not authenticated',
            ], 401);
        }

        // Get user's site and account
        $site = Site::where('user_id', $user->id)->with(['accounts.meters', 'accounts.tariffTemplate'])->first();
        
        if (!$site) {
            return response()->json([
                'success' => false,
                'message' => 'No site found for user',
            ], 404);
        }

        $account = $site->accounts->first();
        
        if (!$account) {
            return response()->json([
                'success' => false,
                'message' => 'No account found for site',
            ], 404);
        }

        $tariff = $account->tariffTemplate;
        
        if (!$tariff) {
            return response()->json([
                'success' => false,
                'message' => 'No tariff template assigned to account',
            ], 404);
        }

        // Get billing type and related info
        $billingType = $tariff->billing_type ?? 'MONTHLY';
        $isDateToDate = $tariff->isDateToDateBilling();

        // Get meters and their readings
        $meters = $account->meters;
        $waterMeter = $meters->where('meter_type_id', 1)->first(); // Assuming 1 = Water
        $electricityMeter = $meters->where('meter_type_id', 2)->first(); // Assuming 2 = Electricity

        // Build water data
        $waterData = $this->getMeterData($waterMeter, $tariff, 'water');
        
        // Build electricity data
        $electricityData = $this->getMeterData($electricityMeter, $tariff, 'electricity');

        // Calculate per-meter VAT and period totals
        $vatRate = $tariff->getVatRate() / 100;
        $waterCharges = $waterData['charges']['total'] ?? 0;
        $electricityCharges = $electricityData['charges']['total'] ?? 0;
        
        $waterVat = $waterCharges * $vatRate;
        $electricityVat = $electricityCharges * $vatRate;
        
        $waterPeriodTotal = $waterCharges + $waterVat;
        $electricityPeriodTotal = $electricityCharges + $electricityVat;
        
        // Add per-meter totals to data
        $waterData['totals'] = [
            'consumption_total' => round($waterCharges, 2),
            'vat_amount' => round($waterVat, 2),
            'vat_rate' => $tariff->getVatRate(),
            'period_total' => round($waterPeriodTotal, 2),
        ];
        
        $electricityData['totals'] = [
            'consumption_total' => round($electricityCharges, 2),
            'vat_amount' => round($electricityVat, 2),
            'vat_rate' => $tariff->getVatRate(),
            'period_total' => round($electricityPeriodTotal, 2),
        ];

        // Calculate total charges (combined)
        $totalCharges = $waterCharges + $electricityCharges;
        $totalVat = $waterVat + $electricityVat;
        $grandTotal = $totalCharges + $totalVat;

        // Get period info based on billing type
        $periodInfo = $this->getPeriodInfo($account, $tariff);

        // Get payments for the current period
        $paymentsData = $this->getPaymentsData($account, $periodInfo['start_date'], $periodInfo['end_date']);
        
        // Calculate balance
        $totalPaid = $paymentsData['total_paid'];
        $balanceDue = $grandTotal - $totalPaid;

        return response()->json([
            'success' => true,
            'data' => [
                'user' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                ],
                'account' => [
                    'id' => $account->id,
                    'name' => $account->account_name,
                    'account_number' => $account->account_number,
                ],
                'site' => [
                    'id' => $site->id,
                    'title' => $site->title,
                    'address' => $site->address,
                ],
                'tariff' => [
                    'id' => $tariff->id,
                    'name' => $tariff->template_name,
                    'billing_type' => $billingType,
                    'is_date_to_date' => $isDateToDate,
                    'vat_rate' => $tariff->getVatRate(),
                    'is_water' => (bool) $tariff->is_water,
                    'is_electricity' => (bool) $tariff->is_electricity,
                    // Water tiers from tariff template
                    'water_tiers' => $tariff->water_in ?? [],
                    // Fixed costs
                    'fixed_costs' => $tariff->fixed_costs ?? [],
                ],
                'period' => $periodInfo,
                'water' => $waterData,
                'electricity' => $electricityData,
                'payments' => $paymentsData,
                'totals' => [
                    'consumption_total' => round($totalCharges, 2),
                    'vat_amount' => round($totalVat, 2),
                    'vat_rate' => $tariff->getVatRate(),
                    'grand_total' => round($grandTotal, 2),
                    'total_paid' => round($totalPaid, 2),
                    'balance_due' => round($balanceDue, 2),
                ],
            ],
        ]);
    }

    /**
     * Get payments data for an account within a period.
     */
    private function getPaymentsData($account, $startDate, $endDate): array
    {
        $payments = Payment::where('account_id', $account->id)
            ->whereBetween('payment_date', [$startDate, $endDate])
            ->orderBy('payment_date', 'desc')
            ->get();

        $totalPaid = $payments->sum('amount');

        return [
            'items' => $payments->map(fn($p) => [
                'id' => $p->id,
                'amount' => (float) $p->amount,
                'payment_date' => $p->payment_date->toDateString(),
                'payment_method' => $p->payment_method,
                'reference' => $p->reference,
                'notes' => $p->notes,
            ])->toArray(),
            'total_paid' => (float) $totalPaid,
            'count' => $payments->count(),
        ];
    }

    /**
     * Add a payment for an account.
     */
    public function addPayment(Request $request): JsonResponse
    {
        $request->validate([
            'account_id' => 'required|exists:accounts,id',
            'amount' => 'required|numeric|min:0.01',
            'payment_date' => 'required|date',
            'payment_method' => 'nullable|string|max:50',
            'reference' => 'nullable|string|max:100',
            'notes' => 'nullable|string|max:500',
        ]);

        $payment = Payment::create([
            'account_id' => $request->account_id,
            'amount' => $request->amount,
            'payment_date' => $request->payment_date,
            'payment_method' => $request->payment_method ?? 'EFT',
            'reference' => $request->reference ?? 'PAY-' . time(),
            'notes' => $request->notes,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Payment added successfully',
            'data' => [
                'id' => $payment->id,
                'amount' => (float) $payment->amount,
                'payment_date' => $payment->payment_date->toDateString(),
                'payment_method' => $payment->payment_method,
                'reference' => $payment->reference,
            ],
        ]);
    }

    /**
     * Get all payments for an account.
     */
    public function getPayments(Account $account): JsonResponse
    {
        $payments = Payment::where('account_id', $account->id)
            ->orderBy('payment_date', 'desc')
            ->get();

        return response()->json([
            'success' => true,
            'data' => [
                'account_id' => $account->id,
                'account_name' => $account->account_name,
                'payments' => $payments->map(fn($p) => [
                    'id' => $p->id,
                    'amount' => (float) $p->amount,
                    'payment_date' => $p->payment_date->toDateString(),
                    'payment_method' => $p->payment_method,
                    'reference' => $p->reference,
                    'notes' => $p->notes,
                    'created_at' => $p->created_at->toDateTimeString(),
                ])->toArray(),
                'total_paid' => (float) $payments->sum('amount'),
            ],
        ]);
    }

    /**
     * Get meter data including readings and calculated charges.
     * For MONTHLY billing: calculates consumption from billing day to latest reading.
     * Opening reading on billing day is interpolated if no actual reading exists.
     */
    private function getMeterData(?Meter $meter, $tariff, string $type): array
    {
        if (!$meter) {
            return [
                'enabled' => false,
                'meter' => null,
                'readings' => [],
                'consumption' => 0,
                'charges' => ['total' => 0, 'breakdown' => []],
            ];
        }

        // Get all readings for this meter, ordered by date
        $readings = MeterReadings::where('meter_id', $meter->id)
            ->orderBy('reading_date', 'asc')
            ->get();

        if ($readings->isEmpty()) {
            return [
                'enabled' => true,
                'meter' => [
                    'id' => $meter->id,
                    'number' => $meter->meter_number,
                    'title' => $meter->meter_title,
                ],
                'readings' => [],
                'consumption' => 0,
                'charges' => ['total' => 0, 'breakdown' => []],
            ];
        }

        // Get billing day from tariff (default 20)
        $billingDay = $tariff->billing_day ?? 20;
        $now = Carbon::now();
        
        // Calculate current billing period start (the billing day of the current or previous month)
        $periodStart = $now->copy()->setDay($billingDay)->startOfDay();
        if ($now->day < $billingDay) {
            // We haven't reached billing day this month, so period started last month
            $periodStart->subMonth();
        }
        
        // Period end is the next billing day
        $periodEnd = $periodStart->copy()->addMonth();
        
        // Get the latest reading as closing
        $closingReading = $readings->last();
        $closingDate = Carbon::parse($closingReading->reading_date);
        $closingValue = $closingReading->reading_value;
        
        // Calculate opening reading VALUE for the billing day (interpolated if needed)
        $openingValue = $this->getReadingValueForDate($readings, $periodStart);
        $openingType = 'INTERPOLATED';
        
        // Check if there's an actual reading on the period start date
        $actualOpening = $readings->first(function($r) use ($periodStart) {
            return Carbon::parse($r->reading_date)->format('Y-m-d') === $periodStart->format('Y-m-d');
        });
        if ($actualOpening) {
            $openingValue = $actualOpening->reading_value;
            $openingType = 'ACTUAL';
        }
        
        // If no readings after period start, use 6-month average to estimate
        $hasReadingsAfterPeriodStart = $readings->filter(function($r) use ($periodStart) {
            return Carbon::parse($r->reading_date)->gt($periodStart);
        })->isNotEmpty();
        
        $estimatedClosing = false;
        if (!$hasReadingsAfterPeriodStart) {
            // No readings after period start - estimate using 6-month average
            $sixMonthAvg = $this->getSixMonthDailyAverage($readings);
            $daysSincePeriodStart = $periodStart->diffInDays($now);
            $closingValue = $openingValue + ($sixMonthAvg * $daysSincePeriodStart);
            $closingDate = $now;
            $estimatedClosing = true;
        }
        
        // Calculate consumption
        $consumption = max(0, $closingValue - $openingValue);
        
        // Calculate days in current reading period
        $daysBetween = $periodStart->diffInDays($closingDate);
        $dailyAverage = $daysBetween > 0 ? $consumption / $daysBetween : 0;
        
        // Calculate charges based on tariff
        $charges = $this->calculateChargesForConsumption($tariff, $consumption, $type);

        return [
            'enabled' => true,
            'meter' => [
                'id' => $meter->id,
                'number' => $meter->meter_number,
                'title' => $meter->meter_title,
            ],
            'readings' => $readings->map(fn($r) => [
                'id' => $r->id,
                'value' => $r->reading_value,
                'date' => $r->reading_date,
                'type' => $r->reading_type,
            ])->toArray(),
            'period_start' => $periodStart->toDateString(),
            'period_end' => $periodEnd->toDateString(),
            'opening_reading' => [
                'value' => round($openingValue, 0),
                'date' => $periodStart->toDateString(),
                'type' => $openingType,
            ],
            'closing_reading' => [
                'value' => round($closingValue, 0),
                'date' => $closingDate instanceof Carbon ? $closingDate->toDateString() : $closingDate,
                'type' => $estimatedClosing ? 'ESTIMATED' : ($closingReading->reading_type ?? 'ACTUAL'),
            ],
            'consumption' => round($consumption, 0),
            'consumption_kl' => $type === 'water' ? round($consumption / 1000, 2) : null,
            'consumption_kwh' => $type === 'electricity' ? round($consumption, 0) : null,
            'daily_average' => round($dailyAverage, 2),
            'daily_average_formatted' => $type === 'water' 
                ? round($dailyAverage, 0) . ' L' 
                : round($dailyAverage, 1) . ' kWh',
            'charges' => $charges,
            'applicable_tier' => $type === 'water' && !empty($charges['breakdown']) 
                ? $charges['breakdown'][0]['tier'] ?? null 
                : null,
        ];
    }

    /**
     * Get/interpolate reading value for a specific date.
     * If no exact reading exists, interpolates between surrounding readings.
     */
    private function getReadingValueForDate($readings, Carbon $targetDate): float
    {
        // Check for exact match first
        $exact = $readings->first(function($r) use ($targetDate) {
            return Carbon::parse($r->reading_date)->format('Y-m-d') === $targetDate->format('Y-m-d');
        });
        if ($exact) {
            return $exact->reading_value;
        }
        
        // Find readings before and after target date
        $before = null;
        $after = null;
        
        foreach ($readings as $reading) {
            $readingDate = Carbon::parse($reading->reading_date);
            if ($readingDate->lt($targetDate)) {
                $before = $reading;
            } elseif ($readingDate->gt($targetDate) && !$after) {
                $after = $reading;
                break;
            }
        }
        
        // If no reading before, use first reading
        if (!$before && $readings->isNotEmpty()) {
            return $readings->first()->reading_value;
        }
        
        // If no reading after, use last reading
        if (!$after) {
            return $before->reading_value;
        }
        
        // Interpolate between before and after
        $beforeDate = Carbon::parse($before->reading_date);
        $afterDate = Carbon::parse($after->reading_date);
        $totalDays = $beforeDate->diffInDays($afterDate);
        $daysFromBefore = $beforeDate->diffInDays($targetDate);
        
        if ($totalDays == 0) {
            return $before->reading_value;
        }
        
        $consumption = $after->reading_value - $before->reading_value;
        $dailyRate = $consumption / $totalDays;
        
        return $before->reading_value + ($dailyRate * $daysFromBefore);
    }

    /**
     * Calculate 6-month daily average consumption.
     * Used for estimation when no recent readings exist.
     */
    private function getSixMonthDailyAverage($readings): float
    {
        if ($readings->count() < 2) {
            return 0;
        }
        
        $sixMonthsAgo = Carbon::now()->subMonths(6);
        
        // Filter readings within last 6 months
        $recentReadings = $readings->filter(function($r) use ($sixMonthsAgo) {
            return Carbon::parse($r->reading_date)->gte($sixMonthsAgo);
        });
        
        if ($recentReadings->count() < 2) {
            // Fall back to all readings if not enough in 6 months
            $recentReadings = $readings;
        }
        
        $first = $recentReadings->first();
        $last = $recentReadings->last();
        
        $totalConsumption = $last->reading_value - $first->reading_value;
        $totalDays = Carbon::parse($first->reading_date)->diffInDays(Carbon::parse($last->reading_date));
        
        return $totalDays > 0 ? $totalConsumption / $totalDays : 0;
    }

    /**
     * Calculate charges for a given consumption based on tariff.
     */
    /**
     * Calculate ALL charges - water_in, water_out, additional, fixed, customer costs
     */
    private function calculateChargesForConsumption($tariff, float $consumption, string $type): array
    {
        $waterInTotal = 0;
        $waterOutTotal = 0;
        $additionalTotal = 0;
        $fixedTotal = 0;
        $customerTotal = 0;
        $breakdown = [];

        if ($type === 'water') {
            // WATER IN - Tiered
            if (!empty($tariff->water_in)) {
                $remaining = $consumption;
                $tierNum = 0;
                foreach ($tariff->water_in as $tier) {
                    $tierNum++;
                    if ($remaining <= 0) break;
                    $min = (float)($tier['min'] ?? 0);
                    $max = isset($tier['max']) && $tier['max'] !== '' ? (float)$tier['max'] : PHP_FLOAT_MAX;
                    $cost = (float)($tier['cost'] ?? 0);
                    $capacity = $max - $min;
                    $inTier = min($remaining, $capacity);
                    $kl = $inTier / 1000;
                    $charge = $kl * $cost;
                    $waterInTotal += $charge;
                    $breakdown[] = ['type'=>'water_in','tier'=>$tierNum,'label'=>'Tier '.$tierNum.' ('.($min/1000).'-'.($max===PHP_FLOAT_MAX?'ÃƒÂ¢Ã‹â€ Ã…Â¾':$max/1000).' kL)','units_kl'=>round($kl,2),'rate'=>$cost,'charge'=>round($charge,2)];
                    $remaining -= $inTier;
                }
            }

            // WATER OUT - Sewerage
            if (!empty($tariff->water_out)) {
                foreach ($tariff->water_out as $item) {
                    $pct = (float)($item['percentage'] ?? 100);
                    $cost = (float)($item['cost'] ?? 0);
                    $kl = ($consumption * $pct / 100) / 1000;
                    $charge = $kl * $cost;
                    $waterOutTotal += $charge;
                    $breakdown[] = ['type'=>'water_out','label'=>'Sewerage ('.$pct.'%)','units_kl'=>round($kl,2),'rate'=>$cost,'charge'=>round($charge,2)];
                }
            }

            // WATER OUT ADDITIONAL
            $woAdditional = $tariff->waterout_additional ?? [];
            if (!empty($woAdditional)) {
                foreach ($woAdditional as $item) {
                    $pct = (float)($item['percentage'] ?? 100);
                    $cost = (float)($item['cost'] ?? 0);
                    $title = $item['title'] ?? 'Additional';
                    $kl = ($consumption * $pct / 100) / 1000;
                    $charge = $kl * $cost;
                    $additionalTotal += $charge;
                    $breakdown[] = ['type'=>'additional','label'=>$title.' ('.$pct.'%)','units_kl'=>round($kl,2),'rate'=>$cost,'charge'=>round($charge,2)];
                }
            }
        } elseif ($type === 'electricity' && !empty($tariff->electricity)) {
            $remaining = $consumption;
            $tierNum = 0;
            foreach ($tariff->electricity as $tier) {
                $tierNum++;
                if ($remaining <= 0) break;
                $min = (float)($tier['min'] ?? 0);
                $max = isset($tier['max']) && $tier['max'] !== '' ? (float)$tier['max'] : PHP_FLOAT_MAX;
                $cost = (float)($tier['cost'] ?? 0);
                $capacity = $max - $min;
                $inTier = min($remaining, $capacity);
                $charge = $inTier * $cost;
                $waterInTotal += $charge;
                $breakdown[] = ['type'=>'electricity','tier'=>$tierNum,'label'=>'Tier '.$tierNum,'units'=>round($inTier,0),'rate'=>$cost,'charge'=>round($charge,2)];
                $remaining -= $inTier;
            }
        }

        // FIXED COSTS
        if (!empty($tariff->fixed_costs)) {
            foreach ($tariff->fixed_costs as $item) {
                $val = (float)($item['value'] ?? 0);
                $fixedTotal += $val;
                $breakdown[] = ['type'=>'fixed','label'=>$item['name'] ?? 'Fixed','charge'=>round($val,2)];
            }
        }

        // CUSTOMER COSTS
        if ($type === 'water' && !empty($tariff->customer_costs)) {
            foreach ($tariff->customer_costs as $item) {
                $val = (float)($item['value'] ?? 0);
                $customerTotal += $val;
                $breakdown[] = ['type'=>'customer','label'=>$item['name'] ?? 'Customer','charge'=>round($val,2)];
            }
        }

        $consumptionTotal = $waterInTotal + $waterOutTotal + $additionalTotal;
        $total = $consumptionTotal + $fixedTotal + $customerTotal;

        return [
            'total' => round($total, 2),
            'water_in_total' => round($waterInTotal, 2),
            'water_out_total' => round($waterOutTotal, 2),
            'additional_total' => round($additionalTotal, 2),
            'fixed_total' => round($fixedTotal, 2),
            'customer_total' => round($customerTotal, 2),
            'consumption_total' => round($consumptionTotal, 2),
            'breakdown' => $breakdown,
        ];
    }

    /**
     * Get period info based on billing type.
     */
    private function getPeriodInfo($account, $tariff): array
    {
        $isDateToDate = $tariff->isDateToDateBilling();

        if ($isDateToDate) {
            // For date-to-date, period is from first to last reading
            $meters = $account->meters;
            $firstReading = null;
            $lastReading = null;

            foreach ($meters as $meter) {
                $readings = MeterReadings::where('meter_id', $meter->id)
                    ->orderBy('reading_date', 'asc')
                    ->get();
                
                if ($readings->isNotEmpty()) {
                    $first = $readings->first();
                    $last = $readings->last();
                    
                    if (!$firstReading || Carbon::parse($first->reading_date)->lt(Carbon::parse($firstReading))) {
                        $firstReading = $first->reading_date;
                    }
                    if (!$lastReading || Carbon::parse($last->reading_date)->gt(Carbon::parse($lastReading))) {
                        $lastReading = $last->reading_date;
                    }
                }
            }

            $startDate = $firstReading ? Carbon::parse($firstReading) : Carbon::now()->startOfMonth();
            $endDate = $lastReading ? Carbon::parse($lastReading) : Carbon::now();
            $daysInPeriod = $startDate->diffInDays($endDate);
            
            return [
                'billing_type' => 'DATE_TO_DATE',
                'start_date' => $startDate->toDateString(),
                'end_date' => $endDate->toDateString(),
                'days_in_period' => $daysInPeriod,
                'can_close_period' => true,  // Date-to-date can always close
                'reading_due_date' => null,  // No fixed due date for date-to-date
                'days_until_due' => null,
            ];
        } else {
            // For monthly, use billing day configuration
            $billingDay = $tariff->billing_day ?? 15;
            $readDay = $tariff->read_day ?? 5;
            
            $now = Carbon::now();
            $startDate = $now->copy()->startOfMonth()->addDays($billingDay - 1)->subMonth();
            $endDate = $now->copy()->startOfMonth()->addDays($billingDay - 1);
            
            if ($now->day >= $billingDay) {
                $startDate = $now->copy()->startOfMonth()->addDays($billingDay - 1);
                $endDate = $now->copy()->addMonth()->startOfMonth()->addDays($billingDay - 1);
            }

            $dueDate = $endDate->copy()->subDays($readDay);
            $daysUntilDue = max(0, $now->diffInDays($dueDate, false));

            return [
                'billing_type' => 'MONTHLY',
                'start_date' => $startDate->toDateString(),
                'end_date' => $endDate->toDateString(),
                'days_in_period' => $startDate->diffInDays($endDate),
                'can_close_period' => false,  // Monthly closes automatically
                'reading_due_date' => $dueDate->toDateString(),
                'days_until_due' => $daysUntilDue,
            ];
        }
    }

    /**
     * Get projected bill for an account.
     *
     * @param Account $account
     * @return JsonResponse
     */
    public function getProjectedBill(Account $account): JsonResponse
    {
        $projection = $this->billingEngine->getProjectedMonthlyBill($account);

        return response()->json([
            'success' => true,
            'data' => $projection,
        ]);
    }

    /**
     * Get daily consumption for a meter.
     *
     * @param Meter $meter
     * @return JsonResponse
     */
    public function getDailyConsumption(Meter $meter): JsonResponse
    {
        $dailyConsumption = $this->billingEngine->getDailyConsumption($meter);

        return response()->json([
            'success' => true,
            'data' => [
                'meter_id' => $meter->id,
                'meter_title' => $meter->meter_title,
                'daily_consumption' => $dailyConsumption,
            ],
        ]);
    }

    /**
     * Get billing history for an account.
     *
     * @param Request $request
     * @param Account $account
     * @return JsonResponse
     */
    public function getBillingHistory(Request $request, Account $account): JsonResponse
    {
        $limit = $request->input('limit', 12);
        $history = $this->billingEngine->getBillingHistory($account, $limit);

        return response()->json([
            'success' => true,
            'data' => $history,
        ]);
    }

    /**
     * Get the tariff template for an account.
     *
     * @param Account $account
     * @return JsonResponse
     */
    public function getAccountTariff(Account $account): JsonResponse
    {
        $tariff = $account->tariffTemplate;

        if (!$tariff) {
            return response()->json([
                'success' => false,
                'message' => 'No tariff template assigned to this account.',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => [
                'id' => $tariff->id,
                'template_name' => $tariff->template_name,
                'billing_type' => $tariff->billing_type ?? 'MONTHLY',
                'vat_rate' => $tariff->getVatRate(),
                'billing_day' => $tariff->billing_day,
                'read_day' => $tariff->read_day,
                'is_active' => $tariff->is_active,
                'effective_from' => $tariff->effective_from,
                'effective_to' => $tariff->effective_to,
                'region' => $tariff->region ? [
                    'id' => $tariff->region->id,
                    'region_name' => $tariff->region->region_name,
                ] : null,
            ],
        ]);
    }

    /**
     * Get tariff tiers for an account's tariff template.
     *
     * @param Account $account
     * @return JsonResponse
     */
    public function getTariffTiers(Account $account): JsonResponse
    {
        $tariff = $account->tariffTemplate;

        if (!$tariff) {
            return response()->json([
                'success' => false,
                'message' => 'No tariff template assigned to this account.',
            ], 404);
        }

        $tiers = $tariff->tiers()->orderBy('tier_number')->get();

        return response()->json([
            'success' => true,
            'data' => [
                'tariff_id' => $tariff->id,
                'tariff_name' => $tariff->template_name,
                'tiers' => $tiers->map(function ($tier) {
                    return [
                        'tier_number' => $tier->tier_number,
                        'min_units' => (float) $tier->min_units,
                        'max_units' => $tier->max_units ? (float) $tier->max_units : null,
                        'rate_per_unit' => (float) $tier->rate_per_unit,
                    ];
                }),
            ],
        ]);
    }

    /**
     * Get read date and billing date for an account.
     *
     * @param Account $account
     * @return JsonResponse
     */
    public function getBillingDates(Account $account): JsonResponse
    {
        $readDate = $this->billingEngine->getReadDate($account);
        $billingDate = $this->billingEngine->getBillingDate($account);

        return response()->json([
            'success' => true,
            'data' => [
                'read_date' => $readDate->toDateString(),
                'billing_date' => $billingDate->toDateString(),
                'days_until_reading' => now()->diffInDays($readDate, false),
                'days_until_billing' => now()->diffInDays($billingDate, false),
            ],
        ]);
    }

    /**
     * Calculate a bill for given readings.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function calculateBill(Request $request): JsonResponse
    {
        $request->validate([
            'account_id' => 'required|exists:accounts,id',
            'opening_reading_id' => 'required|exists:meter_readings,id',
            'closing_reading_id' => 'required|exists:meter_readings,id',
        ]);

        $account = Account::findOrFail($request->account_id);
        $openingReading = \App\Models\MeterReadings::findOrFail($request->opening_reading_id);
        $closingReading = \App\Models\MeterReadings::findOrFail($request->closing_reading_id);

        $result = $this->billingEngine->calculateCharge($account, $openingReading, $closingReading);

        return response()->json([
            'success' => true,
            'data' => $result->toArray(),
        ]);
    }

    /**
     * Get billing history for the authenticated user.
     * Shows billing periods with payments and balances.
     *
     * @return JsonResponse
     */
    public function getBillingHistoryForUser(): JsonResponse
    {
        $user = Auth::user();
        
        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'User not authenticated',
            ], 401);
        }

        // Get user's site and account
        $site = Site::where('user_id', $user->id)->with(['accounts.meters.readings', 'accounts.tariffTemplate'])->first();
        
        if (!$site) {
            return response()->json([
                'success' => false,
                'message' => 'No site found for user',
            ], 404);
        }

        $account = $site->accounts->first();
        if (!$account) {
            return response()->json([
                'success' => false,
                'message' => 'No account found',
            ], 404);
        }

        $tariff = $account->tariffTemplate;

        // Get unique reading dates across all meters
        $readingDates = collect();
        foreach ($account->meters as $meter) {
            foreach ($meter->readings as $reading) {
                $dateStr = $reading->reading_date->format('Y-m-d');
                if (!$readingDates->contains($dateStr)) {
                    $readingDates->push($dateStr);
                }
            }
        }
        $readingDates = $readingDates->sort()->values();

        // Get all payments
        $allPayments = Payment::where('account_id', $account->id)
            ->orderBy('payment_date', 'asc')
            ->get();

        // Build billing periods from consecutive reading dates
        $periods = [];
        $runningBalance = 0;
        $vatRate = $tariff ? $tariff->getVatRate() : 15;

        for ($i = 0; $i < count($readingDates) - 1; $i++) {
            $prevDateStr = $readingDates[$i];
            $currDateStr = $readingDates[$i + 1];
            $prevDate = Carbon::parse($prevDateStr);
            $currDate = Carbon::parse($currDateStr);
            $days = $prevDate->diffInDays($currDate);

            // Calculate consumption charge for ALL meters in this period
            $totalConsumption = 0;
            $totalUnitsUsed = 0;  // Track actual units (liters/kWh)
            $meterDetails = [];
            
            
            foreach ($account->meters as $meter) {
                // Find readings on the start and end dates for this meter
                $startReading = $meter->readings->first(fn($r) => $r->reading_date->format('Y-m-d') === $prevDateStr);
                $endReading = $meter->readings->first(fn($r) => $r->reading_date->format('Y-m-d') === $currDateStr);
                
                if ($startReading && $endReading) {
                    $result = $this->billingEngine->calculateCharge($account, $startReading, $endReading);
                    $totalConsumption += $result->tieredCharge;
                    
                    // Calculate units used for this meter
                    $unitsUsed = max(0, $endReading->reading_value - $startReading->reading_value);
                    $totalUnitsUsed += $unitsUsed;
                    
                    // Get meter type for display
                    $meterType = $meter->meterType ? $meter->meterType->title : 'Unknown';
                    $dailyUsage = $days > 0 ? $unitsUsed / $days : 0;
                    
                    $meterDetails[] = [
                        'meter_id' => $meter->id,
                        'meter_title' => $meter->meter_title,
                        'meter_type' => $meterType,
                        'opening_reading' => $startReading->reading_value,
                        'closing_reading' => $endReading->reading_value,
                        'units_used' => round($unitsUsed, 2),
                        'daily_usage' => round($dailyUsage, 2),
                        'charge' => round($result->tieredCharge, 2),
                    ];
                }
            }
            
            // Calculate daily usage across all meters
            $dailyUsageTotal = $days > 0 ? $totalUnitsUsed / $days : 0;
            
            // Add VAT
            $vatAmount = $totalConsumption * ($vatRate / 100);
            $consumptionWithVat = $totalConsumption + $vatAmount;

            // Get payments within this period (between start and end dates)
            $periodPayments = $allPayments->filter(function($p) use ($prevDate, $currDate) {
                $payDate = Carbon::parse($p->payment_date);
                return $payDate->gt($prevDate) && $payDate->lte($currDate);
            })->map(fn($p) => [
                'id' => $p->id,
                'date' => $p->payment_date->format('Y-m-d'),
                'amount' => (float) $p->amount,
                'method' => $p->payment_method,
                'reference' => $p->reference,
            ])->values()->toArray();

            $totalPayments = collect($periodPayments)->sum('amount');
            
            // Calculate balance for this period - carry forward both debits AND credits
            $balanceBF = $runningBalance; // Can be positive (owed) or negative (credit)
            $periodOwing = $consumptionWithVat + $balanceBF; // Credit reduces the owing
            $balance = $periodOwing - $totalPayments;
            // Month-to-month: balance not carried forward

            $periods[] = [
                'start_date' => $prevDate->format('Y-m-d'),
                'end_date' => $currDate->format('Y-m-d'),
                'days' => $days,
                // Usage data
                'total_used' => round($totalUnitsUsed, 2),
                'daily_usage' => round($dailyUsageTotal, 2),
                'meters' => $meterDetails,
                // Charges
                'consumption_charge' => round($consumptionWithVat, 2),
                'balance_bf' => 0,
                'period_total' => round($consumptionWithVat, 2),
                'payments' => $periodPayments,
                'total_payments' => round($totalPayments, 2),
                'balance' => round($consumptionWithVat - $totalPayments, 2),
            ];
        }

        // Reverse to show most recent first
        $periods = array_reverse($periods);

        // The FIRST period (after reversal) is the CURRENT OPEN period - shown on Dashboard
        // Remove it from Accounts page - only show CLOSED periods
        $currentPeriod = null;
        $closedPeriods = $periods;
        
        if (count($periods) > 0) {
            $currentPeriod = array_shift($closedPeriods); // Remove and store current period
        }

        // Get the last closed period end date (first in the closed periods list)
        $lastClosedPeriodEnd = count($closedPeriods) > 0 ? $closedPeriods[0]['end_date'] : null;

        // Assign ALL payments made after the last closed period TO the last closed period
        // This ensures payments are always shown INSIDE the period container
        if ($lastClosedPeriodEnd && count($closedPeriods) > 0) {
            $paymentsAfterLastPeriod = $allPayments->filter(function($p) use ($lastClosedPeriodEnd) {
                return Carbon::parse($p->payment_date)->gt(Carbon::parse($lastClosedPeriodEnd));
            });

            // Add these payments to the last closed period's payments array
            foreach ($paymentsAfterLastPeriod as $payment) {
                $closedPeriods[0]['payments'][] = [
                    'id' => $payment->id,
                    'date' => $payment->payment_date->format('Y-m-d'),
                    'amount' => (float) $payment->amount,
                    'method' => $payment->payment_method,
                    'reference' => $payment->reference,
                ];
            }

            // Recalculate the last closed period's totals with additional payments
            $additionalPaymentsTotal = $paymentsAfterLastPeriod->sum('amount');
            $closedPeriods[0]['total_payments'] += $additionalPaymentsTotal;
            $closedPeriods[0]['balance'] -= $additionalPaymentsTotal;
        }

        // Total owing is the balance of the last closed period (after all payments)
        $totalOwing = count($closedPeriods) > 0 ? $closedPeriods[0]['balance'] : 0;

        return response()->json([
            'success' => true,
            'data' => [
                'account_id' => $account->id,
                'user_name' => $user->name,
                'account_name' => $account->account_name,
                'account_number' => $account->account_number,
                'total_owing' => $totalOwing,
                'periods' => $closedPeriods, // Only closed periods with all payments inside
            ],
        ]);
    }
}
