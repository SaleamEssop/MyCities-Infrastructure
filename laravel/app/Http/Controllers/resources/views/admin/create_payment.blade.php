@extends('admin.layouts.main')
@section('title', 'Record Payment')

@section('content')
    <div class="container-fluid">
        <h1 class="h3 mb-2 custom-text-heading">Record New Payment</h1>

        <div class="row">
            <!-- LEFT COLUMN: Payment Form -->
            <div class="col-md-6">
                <div class="card shadow mb-4">
                    <div class="card-header py-3">
                        <h6 class="m-0 font-weight-bold text-primary">Payment Details</h6>
                    </div>
                    <div class="card-body">
                        <form method="POST" action="{{ route('add-payment') }}">
                            
                            <!-- 1. Select Site -->
                            <div class="form-group">
                                <label><strong>Select Site:</strong></label>
                                <select class="form-control" id="site_select" required>
                                    <option value="" disabled selected>-- First Select Site --</option>
                                    @foreach($sites as $site)
                                        <option value="{{ $site->id }}">{{ $site->title }}</option>
                                    @endforeach
                                </select>
                            </div>

                            <!-- 2. Select Account -->
                            <div class="form-group">
                                <label><strong>Select Account:</strong></label>
                                <select class="form-control" name="account_id" id="account_select" required disabled>
                                    <option value="" disabled selected>-- Select Site First --</option>
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
                            <button type="submit" class="btn btn-success btn-block">Save Payment</button>
                        </form>
                    </div>
                </div>
            </div>

            <!-- RIGHT COLUMN: Account Summary (Hidden until Account Selected) -->
            <div class="col-md-6" id="account_summary_card" style="display: none;">
                <div class="card shadow mb-4 border-left-success">
                    <div class="card-body">
                        <div class="text-center mb-4">
                            <h4 class="font-weight-bold text-success">MyCities<span class="small text-muted">.co.za</span></h4>
                            <h5 id="summary_site_name" class="text-dark">Site Name</h5>
                            <p class="mb-0"><strong id="summary_account_name">Account Name</strong></p>
                            <small class="text-muted" id="summary_account_number">Acc #123</small>
                        </div>

                        <hr>

                        <h6 class="font-weight-bold">Linked Meters</h6>
                        <ul id="meter_list" class="list-group list-group-flush mb-3">
                            <!-- Meters will be appended here -->
                        </ul>

                        <h6 class="font-weight-bold mt-4">Recent Payment History</h6>
                        <div class="table-responsive">
                            <table class="table table-sm table-bordered">
                                <thead class="thead-light">
                                    <tr>
                                        <th>Date</th>
                                        <th>Amount</th>
                                        <th>Ref</th>
                                    </tr>
                                </thead>
                                <tbody id="payment_history_body">
                                    <!-- Payments appended here -->
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('page-level-scripts')
<script type="text/javascript">
    $(document).ready(function() {
        
        // 1. Load Accounts when Site Changes
        $('#site_select').on('change', function() {
            var siteId = this.value;
            
            // Reset UI
            $("#account_select").html('<option value="">Loading...</option>').prop('disabled', true);
            $("#account_summary_card").hide();

            $.ajax({
                url: "{{ route('get-accounts-by-site') }}",
                type: "POST",
                data: { site_id: siteId, _token: '{{ csrf_token() }}' },
                dataType: 'json',
                success: function(res) {
                    if (res.status == 200 && res.data.length > 0) {
                        $('#account_select').html('<option value="" disabled selected>-- Select Account --</option>');
                        $.each(res.data, function(key, value) {
                            $("#account_select").append('<option value="' + value.id + '">' + value.account_name + ' (' + value.account_number + ')</option>');
                        });
                        $("#account_select").prop('disabled', false);
                    } else {
                        $('#account_select').html('<option value="">No accounts found</option>');
                    }
                }
            });
        });

        // 2. Load Account Details when Account Changes
        $('#account_select').on('change', function() {
            var accountId = this.value;

            $.ajax({
                url: "{{ route('get-account-details') }}",
                type: "POST",
                data: { account_id: accountId, _token: '{{ csrf_token() }}' },
                dataType: 'json',
                success: function(res) {
                    if (res.status == 200) {
                        var data = res.data;

                        // Fill Header Info
                        $('#summary_site_name').text(data.site_name);
                        $('#summary_account_name').text(data.account_name);
                        $('#summary_account_number').text('Account No: ' + data.account_number);

                        // Fill Meters
                        $('#meter_list').empty();
                        if(data.meters.length > 0) {
                            $.each(data.meters, function(index, meterNum) {
                                $('#meter_list').append('<li class="list-group-item py-1"><i class="fas fa-tachometer-alt mr-2 text-info"></i>' + meterNum + '</li>');
                            });
                        } else {
                            $('#meter_list').append('<li class="list-group-item py-1 text-muted">No meters linked</li>');
                        }

                        // Fill Payments
                        $('#payment_history_body').empty();
                        if(data.recent_payments.length > 0) {
                            $.each(data.recent_payments, function(index, pay) {
                                $('#payment_history_body').append(
                                    '<tr>' +
                                    '<td>' + pay.payment_date + '</td>' +
                                    '<td><strong>R ' + pay.amount + '</strong></td>' +
                                    '<td>' + (pay.reference || '-') + '</td>' +
                                    '</tr>'
                                );
                            });
                        } else {
                            $('#payment_history_body').append('<tr><td colspan="3" class="text-center text-muted">No recent payments</td></tr>');
                        }

                        // Show Card
                        $('#account_summary_card').fadeIn();
                    }
                }
            });
        });

    });
</script>
@endsection
