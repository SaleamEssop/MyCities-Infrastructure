@extends('admin.layouts.main')
@section('title', 'Add Payment')

@section('content')
    <div class="container-fluid">
        <h1 class="h3 mb-2 custom-text-heading">Record New Payment</h1>

        <div class="cust-form-wrapper">
            <div class="row">
                <div class="col-md-6">
                    <form method="POST" action="{{ route('add-payment') }}">
                        <div class="form-group">
                            <label><strong>Account:</strong></label>
                            <select class="form-control" name="account_id" required>
                                <option value="" disabled selected>-- Select Account --</option>
                                @foreach($accounts as $account)
                                    <option value="{{ $account->id }}">{{ $account->account_name }} - {{ $account->account_number }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="form-group">
                            <label><strong>Amount (R):</strong></label>
                            <input type="number" step="0.01" class="form-control" name="amount" placeholder="0.00" required>
                        </div>
                        <div class="form-group">
                            <label><strong>Payment Date:</strong></label>
                            <input type="date" class="form-control" name="payment_date" value="{{ date('Y-m-d') }}" required>
                        </div>
                        <div class="form-group">
                            <label><strong>Reference:</strong></label>
                            <input type="text" class="form-control" name="reference" placeholder="E.g. EFT-12345">
                        </div>
                        <div class="form-group">
                            <label><strong>Notes:</strong></label>
                            <textarea class="form-control" name="notes" rows="3"></textarea>
                        </div>
                        @csrf
                        <button type="submit" class="btn btn-primary">Save Payment</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection
