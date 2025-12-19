<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Account;
use App\Models\Regions;
use App\Models\RegionsAccountTypeCost;
use App\Services\BillingEngine;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * SetupController - Handles account setup and billing preview for the webapp.
 * 
 * This controller provides:
 * - Region and tariff listing for account setup
 * - Tariff details including editable costs
 * - Bill preview using the BillingEngine
 * - Account creation and management
 */
class SetupController extends Controller
{
    protected BillingEngine $billingEngine;

    public function __construct(BillingEngine $billingEngine)
    {
        $this->billingEngine = $billingEngine;
    }

    /**
     * Get all available regions.
     *
     * @return JsonResponse
     */
    public function getRegions(): JsonResponse
    {
        $regions = Regions::all()->map(function ($region) {
            return [
                'id' => $region->id,
                'name' => $region->name,
                'water_email' => $region->water_email,
                'electricity_email' => $region->electricity_email,
            ];
        });

        return response()->json([
            'success' => true,
            'data' => $regions,
        ]);
    }

    /**
     * Get all tariffs for a specific region.
     *
     * @param Regions $region
     * @return JsonResponse
     */
    public function getTariffsForRegion(Regions $region): JsonResponse
    {
        $tariffs = RegionsAccountTypeCost::where('region_id', $region->id)
            ->where('is_active', true)
            ->get()
            ->map(function ($tariff) {
                return [
                    'id' => $tariff->id,
                    'template_name' => $tariff->template_name,
                    'billing_type' => $tariff->billing_type ?? 'MONTHLY',
                    'billing_day' => $tariff->billing_day,
                    'read_day' => $tariff->read_day,
                    'is_water' => (bool) $tariff->is_water,
                    'is_electricity' => (bool) $tariff->is_electricity,
                    'start_date' => $tariff->start_date,
                    'end_date' => $tariff->end_date,
                ];
            });

        return response()->json([
            'success' => true,
            'data' => [
                'region' => [
                    'id' => $region->id,
                    'name' => $region->name,
                ],
                'tariffs' => $tariffs,
            ],
        ]);
    }

    /**
     * Get full tariff details including tiers, fixed costs, and customer-editable costs.
     *
     * @param RegionsAccountTypeCost $tariff
     * @return JsonResponse
     */
    public function getTariffDetails(RegionsAccountTypeCost $tariff): JsonResponse
    {
        // Calculate read_day from billing_day (default: billing_day - 5)
        $billingDay = (int) ($tariff->billing_day ?? 20);
        $calculatedReadDay = max(1, $billingDay - 5);

        return response()->json([
            'success' => true,
            'data' => [
                'id' => $tariff->id,
                'template_name' => $tariff->template_name,
                'billing_type' => $tariff->billing_type ?? 'MONTHLY',
                'region' => $tariff->region ? [
                    'id' => $tariff->region->id,
                    'name' => $tariff->region->name,
                ] : null,
                
                // Billing dates
                'billing_day' => $billingDay,
                'read_day' => $tariff->read_day ?? $calculatedReadDay,
                'calculated_read_day' => $calculatedReadDay,
                
                // Meter types enabled
                'is_water' => (bool) $tariff->is_water,
                'is_electricity' => (bool) $tariff->is_electricity,
                
                // VAT
                'vat_percentage' => (float) ($tariff->vat_percentage ?? 15),
                
                // Water tiers (from water_in JSON)
                'water_in_tiers' => $this->formatTiers($tariff->water_in ?? []),
                'water_in_additional' => $tariff->waterin_additional ?? [],
                
                // Water out tiers
                'water_out_tiers' => $this->formatTiers($tariff->water_out ?? []),
                'water_out_additional' => $tariff->waterout_additional ?? [],
                
                // Electricity tiers
                'electricity_tiers' => $this->formatTiers($tariff->electricity ?? []),
                'electricity_additional' => $tariff->electricity_additional ?? [],
                
                // Fixed costs (NOT editable by customer)
                'fixed_costs' => $tariff->fixed_costs ?? [],
                
                // Customer editable costs (CAN be edited by customer)
                'customer_costs' => $tariff->customer_costs ?? [],
                
                // Rates (editable)
                'vat_rate' => (float) ($tariff->vat_rate ?? 0),
                'rates_rebate' => (float) ($tariff->rates_rebate ?? 0),
                'ratable_value' => (float) ($tariff->ratable_value ?? 0),
                
                // Effective dates
                'start_date' => $tariff->start_date,
                'end_date' => $tariff->end_date,
            ],
        ]);
    }

