<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('billing_reconciliations', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('meter_id');
            $table->unsignedBigInteger('account_id');
            $table->date('billing_date');              // The billing date that was estimated
            $table->decimal('original_estimate', 15, 2);   // What we originally billed
            $table->decimal('calculated_actual', 15, 2);   // What it should have been
            $table->decimal('adjustment_units', 15, 2);    // Difference (+/-)
            $table->enum('adjustment_type', ['OWING', 'CREDIT']);
            $table->unsignedBigInteger('triggered_by_reading_id');  // The reading that triggered reconciliation
            $table->date('triggered_date');            // When reconciliation occurred
            $table->enum('status', ['PENDING', 'APPLIED', 'REVERSED'])->default('PENDING');
            $table->unsignedBigInteger('applied_to_bill_id')->nullable();  // Which bill includes this adjustment
            $table->text('notes')->nullable();
            $table->timestamps();
            
            $table->foreign('meter_id')->references('id')->on('meters')->onDelete('cascade');
            $table->foreign('account_id')->references('id')->on('accounts')->onDelete('cascade');
            $table->foreign('triggered_by_reading_id')->references('id')->on('meter_readings')->onDelete('cascade');
            
            // Prevent duplicate reconciliations for same meter/billing_date
            $table->unique(['meter_id', 'billing_date'], 'unique_reconciliation');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('billing_reconciliations');
    }
};