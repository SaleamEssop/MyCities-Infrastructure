<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * AdminAction Model
 * 
 * Records all admin actions for audit trail.
 */
class AdminAction extends Model
{
    public $timestamps = false;
    
    protected $table = 'admin_actions';

    protected $fillable = [
        'admin_id',
        'action_type',
        'reading_id',
        'bill_id',
        'meter_id',
        'account_id',
        'payload',
        'reason',
        'undone_by_action_id',
        'is_undone',
        'created_at',
    ];

    protected $casts = [
        'payload' => 'array',
        'is_undone' => 'boolean',
        'created_at' => 'datetime',
    ];

    /**
     * Action types
     */
    const ACTION_EDIT_READING = 'edit_reading';
    const ACTION_DELETE_READING = 'delete_reading';
    const ACTION_ADD_READING = 'add_reading';
    const ACTION_SET_FLAGS = 'set_flags';
    const ACTION_RECOMPUTE_BILL = 'recompute_bill';
    const ACTION_UNDO = 'undo';

    /**
     * Get the admin user who performed this action
     */
    public function admin()
    {
        return $this->belongsTo(User::class, 'admin_id');
    }

    /**
     * Get the reading associated with this action
     */
    public function reading()
    {
        return $this->belongsTo(MeterReadings::class, 'reading_id');
    }

    /**
     * Get the meter associated with this action
     */
    public function meter()
    {
        return $this->belongsTo(Meter::class, 'meter_id');
    }

    /**
     * Get the account associated with this action
     */
    public function account()
    {
        return $this->belongsTo(Account::class, 'account_id');
    }

    /**
     * Get the undo action if this action was undone
     */
    public function undoAction()
    {
        return $this->belongsTo(AdminAction::class, 'undone_by_action_id');
    }

    /**
     * Scope for filtering by action type
     */
    public function scopeOfType($query, $type)
    {
        return $query->where('action_type', $type);
    }

    /**
     * Scope for filtering by admin
     */
    public function scopeByAdmin($query, $adminId)
    {
        return $query->where('admin_id', $adminId);
    }

    /**
     * Scope for non-undone actions
     */
    public function scopeActive($query)
    {
        return $query->where('is_undone', false);
    }
}