    /**
     * Format tiers array for consistent output.
     */
    private function formatTiers(array $tiers): array
    {
        return array_map(function ($tier, $index) {
            return [
                'tier_number' => $index + 1,
                'min' => (float) ($tier['min'] ?? 0),
                'max' => (float) ($tier['max'] ?? 0),
                'cost' => (float) ($tier['cost'] ?? 0),
                'percentage' => isset($tier['percentage']) ? (float) $tier['percentage'] : null,
            ];
        }, $tiers, array_keys($tiers));
    }

    /**
     * Preview a bill calculation using the tariff template.
     * This allows users to see what their bill would look like before creating an account.
     *
     * @param Request $request
     * @param RegionsAccountTypeCost $tariff
     * @return JsonResponse
     */
    public function previewBill(Request $request, RegionsAccountTypeCost $tariff): JsonResponse
    {
        // Get usage values from request or use tariff defaults
        $waterUsed = (float) $request->input('water_used', $tariff->water_used ?? 1);
        $electricityUsed = (float) $request->input('electricity_used', $tariff->electricity_used ?? 1);
        
        // Get customer-editable costs from request or use tariff defaults
        $customerCosts = $request->input('customer_costs', $tariff->customer_costs ?? []);
        $ratesValue = (float) $request->input('vat_rate', $tariff->vat_rate ?? 0);
        $ratesRebate = (float) $request->input('rates_rebate', $tariff->rates_rebate ?? 0);

        // Calculate water charges
        $waterCharges = $this->calculateWaterCharges($tariff, $waterUsed);
        
        // Calculate electricity charges
        $electricityCharges = $this->calculateElectricityCharges($tariff, $electricityUsed);
        
        // Calculate fixed costs
        $fixedCostsTotal = $this->sumFixedCosts($tariff->fixed_costs ?? []);
        
        // Calculate customer costs
        $customerCostsTotal = $this->sumCustomerCosts($customerCosts);
        
        // Calculate subtotal (before VAT and rates)
        $subtotal = $waterCharges['total'] + $electricityCharges['total'] + $fixedCostsTotal + $customerCostsTotal;
        
        // Calculate VAT on vatable items
        $vatPercentage = (float) ($tariff->vat_percentage ?? 15);
        $vatableAmount = $waterCharges['total'] + $electricityCharges['total'] + $fixedCostsTotal + $customerCostsTotal;
        $vatAmount = round($vatableAmount * ($vatPercentage / 100), 2);
        
        // Calculate final total
        $total = $subtotal + $vatAmount + $ratesValue - $ratesRebate;

        return response()->json([
            'success' => true,
            'data' => [
                'tariff_name' => $tariff->template_name,
                'inputs' => [
                    'water_used' => $waterUsed,
                    'electricity_used' => $electricityUsed,
                ],
                'water' => $waterCharges,
                'electricity' => $electricityCharges,
                'fixed_costs' => [
                    'items' => $tariff->fixed_costs ?? [],
                    'total' => $fixedCostsTotal,
                ],
                'customer_costs' => [
                    'items' => $customerCosts,
                    'total' => $customerCostsTotal,
                ],
                'subtotal' => round($subtotal, 2),
                'vat' => [
                    'percentage' => $vatPercentage,
                    'amount' => $vatAmount,
                ],
                'rates' => [
                    'value' => $ratesValue,
                    'rebate' => $ratesRebate,
                    'net' => $ratesValue - $ratesRebate,
                ],
                'total' => round($total, 2),
            ],
        ]);
    }

