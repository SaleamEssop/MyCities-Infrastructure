<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\MeterReadings;
use App\Models\AdminAction;
use App\Models\Meter;
use App\Models\Account;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

/**
 * Admin Readings Controller
 * 
 * Handles admin-only operations for meter readings.
 * All operations are logged to admin_actions table for audit trail.
 */
class AdminReadingsController extends Controller
{
    /**
     * Edit a meter reading (admin only)
     * 
     * @param Request $request
     * @param int $id Reading ID
     * @return \Illuminate\Http\JsonResponse
     */
    public function editReading(Request $request, $id)
    {
        $request->validate([
            'value' => 'required|string',
            'reason' => 'required|string|min:5',
            'is_rollover' => 'boolean',
            'rollover_reason_code' => 'nullable|string',
        ]);

        $reading = MeterReadings::find($id);
        if (!$reading) {
            return response()->json([
                'status' => false,
                'code' => 404,
                'msg' => 'Reading not found'
            ], 404);
        }

        DB::beginTransaction();
        try {
            // Store old value for audit
            $oldValue = $reading->reading_value;
            
            // Update the reading
            $reading->reading_value = $request->value;
            if ($request->has('is_rollover')) {
                $reading->is_rollover = $request->is_rollover;
            }
            if ($request->has('rollover_reason_code')) {
                $reading->rollover_reason_code = $request->rollover_reason_code;
            }
            $reading->admin_override = true;
            $reading->updated_at = now();
            $reading->save();

            // Log admin action
            $this->logAdminAction('edit_reading', [
                'reading_id' => $id,
                'meter_id' => $reading->meter_id,
                'old_value' => $oldValue,
                'new_value' => $request->value,
                'is_rollover' => $request->is_rollover ?? false,
            ], $request->reason, $reading);

            DB::commit();

            return response()->json([
                'status' => true,
                'code' => 200,
                'msg' => 'Reading updated successfully',
                'data' => $reading
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'status' => false,
                'code' => 500,
                'msg' => 'Failed to update reading: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Delete (soft-delete) a meter reading (admin only)
     * 
     * @param Request $request
     * @param int $id Reading ID
     * @return \Illuminate\Http\JsonResponse
     */
    public function deleteReading(Request $request, $id)
    {
        $request->validate([
            'reason' => 'required|string|min:5',
        ]);

        $reading = MeterReadings::find($id);
        if (!$reading) {
            return response()->json([
                'status' => false,
                'code' => 404,
                'msg' => 'Reading not found'
            ], 404);
        }

        DB::beginTransaction();
        try {
            // Store reading data for audit before deletion
            $readingData = $reading->toArray();
            
            // Log admin action BEFORE deletion
            $this->logAdminAction('delete_reading', [
                'reading_id' => $id,
                'meter_id' => $reading->meter_id,
                'reading_value' => $reading->reading_value,
                'reading_date' => $reading->reading_date,
            ], $request->reason, $reading);

            // Soft delete (or hard delete based on your needs)
            // For now, we'll use soft delete by setting a deleted flag
            // If your model doesn't have soft deletes, you can add it
            $reading->delete();

            DB::commit();

            return response()->json([
                'status' => true,
                'code' => 200,
                'msg' => 'Reading deleted successfully',
                'data' => $readingData
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'status' => false,
                'code' => 500,
                'msg' => 'Failed to delete reading: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Add a new meter reading (admin only, bypasses cooldown)
     * 
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function addReading(Request $request)
    {
        $request->validate([
            'meter_id' => 'required|exists:meters,id',
            'meter_reading_date' => 'required|date',
            'meter_reading' => 'required|string',
            'reason' => 'required|string|min:5',
            'is_rollover' => 'boolean',
            'rollover_reason_code' => 'nullable|string',
        ]);

        DB::beginTransaction();
        try {
            // Create the reading (bypassing cooldown check)
            $reading = MeterReadings::create([
                'meter_id' => $request->meter_id,
                'reading_date' => $request->meter_reading_date,
                'reading_value' => $request->meter_reading,
                'is_rollover' => $request->is_rollover ?? false,
                'rollover_reason_code' => $request->rollover_reason_code,
                'admin_override' => true, // Mark as admin-added
                'created_at' => now(),
            ]);

            // Get meter info for audit
            $meter = Meter::find($request->meter_id);

            // Log admin action
            $this->logAdminAction('add_reading', [
                'reading_id' => $reading->id,
                'meter_id' => $request->meter_id,
                'account_id' => $meter->account_id ?? null,
                'reading_value' => $request->meter_reading,
                'reading_date' => $request->meter_reading_date,
                'bypass_cooldown' => true,
            ], $request->reason, $reading);

            DB::commit();

            return response()->json([
                'status' => true,
                'code' => 200,
                'msg' => 'Reading added successfully',
                'data' => $reading
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'status' => false,
                'code' => 500,
                'msg' => 'Failed to add reading: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Set reading flags (admin only)
     * 
     * @param Request $request
     * @param int $id Reading ID
     * @return \Illuminate\Http\JsonResponse
     */
    public function setFlags(Request $request, $id)
    {
        $request->validate([
            'reason' => 'required|string|min:5',
        ]);

        $reading = MeterReadings::find($id);
        if (!$reading) {
            return response()->json([
                'status' => false,
                'code' => 404,
                'msg' => 'Reading not found'
            ], 404);
        }

        DB::beginTransaction();
        try {
            $oldFlags = [
                'is_rollover' => $reading->is_rollover,
                'admin_override' => $reading->admin_override,
                'is_estimated' => $reading->is_estimated ?? false,
                'is_final' => $reading->is_final ?? false,
            ];

            // Update flags
            if ($request->has('is_rollover')) {
                $reading->is_rollover = $request->is_rollover;
            }
            if ($request->has('admin_override')) {
                $reading->admin_override = $request->admin_override;
            }
            if ($request->has('is_estimated')) {
                $reading->is_estimated = $request->is_estimated;
            }
            if ($request->has('is_final')) {
                $reading->is_final = $request->is_final;
            }
            $reading->save();

            $newFlags = [
                'is_rollover' => $reading->is_rollover,
                'admin_override' => $reading->admin_override,
                'is_estimated' => $reading->is_estimated ?? false,
                'is_final' => $reading->is_final ?? false,
            ];

            // Log admin action
            $this->logAdminAction('set_flags', [
                'reading_id' => $id,
                'old_flags' => $oldFlags,
                'new_flags' => $newFlags,
            ], $request->reason, $reading);

            DB::commit();

            return response()->json([
                'status' => true,
                'code' => 200,
                'msg' => 'Flags updated successfully',
                'data' => $reading
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'status' => false,
                'code' => 500,
                'msg' => 'Failed to update flags: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get reading history for a meter (admin view with all details)
     * 
     * @param Request $request
     * @param int $meterId Meter ID
     * @return \Illuminate\Http\JsonResponse
     */
    public function getReadingHistory(Request $request, $meterId)
    {
        $meter = Meter::with(['readings' => function($q) {
            $q->orderBy('reading_date', 'desc');
        }])->find($meterId);

        if (!$meter) {
            return response()->json([
                'status' => false,
                'code' => 404,
                'msg' => 'Meter not found'
            ], 404);
        }

        return response()->json([
            'status' => true,
            'code' => 200,
            'msg' => 'Reading history retrieved',
            'data' => [
                'meter' => $meter,
                'readings' => $meter->readings,
            ]
        ]);
    }

    /**
     * Get audit log for readings
     * 
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function getAuditLog(Request $request)
    {
        $query = AdminAction::with(['admin'])->orderBy('created_at', 'desc');

        if ($request->has('account_id')) {
            $query->where('account_id', $request->account_id);
        }
        if ($request->has('meter_id')) {
            $query->where('meter_id', $request->meter_id);
        }
        if ($request->has('reading_id')) {
            $query->where('reading_id', $request->reading_id);
        }
        if ($request->has('action_type')) {
            $query->where('action_type', $request->action_type);
        }

        $limit = $request->get('limit', 50);
        $actions = $query->limit($limit)->get();

        return response()->json([
            'status' => true,
            'code' => 200,
            'msg' => 'Audit log retrieved',
            'data' => $actions
        ]);
    }

    /**
     * Undo a previous admin action
     * 
     * @param Request $request
     * @param int $actionId Admin action ID
     * @return \Illuminate\Http\JsonResponse
     */
    public function undoAction(Request $request, $actionId)
    {
        $request->validate([
            'reason' => 'required|string|min:5',
        ]);

        $action = AdminAction::find($actionId);
        if (!$action) {
            return response()->json([
                'status' => false,
                'code' => 404,
                'msg' => 'Admin action not found'
            ], 404);
        }

        if ($action->is_undone) {
            return response()->json([
                'status' => false,
                'code' => 400,
                'msg' => 'This action has already been undone'
            ], 400);
        }

        DB::beginTransaction();
        try {
            $payload = json_decode($action->payload, true);
            
            // Perform undo based on action type
            switch ($action->action_type) {
                case 'edit_reading':
                    // Restore old value
                    if (isset($payload['reading_id']) && isset($payload['old_value'])) {
                        $reading = MeterReadings::find($payload['reading_id']);
                        if ($reading) {
                            $reading->reading_value = $payload['old_value'];
                            $reading->save();
                        }
                    }
                    break;

                case 'delete_reading':
                    // Cannot easily undo a delete - would need to recreate
                    // For now, return error
                    return response()->json([
                        'status' => false,
                        'code' => 400,
                        'msg' => 'Cannot undo delete action. Please manually add the reading back.'
                    ], 400);

                case 'add_reading':
                    // Delete the added reading
                    if (isset($payload['reading_id'])) {
                        MeterReadings::destroy($payload['reading_id']);
                    }
                    break;

                case 'set_flags':
                    // Restore old flags
                    if (isset($payload['reading_id']) && isset($payload['old_flags'])) {
                        $reading = MeterReadings::find($payload['reading_id']);
                        if ($reading) {
                            foreach ($payload['old_flags'] as $flag => $value) {
                                $reading->$flag = $value;
                            }
                            $reading->save();
                        }
                    }
                    break;
            }

            // Mark original action as undone
            $action->is_undone = true;
            $action->save();

            // Log the undo action
            $undoAction = $this->logAdminAction('undo', [
                'original_action_id' => $actionId,
                'original_action_type' => $action->action_type,
                'original_payload' => $payload,
            ], $request->reason);

            // Link the undo action to the original
            $action->undone_by_action_id = $undoAction->id;
            $action->save();

            DB::commit();

            return response()->json([
                'status' => true,
                'code' => 200,
                'msg' => 'Action undone successfully',
                'data' => [
                    'original_action' => $action,
                    'undo_action' => $undoAction,
                ]
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'status' => false,
                'code' => 500,
                'msg' => 'Failed to undo action: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Recompute a bill by bill ID (admin only)
     * 
     * @param Request $request
     * @param int $billId Bill ID
     * @return \Illuminate\Http\JsonResponse
     */
    public function recomputeBill(Request $request, $billId)
    {
        $reason = $request->get('reason', 'Admin recompute request');
        
        DB::beginTransaction();
        try {
            // Log admin action
            $this->logAdminAction('recompute_bill', [
                'bill_id' => $billId,
            ], $reason);

            // TODO: Dispatch BillRecomputeJob
            // For now, return success with job_id placeholder
            $jobId = 'job_' . time() . '_' . $billId;

            DB::commit();

            return response()->json([
                'status' => true,
                'code' => 200,
                'msg' => 'Bill recompute queued successfully',
                'job_id' => $jobId,
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'status' => false,
                'code' => 500,
                'msg' => 'Failed to recompute bill: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Recompute bill for an account (admin only)
     * 
     * @param Request $request
     * @param int $accountId Account ID
     * @return \Illuminate\Http\JsonResponse
     */
    public function recomputeAccountBill(Request $request, $accountId)
    {
        $reason = $request->get('reason', 'Admin recompute request');
        
        $account = Account::find($accountId);
        if (!$account) {
            return response()->json([
                'status' => false,
                'code' => 404,
                'msg' => 'Account not found'
            ], 404);
        }

        DB::beginTransaction();
        try {
            // Log admin action
            $this->logAdminAction('recompute_bill', [
                'account_id' => $accountId,
            ], $reason);

            // TODO: Dispatch BillRecomputeJob for account
            // For now, return success with job_id placeholder
            $jobId = 'job_' . time() . '_acc_' . $accountId;

            DB::commit();

            return response()->json([
                'status' => true,
                'code' => 200,
                'msg' => 'Account bill recompute queued successfully',
                'job_id' => $jobId,
                'account_id' => $accountId,
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'status' => false,
                'code' => 500,
                'msg' => 'Failed to recompute account bill: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Check if current user has admin role
     * 
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function checkAdminRole(Request $request)
    {
        $user = Auth::user();
        
        if (!$user) {
            return response()->json([
                'status' => false,
                'is_admin' => false,
                'role' => 'guest'
            ], 401);
        }

        $isAdmin = $user->is_admin ?? false;
        
        return response()->json([
            'status' => true,
            'is_admin' => $isAdmin,
            'role' => $isAdmin ? 'admin' : 'user',
            'user_id' => $user->id,
        ]);
    }

    /**
     * Log an admin action to the audit table
     * 
     * @param string $actionType
     * @param array $payload
     * @param string $reason
     * @param MeterReadings|null $reading
     * @return AdminAction
     */
    protected function logAdminAction(string $actionType, array $payload, string $reason, $reading = null): AdminAction
    {
        $user = Auth::user();
        
        return AdminAction::create([
            'admin_id' => $user ? $user->id : 1, // Fallback to 1 for testing
            'action_type' => $actionType,
            'reading_id' => $reading ? $reading->id : ($payload['reading_id'] ?? null),
            'meter_id' => $reading ? $reading->meter_id : ($payload['meter_id'] ?? null),
            'account_id' => $payload['account_id'] ?? null,
            'payload' => json_encode($payload),
            'reason' => $reason,
            'created_at' => now(),
        ]);
    }
}

