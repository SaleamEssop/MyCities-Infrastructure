@extends('admin.layouts.main')
@section('title', 'Payments')

@section('content')
    <div class="container-fluid">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h1 class="h3 custom-text-heading">Payments</h1>
            <a href="{{ route('add-payment-form') }}" class="btn btn-primary">
                <i class="fa fa-plus"></i> Add Payment
            </a>
        </div>

        @if(session('alert-message'))
            <div class="alert {{ session('alert-class', 'alert-info') }} alert-dismissible fade show" role="alert">
                {{ session('alert-message') }}
                <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
        @endif

        <div class="card shadow mb-4">
            <div class="card-header py-3">
                <h6 class="m-0 font-weight-bold text-primary">All Payments</h6>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-bordered table-hover" id="dataTable" width="100%" cellspacing="0">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Account</th>
                                <th>Amount</th>
                                <th>Date</th>
                                <th>Method</th>
                                <th>Reference</th>
                                <th>Notes</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($payments as $payment)
                                <tr>
                                    <td>{{ $payment->id }}</td>
                                    <td>
                                        @if($payment->account)
                                            {{ $payment->account->account_name }}<br>
                                            <small class="text-muted">{{ $payment->account->account_number }}</small>
                                        @else
                                            <span class="text-muted">N/A</span>
                                        @endif
                                    </td>
                                    <td class="text-success font-weight-bold">R {{ number_format($payment->amount, 2) }}</td>
                                    <td>{{ \Carbon\Carbon::parse($payment->payment_date)->format('Y-m-d') }}</td>
                                    <td>{{ $payment->payment_method ?? 'EFT' }}</td>
                                    <td>{{ $payment->reference }}</td>
                                    <td>{{ $payment->notes }}</td>
                                    <td>
                                        <a href="{{ url('admin/payments/delete/' . $payment->id) }}" 
                                           class="btn btn-danger btn-sm"
                                           onclick="return confirm('Are you sure you want to delete this payment?');">
                                            <i class="fa fa-trash"></i>
                                        </a>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="8" class="text-center text-muted">No payments recorded yet.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        @if($payments->count() > 0)
        <div class="card shadow mb-4">
            <div class="card-body">
                <div class="row">
                    <div class="col-md-4">
                        <div class="card bg-success text-white">
                            <div class="card-body">
                                <h5 class="card-title">Total Collected</h5>
                                <h3>R {{ number_format($payments->sum('amount'), 2) }}</h3>
                                <small>{{ $payments->count() }} payment(s)</small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        @endif
    </div>
@endsection

@section('page-level-scripts')
<script type="text/javascript">
    $(document).ready(function() {
        $('#dataTable').DataTable({
            order: [[3, 'desc']] // Sort by date descending
        });
    });
</script>
@endsection






