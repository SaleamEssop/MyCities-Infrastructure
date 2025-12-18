<?php

namespace App\Observers;

use App\Models\MeterReadings;
use App\Services\BillingEngine;
use Illuminate\Support\Facades\Log;

class MeterReadingObserver
{
    protected BillingEngine $billingEngine;

    public function __construct(BillingEngine $billingEngine)
    {
        $this->billingEngine = $billingEngine;
    }

    /**
     * Handle the MeterReadings "created" event.
     * When a new actual reading is submitted, check for reconciliation opportunities.
     */
    public function created(MeterReadings $reading): void
    {
        // Only trigger reconciliation for ACTUAL readings
        if (!$reading->isActual()) {
            return;
        }

        $meter = $reading->meter;
        if (!$meter) {
            return;
        }

        try {
            $reconciliations = $this->billingEngine->checkAndReconcile($meter, $reading);
            
            if (count($reconciliations) > 0) {
                Log::info('Billing reconciliation triggered', [
                    'meter_id' => $meter->id,
                    'reading_id' => $reading->id,
                    'reconciliations_count' => count($reconciliations),
                ]);
            }
        } catch (\Exception $e) {
            Log::error('Billing reconciliation failed', [
                'meter_id' => $meter->id,
                'reading_id' => $reading->id,
                'error' => $e->getMessage(),
            ]);
        }
    }
}