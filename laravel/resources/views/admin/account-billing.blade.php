@extends('admin.layouts.main')
@section('title', 'Account Billing - ' . $account->account_name)

@section('content')
<div class="container-fluid">
    <!-- Back Button -->
    <a href="{{ route('user-accounts.manager') }}" class="btn btn-secondary mb-3">
        <i class="fas fa-arrow-left mr-2"></i> Back to User Manager
    </a>

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="fas fa-check-circle mr-2"></i>{{ session('success') }}
            <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                <span aria-hidden="true">&times;</span>
            </button>
        </div>
    @endif

    @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <i class="fas fa-exclamation-circle mr-2"></i>{{ session('error') }}
            <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                <span aria-hidden="true">&times;</span>
            </button>
        </div>
    @endif

    <div class="row">
        <!-- Left: Account Info -->
        <div class="col-lg-4">
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">
                        <i class="fas fa-user mr-2"></i>Account Details
                    </h6>
                </div>
                <div class="card-body">
                    <p><strong>User:</strong> {{ $user->name ?? 'Unknown' }}</p>
                    <p><strong>Email:</strong> {{ $user->email ?? 'N/A' }}</p>
                    <p><strong>Account:</strong> {{ $account->account_name }}</p>
                    <p><strong>Account #:</strong> {{ $account->account_number }}</p>
                    <p><strong>Tariff:</strong> {{ $tariff->template_name ?? 'N/A' }}</p>
                    <p>
                        <strong>Billing Type:</strong> 
                        <span class="badge {{ ($tariff->billing_type ?? 'MONTHLY') === 'DATE_TO_DATE' ? 'badge-info' : 'badge-secondary' }}">
                            {{ $tariff->billing_type ?? 'MONTHLY' }}
                        </span>
                    </p>
                </div>
            </div>

            <!-- Add Payment Form -->
            <div class="card shadow mb-4">
                <div class="card-header py-3 bg-success text-white">
                    <h6 class="m-0 font-weight-bold">
                        <i class="fas fa-plus mr-2"></i>Add Payment
                    </h6>
                </div>
                <div class="card-body">
                    <form method="POST" action="{{ route('user-accounts.manager.add-payment') }}">
                        @csrf
                        <input type="hidden" name="account_id" value="{{ $account->id }}">
                        
                        <div class="form-group">
                            <label>Amount (R)</label>
                            <input type="number" step="0.01" name="amount" class="form-control form-control-lg" placeholder="0.00" required>
                        </div>
                        
                        <div class="form-group">
                            <label>Payment Date</label>
                            <input type="date" name="payment_date" class="form-control" value="{{ date('Y-m-d') }}" required>
                        </div>
                        
                        <div class="form-group">
                            <label>Method</label>
                            <select name="payment_method" class="form-control">
                                <option value="EFT">EFT</option>
                                <option value="Cash">Cash</option>
                                <option value="Card">Card</option>
                                <option value="Debit Order">Debit Order</option>
                            </select>
                        </div>
                        
                        <div class="form-group">
                            <label>Reference</label>
                            <input type="text" name="reference" class="form-control" placeholder="PAY-...">
                        </div>
                        
                        <div class="form-group">
                            <label>Notes</label>
                            <textarea name="notes" class="form-control" rows="2"></textarea>
                        </div>
                        
                        <button type="submit" class="btn btn-success btn-block">
                            <i class="fas fa-check mr-2"></i>Record Payment
                        </button>
                    </form>
                </div>
            </div>
        </div>

        <!-- Right: Mobile Preview -->
        <div class="col-lg-8">
            <div class="d-flex justify-content-center">
                <!-- Mobile Frame -->
                <div class="mobile-frame">
                    <div class="mobile-notch"></div>
                    <div class="mobile-billing-container">
                        <!-- Header -->
                        <div class="mobile-header">
                            <h2>User: {{ $user->name ?? 'Unknown' }}</h2>
                            <div class="current-date">Current Date: {{ now()->format('jS F Y') }}</div>
                        </div>

                        <!-- Total Owing -->
                        <div class="payment-entry-section">
                            <div class="label">Total Outstanding</div>
                            <div class="payment-amount-display">
                                <span class="amount {{ $totalOwing > 0 ? 'text-danger' : 'text-success' }}">
                                    R{{ number_format(abs($totalOwing), 2) }}
                                    @if($totalOwing < 0) <small>(Credit)</small> @endif
                                </span>
                            </div>
                        </div>

                        <!-- Recent Payments (after last period) -->
                        @if(count($recentPayments) > 0)
                        <div class="recent-payments-section">
                            <div class="recent-payments-header">
                                <i class="fas fa-clock mr-2"></i>Recent Payments
                            </div>
                            <div class="recent-payments-list">
                                @foreach($recentPayments as $payment)
                                <div class="recent-payment-item">
                                    <span>{{ \Carbon\Carbon::parse($payment['date'])->format('j M Y') }}</span>
                                    <span class="text-success font-weight-bold">R{{ number_format($payment['amount'], 2) }}</span>
                                </div>
                                @endforeach
                            </div>
                        </div>
                        @endif

                        <!-- Billing Periods -->
                        <div class="billing-periods">
                            @forelse($periods as $index => $period)
                                <div class="period-card">
                                    <div class="period-header {{ $index === 0 ? 'current' : '' }}">
                                        <span class="period-dates">
                                            {{ \Carbon\Carbon::parse($period['start_date'])->format('jS M') }} > 
                                            {{ \Carbon\Carbon::parse($period['end_date'])->format('jS M') }}
                                        </span>
                                        <span class="period-total">R{{ number_format($period['period_total'], 2) }}</span>
                                    </div>
                                    <div class="period-details">
                                        <div class="period-row">
                                            <span class="label">Consumption - {{ $period['days'] }} days</span>
                                            <span class="value">R{{ number_format($period['consumption_charge'], 2) }}</span>
                                        </div>
                                        
                                        @if($period['balance_bf'] > 0)
                                        <div class="period-row">
                                            <span class="label">Balance B/F</span>
                                            <span class="value">R{{ number_format($period['balance_bf'], 2) }}</span>
                                        </div>
                                        @endif
                                        
                                        @foreach($period['payments'] as $payment)
                                        <div class="period-row payment">
                                            <span class="label">Payment - {{ \Carbon\Carbon::parse($payment['date'])->format('j M Y') }}</span>
                                            <span class="value">R{{ number_format($payment['amount'], 2) }}</span>
                                        </div>
                                        @endforeach
                                        
                                        <div class="period-row balance">
                                            <span class="label">Balance</span>
                                            <span class="value {{ $period['balance'] > 0 ? 'text-danger' : '' }}">
                                                R{{ number_format($period['balance'], 2) }}
                                            </span>
                                        </div>
                                    </div>
                                </div>
                            @empty
                                <div class="no-periods">
                                    <i class="fas fa-info-circle fa-2x mb-3"></i>
                                    <p>No billing periods found.<br>Add meter readings to generate billing.</p>
                                </div>
                            @endforelse
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
/* Mobile Frame Styles */
.mobile-frame {
    background: #1a1a1a;
    padding: 10px;
    border-radius: 35px;
    box-shadow: 0 20px 60px rgba(0,0,0,0.3);
}

