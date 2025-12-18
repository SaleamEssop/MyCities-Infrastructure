<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Account;
use App\Models\Meter;
use App\Models\MeterReadings;
use App\Models\MeterType;
use App\Models\Payment;
use App\Models\Regions;
use App\Models\RegionsAccountTypeCost;
use App\Models\Site;
use App\Models\User;
use App\Services\BillingEngine;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class UserAccountManagerController extends Controller
{
    // Default meter category ID
    private const DEFAULT_METER_CATEGORY_ID = 1;

    /**
     * Display the manager dashboard
     */
    public function index()
    {
        $users = User::withCount('sites')
            ->with(['sites.region'])
            ->get();
        $regions = Regions::all();
        $meterTypes = MeterType::all();
        
        return view('admin.user-accounts.manager', [
            'users' => $users,
            'regions' => $regions,
            'meterTypes' => $meterTypes,
        ]);
    }

    /**
     * Display the account billing page (Blade-based, non-Vue)
     */
    public function showAccountBilling($accountId)
    {
        $account = Account::with(['tariffTemplate', 'meters.readings', 'site.user'])->findOrFail($accountId);
        $site = $account->site;
        $user = $site ? $site->user : User::find($account->user_id);
        $tariff = $account->tariffTemplate;
        $billingEngine = app(BillingEngine::class);

        // Get all readings sorted by date
        $allReadings = collect();
        foreach ($account->meters as $meter) {
            foreach ($meter->readings as $reading) {
                $allReadings->push([
                    'meter_id' => $meter->id,
                    'meter_type' => $meter->meterTypes?->title ?? 'Unknown',
                    'reading' => $reading,
                ]);
            }
        }
        $allReadings = $allReadings->sortBy(fn($r) => $r['reading']->reading_date);

        // Get all payments
        $allPayments = Payment::where('account_id', $accountId)
            ->orderBy('payment_date', 'asc')
            ->get();

        // Build billing periods from consecutive readings
        $periods = [];
        $previousReading = null;
        $runningBalance = 0;

        foreach ($allReadings as $readingData) {
            $reading = $readingData['reading'];
            
            if ($previousReading) {
                $prevDate = \Carbon\Carbon::parse($previousReading['reading']->reading_date);
                $currDate = \Carbon\Carbon::parse($reading->reading_date);
                $days = $prevDate->diffInDays($currDate);

                // Calculate consumption charge for this period
                $result = $billingEngine->calculateCharge($account, $previousReading['reading'], $reading);
                $consumptionCharge = $result->tieredCharge;
                
                // Add VAT
                $vatRate = $tariff ? $tariff->getVatRate() : 15;
                $vatAmount = $consumptionCharge * ($vatRate / 100);
                $periodTotal = $consumptionCharge + $vatAmount;

                // Get payments within this period
                $periodPayments = $allPayments->filter(function($p) use ($prevDate, $currDate) {
                    $payDate = \Carbon\Carbon::parse($p->payment_date);
                    return $payDate->gte($prevDate) && $payDate->lte($currDate);
                })->map(fn($p) => [
                    'id' => $p->id,
                    'date' => $p->payment_date->format('Y-m-d'),
                    'amount' => (float) $p->amount,
                    'method' => $p->payment_method,
                    'reference' => $p->reference,
                ])->values()->toArray();

                $totalPayments = collect($periodPayments)->sum('amount');
                
                // Calculate balance for this period
                $balanceBF = $runningBalance > 0 ? $runningBalance : 0;
                $periodOwing = $periodTotal + $balanceBF;
                $balance = $periodOwing - $totalPayments;
                $runningBalance = $balance;

                $periods[] = [
                    'start_date' => $prevDate->format('Y-m-d'),
                    'end_date' => $currDate->format('Y-m-d'),
                    'days' => $days,
                    'consumption_charge' => round($consumptionCharge + $vatAmount, 2),
                    'balance_bf' => round($balanceBF, 2),
                    'period_total' => round($periodOwing, 2),
                    'payments' => $periodPayments,
                    'total_payments' => round($totalPayments, 2),
                    'balance' => round($balance, 2),
                ];
            }

            $previousReading = $readingData;
        }

        // Reverse to show most recent first
        $periods = array_reverse($periods);

        // Calculate total owing (current balance from last period)
        $totalOwing = count($periods) > 0 ? $periods[0]['balance'] : 0;

        // Also count payments made AFTER the last period (recent payments)
        $lastPeriodEnd = count($periods) > 0 ? $periods[0]['end_date'] : null;
        $recentPayments = [];
        
        if ($lastPeriodEnd) {
            $recentPayments = $allPayments->filter(function($p) use ($lastPeriodEnd) {
                return \Carbon\Carbon::parse($p->payment_date)->gt(\Carbon\Carbon::parse($lastPeriodEnd));
            })->map(fn($p) => [
                'id' => $p->id,
                'date' => $p->payment_date->format('Y-m-d'),
                'amount' => (float) $p->amount,
                'method' => $p->payment_method,
                'reference' => $p->reference,
            ])->values()->toArray();
            
            $recentPaymentsTotal = collect($recentPayments)->sum('amount');
            $totalOwing = $totalOwing - $recentPaymentsTotal;
        }

        return view('admin.account-billing', [
            'account' => $account,
            'user' => $user,
            'site' => $site,
            'tariff' => $tariff,
            'periods' => $periods,
            'totalOwing' => $totalOwing,
            'recentPayments' => $recentPayments,
        ]);
    }

    /**
     * Search users with filters
     */
    public function search(Request $request)
    {
        $query = User::withCount('sites');
        
        // Search by name
        if ($request->filled('name')) {
            $query->where('name', 'like', '%' . $request->name . '%');
        }
        
        // Search by address (through sites)
        if ($request->filled('address')) {
            $query->whereHas('sites', function($q) use ($request) {
                $q->where('address', 'like', '%' . $request->address . '%');
            });
        }
        
        // Search by phone
        if ($request->filled('phone')) {
            $query->where('contact_number', 'like', '%' . $request->phone . '%');
        }
        
        // Filter by user type
        if ($request->filled('user_type')) {
            if ($request->user_type === 'test') {
                $query->where('email', 'like', '%@test.com');
            } elseif ($request->user_type === 'real') {
                $query->where('email', 'not like', '%@test.com');
            }
        }
        
        $users = $query->get();
        
        return response()->json([
            'status' => 200,
            'data' => $users
        ]);
    }

    /**
     * Get user data with all related entities for editing
     */
    public function getUserData($id)
    {
        $user = User::with([
            'sites.accounts.meters.readings' => function($query) {
                $query->orderBy('reading_date', 'desc');
            },
            'sites.accounts.tariffTemplate',
            'sites.region'
        ])->find($id);
        
        if (!$user) {
            return response()->json(['status' => 404, 'message' => 'User not found']);
        }
        
        return response()->json(['status' => 200, 'data' => $user]);
    }

    /**
     * Update user basic details
     */
    public function updateUser(Request $request, $id)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email,' . $id,
            'contact_number' => 'required|string|max:20',
        ]);

        try {
            $user = User::findOrFail($id);
            
            $user->name = $request->name;
            $user->email = $request->email;
            $user->contact_number = $request->contact_number;
            
            if ($request->filled('password')) {
                $user->password = Hash::make($request->password);
            }
            
            $user->save();
            
            return response()->json([
                'status' => 200, 
                'message' => 'User updated successfully'
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'status' => 500, 
                'message' => 'Error updating user: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Update account details
     */
    public function updateAccount(Request $request, $id)
    {
        try {
            $account = Account::findOrFail($id);
            
            // Update editable fields (only if provided)
            if ($request->has('account_name')) {
                $account->account_name = $request->account_name;
            }
            if ($request->has('name_on_bill')) {
                $account->name_on_bill = $request->name_on_bill;
            }
            if ($request->has('water_email')) {
                $account->water_email = $request->water_email;
            }
            if ($request->has('electricity_email')) {
                $account->electricity_email = $request->electricity_email;
            }
            if ($request->has('bill_day')) {
                $account->bill_day = $request->bill_day;
            }
            if ($request->has('read_day')) {
                $account->read_day = $request->read_day;
            }
            if ($request->has('billing_date')) {
                $account->billing_date = $request->billing_date;
            }
            
            // Handle customer editable costs (stored as JSON)
            if ($request->has('customer_costs')) {
                $account->customer_costs = $request->customer_costs;
            }
            
            $account->save();
            
            return response()->json([
                'status' => 200, 
                'message' => 'Account updated successfully'
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'status' => 500, 
                'message' => 'Error updating account: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Add a meter to an account
     */
    public function addMeter(Request $request)
    {
        $request->validate([
            'account_id' => 'required|exists:accounts,id',
            'meter_type_id' => 'required|exists:meter_types,id',
            'meter_title' => 'required|string|max:255',
            'meter_number' => 'required|string|max:50',
        ]);

        try {
            $meter = Meter::create([
                'account_id' => $request->account_id,
                'meter_type_id' => $request->meter_type_id,
                'meter_category_id' => $request->meter_category_id ?? self::DEFAULT_METER_CATEGORY_ID,
                'meter_title' => $request->meter_title,
                'meter_number' => $request->meter_number,
            ]);
            
            // Add initial reading if provided
            if ($request->filled('initial_reading')) {
                MeterReadings::create([
                    'meter_id' => $meter->id,
                    'reading_date' => $request->initial_reading_date ?? now()->format('Y-m-d'),
                    'reading_value' => $request->initial_reading,
                ]);
            }
            
            return response()->json([
                'status' => 200, 
                'message' => 'Meter added successfully',
                'meter_id' => $meter->id
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'status' => 500, 
                'message' => 'Error adding meter: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Update meter details
     */
    public function updateMeter(Request $request, $id)
    {
        $request->validate([
            'meter_title' => 'required|string|max:255',
            'meter_number' => 'required|string|max:50',
        ]);

        try {
            $meter = Meter::findOrFail($id);
            
            $meter->meter_title = $request->meter_title;
            $meter->meter_number = $request->meter_number;
            $meter->meter_type_id = $request->meter_type_id;
            
            $meter->save();
            
            return response()->json([
                'status' => 200, 
                'message' => 'Meter updated successfully'
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'status' => 500, 
                'message' => 'Error updating meter: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Delete a meter
     */
    public function deleteMeter($id)
    {
        try {
            $meter = Meter::findOrFail($id);
            $meter->delete();
            
            return response()->json([
                'status' => 200, 
                'message' => 'Meter deleted successfully'
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'status' => 500, 
                'message' => 'Error deleting meter: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Add a reading to a meter with validation
     */
    public function addReading(Request $request)
    {
        $request->validate([
            'meter_id' => 'required|exists:meters,id',
            'reading_date' => 'required|date',
            'reading_value' => 'required|string', // String to preserve leading zeros for water meters
        ]);

        try {
            $meter = Meter::findOrFail($request->meter_id);
            
            // Get the previous reading for validation
            $previousReading = MeterReadings::where('meter_id', $request->meter_id)
                ->orderBy('reading_date', 'desc')
                ->first();
            
            // Validate date is not earlier than previous reading
            if ($previousReading) {
                $newDate = strtotime($request->reading_date);
                $prevDate = strtotime($previousReading->reading_date);
                
                if ($newDate < $prevDate) {
                    return response()->json([
                        'status' => 400,
                        'message' => 'Reading date cannot be earlier than the previous reading date (' . $previousReading->reading_date . ')'
                    ], 400);
                }
                
                // Validate reading value is not lower than previous
                $newValue = floatval($request->reading_value);
                $prevValue = floatval($previousReading->reading_value);
                
                if ($newValue < $prevValue) {
                    return response()->json([
                        'status' => 400,
                        'message' => 'Reading value cannot be lower than the previous reading (' . $previousReading->reading_value . ')'
                    ], 400);
                }
            }
            
            $reading = MeterReadings::create([
                'meter_id' => $request->meter_id,
                'reading_date' => $request->reading_date,
                'reading_value' => $request->reading_value,
            ]);
            
            return response()->json([
                'status' => 200, 
                'message' => 'Reading added successfully',
                'reading_id' => $reading->id
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'status' => 500, 
                'message' => 'Error adding reading: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get readings history for a meter
     */
    public function getReadings($meterId)
    {
        $readings = MeterReadings::where('meter_id', $meterId)
            ->orderBy('reading_date', 'desc')
            ->get();
        
        return response()->json([
            'status' => 200,
            'data' => $readings
        ]);
    }

    /**
     * Delete a user and all related data completely
     */
    public function deleteUser($id)
    {
        DB::beginTransaction();
        
        try {
            $user = User::with(['sites.accounts.meters.readings', 'sites.accounts.payments'])->findOrFail($id);
            
            // Log what will be deleted
            $deletionSummary = [
                'sites' => $user->sites->count(),
                'accounts' => 0,
                'meters' => 0,
                'readings' => 0,
                'payments' => 0,
            ];
            
            foreach ($user->sites as $site) {
                $deletionSummary['accounts'] += $site->accounts->count();
                foreach ($site->accounts as $account) {
                    $deletionSummary['meters'] += $account->meters->count();
                    $deletionSummary['payments'] += $account->payments->count();
                    foreach ($account->meters as $meter) {
                        $deletionSummary['readings'] += $meter->readings->count();
                    }
                }
            }
            
            // Delete user (cascade will handle the rest)
            $user->delete();
            
            // Also delete any API tokens
            DB::table('personal_access_tokens')
                ->where('tokenable_type', User::class)
                ->where('tokenable_id', $id)
                ->delete();
            
            DB::commit();
            
            return response()->json([
                'status' => 200, 
                'message' => "User '{$user->name}' and all associated data deleted successfully. Removed: {$deletionSummary['sites']} site(s), {$deletionSummary['accounts']} account(s), {$deletionSummary['meters']} meter(s), {$deletionSummary['readings']} reading(s), {$deletionSummary['payments']} payment(s)."
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'status' => 500, 
                'message' => 'Error deleting user: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get tariff templates by region
     */
    public function getTariffTemplatesByRegion($regionId)
    {
        $templates = RegionsAccountTypeCost::where('region_id', $regionId)
            ->where('is_active', 1)
            ->get(['id', 'template_name', 'is_water', 'is_electricity', 'start_date', 'end_date']);
        
        return response()->json([
            'status' => 200,
            'data' => $templates
        ]);
    }

    /**
     * Get account billing summary with payments
     */
    public function getAccountBilling($accountId)
    {
        try {
            $account = Account::with(['tariffTemplate', 'meters.readings'])->findOrFail($accountId);
            $tariff = $account->tariffTemplate;
            
            // Get billing period dates from meter readings
            $allReadings = collect();
            foreach ($account->meters as $meter) {
                $allReadings = $allReadings->merge($meter->readings);
            }
            $allReadings = $allReadings->sortBy('reading_date');
            
            // Format dates as simple Y-m-d strings
            $firstReading = $allReadings->first();
            $lastReading = $allReadings->last();
            $periodStart = $firstReading ? \Carbon\Carbon::parse($firstReading->reading_date)->format('Y-m-d') : now()->startOfMonth()->format('Y-m-d');
            $periodEnd = $lastReading ? \Carbon\Carbon::parse($lastReading->reading_date)->format('Y-m-d') : now()->format('Y-m-d');
            
            // Get payments for this period
            $payments = Payment::where('account_id', $accountId)
                ->whereBetween('payment_date', [$periodStart, $periodEnd])
                ->orderBy('payment_date', 'desc')
                ->get();
            
            // Calculate consumption and charges using billing engine
            $billingEngine = app(BillingEngine::class);
            $totalCharge = 0;
            $consumptionBreakdown = [];
            
            foreach ($account->meters as $meter) {
                $readings = $meter->readings->sortBy('reading_date');
                $openingReading = $readings->first();
                $closingReading = $readings->last();
                
                if ($openingReading && $closingReading && $openingReading->id !== $closingReading->id) {
                    $result = $billingEngine->calculateCharge($account, $openingReading, $closingReading);
                    $totalCharge += $result->tieredCharge;
                    
                    $consumptionBreakdown[] = [
                        'meter' => $meter->meter_title,
                        'type' => $meter->meterTypes?->title ?? 'Unknown',
                        'opening' => $openingReading->reading_value,
                        'closing' => $closingReading->reading_value,
                        'consumption' => $result->consumption,
                        'charge' => $result->tieredCharge,
                    ];
                }
            }
            
            // Calculate VAT and total
            $vatRate = $tariff ? $tariff->getVatRate() : 15;
            $vatAmount = $totalCharge * ($vatRate / 100);
            $grandTotal = $totalCharge + $vatAmount;
            $totalPaid = $payments->sum('amount');
            $balanceDue = $grandTotal - $totalPaid;
            
            return response()->json([
                'status' => 200,
                'data' => [
                    'account' => [
                        'id' => $account->id,
                        'name' => $account->account_name,
                        'number' => $account->account_number,
                    ],
                    'tariff' => $tariff ? [
                        'name' => $tariff->template_name,
                        'billing_type' => $tariff->billing_type ?? 'MONTHLY',
                        'vat_rate' => $vatRate,
                    ] : null,
                    'period' => [
                        'start' => $periodStart,
                        'end' => $periodEnd,
                    ],
                    'consumption' => $consumptionBreakdown,
                    'payments' => $payments->map(fn($p) => [
                        'id' => $p->id,
                        'amount' => (float) $p->amount,
                        'date' => $p->payment_date->format('Y-m-d'),
                        'method' => $p->payment_method,
                        'reference' => $p->reference,
                        'notes' => $p->notes,
                    ])->toArray(),
                    'totals' => [
                        'consumption' => round($totalCharge, 2),
                        'vat' => round($vatAmount, 2),
                        'grand_total' => round($grandTotal, 2),
                        'total_paid' => round($totalPaid, 2),
                        'balance_due' => round($balanceDue, 2),
                    ],
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 500,
                'message' => 'Error fetching billing data: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Add a payment for an account
     */
    public function addPayment(Request $request)
    {
        $request->validate([
            'account_id' => 'required|exists:accounts,id',
            'amount' => 'required|numeric|min:0.01',
            'payment_date' => 'required|date',
            'payment_method' => 'nullable|string|max:50',
            'reference' => 'nullable|string|max:100',
            'notes' => 'nullable|string|max:500',
        ]);

        try {
            $payment = Payment::create([
                'account_id' => $request->account_id,
                'amount' => $request->amount,
                'payment_date' => $request->payment_date,
                'payment_method' => $request->payment_method ?? 'EFT',
                'reference' => $request->reference ?? 'PAY-' . time(),
                'notes' => $request->notes,
            ]);

            // If it's a form submission (not AJAX), redirect back
            if (!$request->expectsJson()) {
                return redirect()->route('user-accounts.billing', ['accountId' => $request->account_id])
                    ->with('success', 'Payment of R' . number_format($payment->amount, 2) . ' recorded successfully!');
            }

            return response()->json([
                'status' => 200,
                'message' => 'Payment added successfully',
                'data' => [
                    'id' => $payment->id,
                    'amount' => (float) $payment->amount,
                    'date' => $payment->payment_date->format('Y-m-d'),
                    'method' => $payment->payment_method,
                    'reference' => $payment->reference,
                ],
            ]);
        } catch (\Exception $e) {
            if (!$request->expectsJson()) {
                return redirect()->back()->with('error', 'Error adding payment: ' . $e->getMessage());
            }
            
            return response()->json([
                'status' => 500,
                'message' => 'Error adding payment: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Delete a payment
     */
    public function deletePayment($id)
    {
        try {
            $payment = Payment::findOrFail($id);
            $payment->delete();

            return response()->json([
                'status' => 200,
                'message' => 'Payment deleted successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 500,
                'message' => 'Error deleting payment: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get billing history with periods for mobile preview
     */
    public function getBillingHistory($accountId)
    {
        try {
            $account = Account::with(['tariffTemplate', 'meters.readings'])->findOrFail($accountId);
            $user = User::find($account->user_id);
            $tariff = $account->tariffTemplate;
            $billingEngine = app(BillingEngine::class);

            // Get all readings sorted by date
            $allReadings = collect();
            foreach ($account->meters as $meter) {
                foreach ($meter->readings as $reading) {
                    $allReadings->push([
                        'meter_id' => $meter->id,
                        'meter_type' => $meter->meterTypes?->title ?? 'Unknown',
                        'reading' => $reading,
                    ]);
                }
            }
            $allReadings = $allReadings->sortBy(fn($r) => $r['reading']->reading_date);

            // Get all payments
            $allPayments = Payment::where('account_id', $accountId)
                ->orderBy('payment_date', 'asc')
                ->get();

            // Build billing periods from consecutive readings
            $periods = [];
            $previousReading = null;
            $runningBalance = 0;

            foreach ($allReadings as $readingData) {
                $reading = $readingData['reading'];
                
                if ($previousReading) {
                    $prevDate = \Carbon\Carbon::parse($previousReading['reading']->reading_date);
                    $currDate = \Carbon\Carbon::parse($reading->reading_date);
                    $days = $prevDate->diffInDays($currDate);

                    // Calculate consumption charge for this period
                    $result = $billingEngine->calculateCharge($account, $previousReading['reading'], $reading);
                    $consumptionCharge = $result->tieredCharge;
                    
                    // Add VAT
                    $vatRate = $tariff ? $tariff->getVatRate() : 15;
                    $vatAmount = $consumptionCharge * ($vatRate / 100);
                    $periodTotal = $consumptionCharge + $vatAmount;

                    // Get payments within this period
                    $periodPayments = $allPayments->filter(function($p) use ($prevDate, $currDate) {
                        $payDate = \Carbon\Carbon::parse($p->payment_date);
                        return $payDate->gte($prevDate) && $payDate->lte($currDate);
                    })->map(fn($p) => [
                        'id' => $p->id,
                        'date' => $p->payment_date->format('Y-m-d'),
                        'amount' => (float) $p->amount,
                        'method' => $p->payment_method,
                        'reference' => $p->reference,
                    ])->values()->toArray();

                    $totalPayments = collect($periodPayments)->sum('amount');
                    
                    // Calculate balance for this period
                    $balanceBF = $runningBalance > 0 ? $runningBalance : 0;
                    $periodOwing = $periodTotal + $balanceBF;
                    $balance = $periodOwing - $totalPayments;
                    $runningBalance = $balance;

                    $periods[] = [
                        'start_date' => $prevDate->format('Y-m-d'),
                        'end_date' => $currDate->format('Y-m-d'),
                        'days' => $days,
                        'consumption_charge' => round($consumptionCharge + $vatAmount, 2),
                        'balance_bf' => round($balanceBF, 2),
                        'period_total' => round($periodOwing, 2),
                        'payments' => $periodPayments,
                        'total_payments' => round($totalPayments, 2),
                        'balance' => round($balance, 2),
                    ];
                }

                $previousReading = $readingData;
            }

            // Reverse to show most recent first
            $periods = array_reverse($periods);

            // Calculate total owing (current balance)
            $totalOwing = count($periods) > 0 ? $periods[0]['balance'] : 0;

            return response()->json([
                'status' => 200,
                'data' => [
                    'user_name' => $user ? $user->name : 'Unknown',
                    'account_name' => $account->account_name,
                    'account_number' => $account->account_number,
                    'total_owing' => max(0, $totalOwing),
                    'periods' => $periods,
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 500,
                'message' => 'Error fetching billing history: ' . $e->getMessage()
            ], 500);
        }
    }
}