    /**
     * Calculate water charges using tiered rates.
     * Water tiers are in Litres, cost is per KL.
     */
    private function calculateWaterCharges(RegionsAccountTypeCost $tariff, float $waterUsedKL): array
    {
        if (!$tariff->is_water) {
            return ['total' => 0, 'breakdown' => []];
        }

        $waterInTiers = $tariff->water_in ?? [];
        $waterInAdditional = $tariff->waterin_additional ?? [];
        $waterOutTiers = $tariff->water_out ?? [];
        $waterOutAdditional = $tariff->waterout_additional ?? [];
        
        // Convert KL to Litres for tier calculation
        $waterUsedLitres = $waterUsedKL * 1000;
        
        // Calculate Water In charges
        $waterInCharges = $this->calculateTieredCharges($waterInTiers, $waterUsedLitres, true);
        $waterInAdditionalCharges = $this->calculateAdditionalCharges($waterInAdditional, $waterUsedKL, $waterInCharges['total']);
        
        // Calculate Water Out charges
        $waterOutCharges = $this->calculateTieredCharges($waterOutTiers, $waterUsedLitres, true);
        $waterOutAdditionalCharges = $this->calculateAdditionalCharges($waterOutAdditional, $waterUsedKL, $waterOutCharges['total']);
        
        $total = $waterInCharges['total'] + $waterInAdditionalCharges['total'] + 
                 $waterOutCharges['total'] + $waterOutAdditionalCharges['total'];

        return [
            'total' => round($total, 2),
            'breakdown' => [
                'water_in' => $waterInCharges,
                'water_in_additional' => $waterInAdditionalCharges,
                'water_out' => $waterOutCharges,
                'water_out_additional' => $waterOutAdditionalCharges,
            ],
        ];
    }

    /**
     * Calculate electricity charges using tiered rates.
     */
    private function calculateElectricityCharges(RegionsAccountTypeCost $tariff, float $electricityUsedKWH): array
    {
        if (!$tariff->is_electricity) {
            return ['total' => 0, 'breakdown' => []];
        }

        $electricityTiers = $tariff->electricity ?? [];
        $electricityAdditional = $tariff->electricity_additional ?? [];
        
        // Calculate Electricity charges (no conversion needed, KWH to KWH)
        $electricityCharges = $this->calculateTieredCharges($electricityTiers, $electricityUsedKWH, false);
        $electricityAdditionalCharges = $this->calculateAdditionalCharges($electricityAdditional, $electricityUsedKWH, $electricityCharges['total']);
        
        $total = $electricityCharges['total'] + $electricityAdditionalCharges['total'];

        return [
            'total' => round($total, 2),
            'breakdown' => [
                'electricity' => $electricityCharges,
                'electricity_additional' => $electricityAdditionalCharges,
            ],
        ];
    }

    /**
     * Calculate charges using tiered rates.
     * 
     * @param array $tiers Array of tier definitions with min, max, cost, percentage
     * @param float $usage Usage in base units (Litres for water, KWH for electricity)
     * @param bool $convertToKL Whether to convert usage to KL for cost calculation
     * @return array
     */
    private function calculateTieredCharges(array $tiers, float $usage, bool $convertToKL = false): array
    {
        $totalCharge = 0;
        $breakdown = [];
        $consumedSoFar = 0;

        foreach ($tiers as $index => $tier) {
            $tierMin = (float) ($tier['min'] ?? 0);
            $tierMax = (float) ($tier['max'] ?? PHP_FLOAT_MAX);
            $costPerUnit = (float) ($tier['cost'] ?? 0);
            $percentage = isset($tier['percentage']) ? (float) $tier['percentage'] : 100;
            
            // Calculate tier capacity
            $tierCapacity = $tierMax - $tierMin;
            
            // Calculate remaining usage after previous tiers
            $remainingUsage = $usage - $consumedSoFar;
            
            if ($remainingUsage <= 0) {
                break;
            }
            
            // Units in this tier
            $unitsInTier = min($tierCapacity, $remainingUsage);
            
            // Apply percentage if specified (for water out)
            $effectiveUnits = $unitsInTier * ($percentage / 100);
            
            // Convert to KL if needed (for water - cost is per KL)
            $unitsForCost = $convertToKL ? ($effectiveUnits / 1000) : $effectiveUnits;
            
            // Calculate charge
            $tierCharge = $unitsForCost * $costPerUnit;
            
            $totalCharge += $tierCharge;
            $breakdown[] = [
                'tier' => $index + 1,
                'min' => $tierMin,
                'max' => $tierMax,
                'units_in_tier' => round($unitsInTier, 2),
                'effective_units' => round($effectiveUnits, 2),
                'units_for_cost' => round($unitsForCost, 4),
                'cost_per_unit' => $costPerUnit,
                'percentage' => $percentage,
                'charge' => round($tierCharge, 2),
            ];
            
            $consumedSoFar += $unitsInTier;
        }

        return [
            'total' => round($totalCharge, 2),
            'breakdown' => $breakdown,
        ];
    }