.mobile-notch {
    width: 120px;
    height: 25px;
    background: #1a1a1a;
    border-radius: 0 0 15px 15px;
    margin: 0 auto -5px;
    position: relative;
    z-index: 10;
}

.mobile-billing-container {
    width: 375px;
    min-height: 650px;
    max-height: 75vh;
    overflow-y: auto;
    background: #f5f5f5;
    border-radius: 25px;
    font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
}

.mobile-header {
    background: #fff;
    padding: 20px;
    border-bottom: 1px solid #eee;
}

.mobile-header h2 {
    margin: 0 0 5px 0;
    font-size: 18px;
    font-weight: 600;
    color: #333;
}

.mobile-header .current-date {
    font-size: 13px;
    color: #666;
}

.payment-entry-section {
    background: #fff;
    padding: 20px;
    text-align: center;
    border-bottom: 1px solid #eee;
}

.payment-entry-section .label {
    font-size: 13px;
    color: #666;
    margin-bottom: 10px;
}

.payment-amount-display {
    background: #f0f0f0;
    border-radius: 10px;
    padding: 20px;
}

.payment-amount-display .amount {
    font-size: 32px;
    font-weight: 700;
    color: #333;
}

.payment-amount-display .amount.text-danger {
    color: #dc3545 !important;
}

.payment-amount-display .amount.text-success {
    color: #28a745 !important;
}

.billing-periods {
    padding: 15px;
}

.no-periods {
    text-align: center;
    padding: 60px 20px;
    color: #888;
}

.period-card {
    background: #fff;
    border-radius: 12px;
    margin-bottom: 15px;
    overflow: hidden;
    box-shadow: 0 2px 8px rgba(0,0,0,0.08);
}

.period-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 15px 20px;
    background: #e8e8e8;
}

.period-header.current {
    background: linear-gradient(135deg, #4ECDC4, #44A08D);
    color: #fff;
}

.period-header .period-dates {
    font-size: 15px;
    font-weight: 600;
}

.period-header .period-total {
    font-size: 18px;
    font-weight: 700;
}

.period-details {
    padding: 12px 20px;
}

.period-row {
    display: flex;
    justify-content: space-between;
    padding: 8px 0;
    font-size: 13px;
    color: #555;
}

.period-row .label {
    color: #666;
}

.period-row .value {
    font-weight: 500;
    color: #333;
}

.period-row.balance {
    border-top: 1px solid #eee;
    margin-top: 5px;
    padding-top: 12px;
}

.period-row.balance .label {
    font-weight: 600;
    color: #333;
}

.period-row.balance .value {
    font-weight: 700;
}

.period-row.payment .value {
    color: #28a745;
}

/* Recent Payments Section */
.recent-payments-section {
    background: #fff;
    margin: 0 15px 15px;
    border-radius: 12px;
    overflow: hidden;
    box-shadow: 0 2px 8px rgba(0,0,0,0.08);
    border-left: 4px solid #28a745;
}

.recent-payments-header {
    background: #e8f5e9;
    padding: 12px 16px;
    font-weight: 600;
    color: #2e7d32;
    font-size: 14px;
}

.recent-payments-list {
    padding: 10px 16px;
}

.recent-payment-item {
    display: flex;
    justify-content: space-between;
    padding: 8px 0;
    font-size: 13px;
    border-bottom: 1px solid #f0f0f0;
}

.recent-payment-item:last-child {
    border-bottom: none;
}
</style>
@endsection

