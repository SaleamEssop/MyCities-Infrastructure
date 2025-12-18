@extends('admin.layouts.main')
@section('title', 'Payments')

@section('content')
    <div class="container-fluid">
        <div class="cust-page-head">
            <h1 class="h3 mb-2 custom-text-heading">Payments</h1>
            <a href="{{ route('add-payment-form') }}" class="btn btn-warning btn-circle">
                <i class="fas fa-plus"></i>
            </a>
        </div>

        <div class="card shadow mb-4">
            <div class="card-header py-3">
                <h6 class="m-0 font-weight-bold">Payment History</h6>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-bordered" id="payments-dataTable" width="100%" cellspacing="0">
                        <thead>
                        <tr>
                            <th>#</th>
                            <th>Account</th>
                            <th>Amount</th>
                            <th>Date</th>
                            <th>Reference</th>
                            <th>Notes</th>
                            <th>Actions</th>
                        </tr>
                        </thead>
                        <tbody>
                        @foreach($payments as $payment)
                            <tr>
                                <td>{{ $loop->iteration }}</td>
                                <td>{{ $payment->account->account_name ?? 'N/A' }} ({{ $payment->account->account_number ?? '-' }})</td>
                                <td>{{ $payment->amount }}</td>
                                <td>{{ $payment->payment_date }}</td>
                                <td>{{ $payment->reference }}</td>
                                <td>{{ $payment->notes }}</td>
                                <td>
                                    <a href="{{ url('admin/payments/delete/'.$payment->id) }}" onclick="return confirm('Are you sure?')" class="btn btn-danger btn-circle btn-sm">
                                        <i class="fas fa-trash"></i>
                                    </a>
                                </td>
                            </tr>
                        @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('page-level-scripts')
    <script>
        $(document).ready(function() { $('#payments-dataTable').dataTable(); });
    </script>
@endsection