    /**
     * Calculate additional/surcharge costs.
     */
    private function calculateAdditionalCharges(array $additionalCosts, float $usage, float $baseCharge): array
    {
        $totalCharge = 0;
        $breakdown = [];

        foreach ($additionalCosts as $cost) {
            $title = $cost['title'] ?? 'Additional Cost';
            $percentage = isset($cost['percentage']) ? (float) $cost['percentage'] : 100;
            $costValue = (float) ($cost['cost'] ?? 0);
            
            // Calculate effective usage based on percentage
            $effectiveUsage = $usage * ($percentage / 100);
            
            // Calculate charge
            $charge = $effectiveUsage * $costValue;
            
            $totalCharge += $charge;
            $breakdown[] = [
                'title' => $title,
                'percentage' => $percentage,
                'cost_per_unit' => $costValue,
                'charge' => round($charge, 2),
            ];
        }

        return [
            'total' => round($totalCharge, 2),
            'breakdown' => $breakdown,
        ];
    }

    /**
     * Sum fixed costs.
     */
    private function sumFixedCosts(array $fixedCosts): float
    {
        $total = 0;
        foreach ($fixedCosts as $cost) {
            $total += (float) ($cost['value'] ?? 0);
        }
        return round($total, 2);
    }

    /**
     * Sum customer-editable costs.
     */
    private function sumCustomerCosts(array $customerCosts): float
    {
        $total = 0;
        foreach ($customerCosts as $cost) {
            $total += (float) ($cost['value'] ?? 0);
        }
        return round($total, 2);
    }

