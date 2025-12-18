<?php

namespace App\Http\Controllers;

use App\Models\Account;
use App\Models\Bill;
use App\Services\BillingEngine;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Carbon\Carbon;

class BillController extends Controller
{
    protected BillingEngine $billingEngine;

    public function __construct(BillingEngine $billingEngine)
    {
        $this->billingEngine = $billingEngine;
    }

    /**
     * List bills or get estimate for an account.
     * GET/POST /api/v1/bills
     * 
     * Modes: 'estimate' | 'preview' | 'finalize'
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function index(Request $request): JsonResponse
    {
        $accountId = $request->input('account_id');
        $mode = $request->input('mode', 'estimate');
        $periodStart = $request->input('period_start');
        $periodEnd = $request->input('period_end');

        if (!$accountId) {
            return response()->json([
                'success' => false,
                'message' => 'account_id is required',
            ], 400);
        }

        $account = Account::with(['meters', 'tariffTemplate'])->find($accountId);

        if (!$account) {
            return response()->json([
                'success' => false,
                'message' => 'Account not found',
            ], 404);
        }

        // Parse dates if provided
        $startDate = $periodStart ? Carbon::parse($periodStart) : null;
        $endDate = $periodEnd ? Carbon::parse($periodEnd) : null;

        // Get projected/estimated bill
        $projection = $this->billingEngine->getProjectedMonthlyBill($account);

        // Build response in the expected format
        $billingDate = $this->billingEngine->getBillingDate($account);
        $readDate = $this->billingEngine->getReadDate($account);

        $status = match($mode) {
            'estimate' => 'ESTIMATE',
            'preview' => 'PREVIEW',
            'finalize' => 'FINALIZED',
            default => 'ESTIMATE',
        };

        // Map meter breakdown to per_meter format
        $perMeter = [];
        foreach ($projection['breakdown'] as $meterData) {
            $perMeter[] = [
                'meter_id' => $meterData['meter_id'],
                'title' => $meterData['meter_title'],
                'value' => $meterData['projected_amount'],
                'reading' => $meterData['projected_consumption'],
                'daily_consumption' => $meterData['daily_consumption'],
                'line_type' => 'meter_usage',
                'commodity' => 'water', // Could be dynamic based on meter type
                'is_vatable' => true,
            ];
        }

        $response = [
            'status' => $status,
            'billing_period_start' => $startDate?->toIso8601String() ?? $readDate->subMonth()->toIso8601String(),
            'billing_period_end' => $endDate?->toIso8601String() ?? $readDate->toIso8601String(),
            'totals' => [
                'subtotal' => $projection['projected_amount'] / 1.15, // Extract pre-VAT
                'vat' => $projection['projected_amount'] - ($projection['projected_amount'] / 1.15),
                'total' => $projection['projected_amount'],
            ],
            'per_meter' => $perMeter,
            'tariff_snapshot' => [
                'name' => $projection['tariff_name'] ?? 'Default Tariff',
                'billing_type' => $projection['billing_type'] ?? 'MONTHLY',
            ],
            'fingerprint' => md5($accountId . ($startDate?->timestamp ?? '') . ($endDate?->timestamp ?? '') . time()),
            'reason_codes' => [],
            'quality_score' => 100,
            'persisted' => $mode === 'finalize',
            'read_date' => $readDate->toDateString(),
            'billing_date' => $billingDate->toDateString(),
        ];

        return response()->json($response);
    }

    /**
     * Recompute a bill (admin only).
     * POST /api/v1/bills/{id}/recompute
     *
     * @param int $id
     * @return JsonResponse
     */
    public function recompute(int $id): JsonResponse
    {
        $bill = Bill::with(['account', 'meter', 'openingReading', 'closingReading'])->find($id);

        if (!$bill) {
            return response()->json([
                'success' => false,
                'message' => 'Bill not found',
            ], 404);
        }

        if (!$bill->openingReading || !$bill->closingReading) {
            return response()->json([
                'success' => false,
                'message' => 'Cannot recompute: missing readings',
            ], 400);
        }

        // Recalculate the bill
        $result = $this->billingEngine->calculateCharge(
            $bill->account,
            $bill->openingReading,
            $bill->closingReading
        );

        // Update the bill
        $bill->update([
            'consumption' => $result->consumption,
            'tiered_charge' => $result->tieredCharge,
            'fixed_costs_total' => $result->fixedCostsTotal,
            'vat_amount' => $result->vatAmount,
            'total_amount' => $result->totalAmount,
            'is_provisional' => $result->isProvisional,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Bill recomputed successfully',
            'data' => $result->toArray(),
        ]);
    }
}
