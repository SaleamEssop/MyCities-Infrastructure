<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * 
     * Table to log all admin actions for audit trail
     */
    public function up(): void
    {
        Schema::create('admin_actions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('admin_id')->constrained('users')->onDelete('cascade');
            $table->string('action_type', 50); // edit_reading, delete_reading, add_reading, recompute_bill, set_flags, undo
            $table->foreignId('reading_id')->nullable()->constrained('meter_readings')->onDelete('set null');
            $table->foreignId('bill_id')->nullable()->constrained('bills')->onDelete('set null');
            $table->foreignId('meter_id')->nullable()->constrained('meters')->onDelete('set null');
            $table->foreignId('account_id')->nullable()->constrained('accounts')->onDelete('set null');
            $table->json('payload')->nullable(); // Store old/new values, flags, etc.
            $table->text('reason'); // Required reason for audit
            $table->foreignId('undone_by_action_id')->nullable(); // If this action was undone, reference to undo action
            $table->boolean('is_undone')->default(false);
            $table->timestamp('created_at')->useCurrent();
            
            // Indexes for common queries
            $table->index('action_type');
            $table->index('admin_id');
            $table->index(['reading_id', 'created_at']);
            $table->index(['account_id', 'created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('admin_actions');
    }
};