    /**
     * Get the current user's account with tariff details.
     * User → Site → Account → TariffTemplate
     *
     * @param Request $request
     * @return JsonResponse
     */
    /**
     * Get account details by ID for editing
     */
    public function getAccountDetails(Request $request, $accountId): JsonResponse
    {
        $user = $request->user();
        
        $account = \App\Models\Account::with(['site', 'tariffTemplate', 'meters'])
            ->where('id', $accountId)
            ->first();
        
        if (!$account) {
            return response()->json([
                'success' => false,
                'message' => 'Account not found.',
            ], 404);
        }
        
        if ($account->site && $account->site->user_id !== $user->id) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized.',
            ], 403);
        }
        
        $tariff = $account->tariffTemplate;
        
        return response()->json([
            'success' => true,
            'data' => [
                'id' => $account->id,
                'account_name' => $account->account_name,
                'account_number' => $account->account_number,
                'name_as_per_bill' => $account->name_as_per_bill,
                'billing_day' => $account->billing_day ?? $tariff?->billing_day,
                'read_day' => $account->read_day ?? $tariff?->read_day,
                'water_email' => $account->water_email,
                'electricity_email' => $account->electricity_email,
                'tariff_template_id' => $account->tariff_template_id,
                'region_id' => $tariff?->region_id,
                'customer_costs' => $tariff?->customer_costs ?? [],
                'site' => $account->site ? [
                    'id' => $account->site->id,
                    'title' => $account->site->title,
                    'address' => $account->site->address,
                ] : null,
                'tariff' => $tariff ? [
                    'id' => $tariff->id,
                    'template_name' => $tariff->template_name,
                    'billing_type' => $tariff->billing_type,
                    'billing_day' => $tariff->billing_day,
                    'read_day' => $tariff->read_day,
                    'region_id' => $tariff->region_id,
                ] : null,
            ],
        ]);
    }

        public function getCurrentAccount(Request $request): JsonResponse
    {
        $user = $request->user();
        
        // Get the user's first site
        $site = \App\Models\Site::where('user_id', $user->id)->first();
        
        if (!$site) {
            return response()->json([
                'success' => false,
                'message' => 'No site found for this user.',
                'data' => null,
            ]);
        }
        
        // Get the first account for this site
        $account = $site->accounts()->first();
        
        if (!$account) {
            return response()->json([
                'success' => false,
                'message' => 'No account found for this user.',
                'data' => null,
            ]);
        }

        $tariff = $account->tariffTemplate;

        return response()->json([
            'success' => true,
            'data' => [
                'site' => [
                    'id' => $site->id,
                    'title' => $site->title,
                    'address' => $site->address,
                ],
                'account' => [
                    'id' => $account->id,
                    'account_name' => $account->account_name,
                    'account_number' => $account->account_number,
                    'billing_date' => $account->billing_date,
                ],
                'tariff' => $tariff ? [
                    'id' => $tariff->id,
                    'template_name' => $tariff->template_name,
                    'region' => $tariff->region ? $tariff->region->name : null,
                ] : null,
                'customer_costs' => $tariff->customer_costs ?? [],
                'rates' => [
                    'value' => $tariff->vat_rate ?? 0,
                    'rebate' => $tariff->rates_rebate ?? 0,
                ],
            ],
        ]);
    }

    /**
     * Create a new account for the user.
     * Creates User (if registering), Site, and Account linked to TariffTemplate.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function createAccount(Request $request): JsonResponse
    {
        $request->validate([
            'tariff_template_id' => 'required|exists:regions_account_type_cost,id',
            'account_name' => 'required|string|max:255',
            'billing_day' => 'required|integer|min:1|max:31',
            'site_title' => 'nullable|string|max:255',
            'site_address' => 'nullable|string|max:500',
            'address_lat' => 'nullable|numeric',
            'address_lng' => 'nullable|numeric',
            'water_email' => 'nullable|email|max:255',
            'electricity_email' => 'nullable|email|max:255',
            'customer_costs' => 'nullable|array',
            // User registration fields (optional - for new user registration)
            'user.name' => 'nullable|string|max:255',
            'user.email' => 'nullable|email|max:255|unique:users,email',
            'user.phone' => 'nullable|string|max:50',
            'user.company' => 'nullable|string|max:255',
            'user.timezone' => 'nullable|string|max:100',
            'user.password' => 'nullable|string|min:6',
        ]);

        $tariff = RegionsAccountTypeCost::findOrFail($request->tariff_template_id);
        
        // Calculate read_day from billing_day (billing_day - 5)
        $billingDay = (int) $request->billing_day;
        $readDay = max(1, $billingDay - 5);

        // Get or create user
        $user = $request->user();
        
        // If no authenticated user and user data provided, create new user
        if (!$user && $request->has('user')) {
            $userData = $request->input('user');
            $user = \App\Models\User::create([
                'name' => $userData['name'],
                'email' => $userData['email'],
                'phone' => $userData['phone'] ?? null,
                'company' => $userData['company'] ?? null,
                'timezone' => $userData['timezone'] ?? 'Africa/Johannesburg',
                'password' => bcrypt($userData['password']),
            ]);
        }
        
        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'User authentication required or user data must be provided.',
            ], 401);
        }

        // Get or create site for user
        $site = \App\Models\Site::where('user_id', $user->id)->first();
        
        if (!$site) {
            $site = \App\Models\Site::create([
                'user_id' => $user->id,
                'title' => $request->site_title ?? $request->account_name ?? 'My Home',
                'address' => $request->site_address ?? '',
                'lat' => $request->address_lat,
                'lng' => $request->address_lng,
                'region_id' => $tariff->region_id,
            ]);
        } else {
            // Update existing site with new address if provided
            if ($request->site_address) {
                $site->update([
                    'address' => $request->site_address,
                    'lat' => $request->address_lat,
                    'lng' => $request->address_lng,
                ]);
            }
        }

        // Create account linked to site and tariff
        $account = Account::create([
            'site_id' => $site->id,
            'tariff_template_id' => $tariff->id,
            'account_name' => $request->account_name,
            'billing_date' => $billingDay,
            'bill_day' => $billingDay,
            'read_day' => $readDay,
            'water_email' => $request->water_email ?? $tariff->water_email,
            'electricity_email' => $request->electricity_email ?? $tariff->electricity_email,
            'customer_costs' => $request->customer_costs ? json_encode($request->customer_costs) : null,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Account created successfully.',
            'data' => [
                'user_id' => $user->id,
                'site_id' => $site->id,
                'account_id' => $account->id,
                'account_name' => $account->account_name,
                'tariff_name' => $tariff->template_name,
                'billing_day' => $billingDay,
                'read_day' => $readDay,
                'address' => $site->address,
            ],
        ]);
    }

    /**
     * Update the user's account settings.
     * Note: Customer-editable costs are stored on the TariffTemplate for now.
     * In future, these could be stored on a per-account basis.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function updateAccount(Request $request): JsonResponse
    {
        $request->validate([
            'account_id' => 'required|exists:accounts,id',
        ]);

        $user = $request->user();
        
        // Get account through site relationship
        $site = \App\Models\Site::where('user_id', $user->id)->first();
        
        if (!$site) {
            return response()->json([
                'success' => false,
                'message' => 'No site found for this user.',
            ], 404);
        }
        
        $account = $site->accounts()->where('id', $request->account_id)->firstOrFail();

        // Update account fields if provided
        if ($request->has('account_name')) {
            $account->account_name = $request->account_name;
        }
        if ($request->has('billing_day')) {
            $billingDay = (int) $request->billing_day;
            $account->billing_date = $billingDay;
            $account->bill_day = $billingDay;
            $account->read_day = max(1, $billingDay - 5);
        }

        $account->save();

        return response()->json([
            'success' => true,
            'message' => 'Account updated successfully.',
            'data' => [
                'account_id' => $account->id,
                'account_name' => $account->account_name,
                'billing_day' => $account->billing_date,
                'read_day' => $account->read_day,
            ],
        ]);
    }

    /**
     * Get the current bill for the user's account.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function getCurrentBill(Request $request): JsonResponse
    {
        $user = $request->user();
        
        // Get account through site relationship
        $site = \App\Models\Site::where('user_id', $user->id)->first();
        
        if (!$site) {
            return response()->json([
                'success' => false,
                'message' => 'No site found for this user.',
            ], 404);
        }
        
        $account = $site->accounts()->first();
        
        if (!$account) {
            return response()->json([
                'success' => false,
                'message' => 'No account found for this user.',
            ], 404);
        }

        $tariff = $account->tariffTemplate;
        
        if (!$tariff) {
            return response()->json([
                'success' => false,
                'message' => 'No tariff assigned to this account.',
            ], 404);
        }

        // Use tariff values for calculation
        $waterUsed = (float) ($tariff->water_used ?? 1);
        $electricityUsed = (float) ($tariff->electricity_used ?? 1);
        $customerCosts = $tariff->customer_costs ?? [];
        $ratesValue = (float) ($tariff->vat_rate ?? 0);
        $ratesRebate = (float) ($tariff->rates_rebate ?? 0);

        // Calculate the bill using the same logic as previewBill
        $waterCharges = $this->calculateWaterCharges($tariff, $waterUsed);
        $electricityCharges = $this->calculateElectricityCharges($tariff, $electricityUsed);
        $fixedCostsTotal = $this->sumFixedCosts($tariff->fixed_costs ?? []);
        $customerCostsTotal = $this->sumCustomerCosts($customerCosts);
        
        $subtotal = $waterCharges['total'] + $electricityCharges['total'] + $fixedCostsTotal + $customerCostsTotal;
        
        $vatPercentage = (float) ($tariff->vat_percentage ?? 15);
        $vatAmount = round($subtotal * ($vatPercentage / 100), 2);
        
        $total = $subtotal + $vatAmount + $ratesValue - $ratesRebate;

        return response()->json([
            'success' => true,
            'data' => [
                'account' => [
                    'id' => $account->id,
                    'name' => $account->account_name,
                ],
                'tariff' => [
                    'id' => $tariff->id,
                    'name' => $tariff->template_name,
                    'region' => $tariff->region ? $tariff->region->name : null,
                ],
                'billing' => [
                    'billing_day' => $account->billing_date ?? $tariff->billing_day,
                    'read_day' => $account->read_day ?? $tariff->read_day,
                ],
                'inputs' => [
                    'water_used' => $waterUsed,
                    'electricity_used' => $electricityUsed,
                ],
                'water' => $waterCharges,
                'electricity' => $electricityCharges,
                'fixed_costs' => [
                    'items' => $tariff->fixed_costs ?? [],
                    'total' => $fixedCostsTotal,
                ],
                'customer_costs' => [
                    'items' => $customerCosts,
                    'total' => $customerCostsTotal,
                ],
                'subtotal' => round($subtotal, 2),
                'vat' => [
                    'percentage' => $vatPercentage,
                    'amount' => $vatAmount,
                ],
                'rates' => [
                    'value' => $ratesValue,
                    'rebate' => $ratesRebate,
                    'net' => $ratesValue - $ratesRebate,
                ],
                'total' => round($total, 2),
            ],
        ]);
    }
}

