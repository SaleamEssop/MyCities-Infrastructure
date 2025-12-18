@extends('admin.layouts.main')
@section('title', 'Record Payment')

@section('content')
    <div class="container-fluid">
        <h1 class="h3 mb-2 custom-text-heading">Record New Payment</h1>

        <div class="cust-form-wrapper">
            <div class="row">
                <div class="col-md-6">
                    <form method="POST" action="{{ route('add-payment') }}">
                        
                        <div class="form-group">
                            <label><strong>Select Site:</strong></label>
                            <select class="form-control" id="site_select" required>
                                <option value="" disabled selected>-- First Select Site --</option>
                                @foreach($sites as $site)
                                    <option value="{{ $site->id }}">{{ $site->title }}</option>
                                @endforeach
                            </select>
                        </div>

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
                            <label><strong>Payment Method:</strong></label>
                            <select class="form-control" name="payment_method" required>
                                <option value="EFT" selected>EFT (Bank Transfer)</option>
                                <option value="Cash">Cash</option>
                                <option value="Card">Card</option>
                                <option value="Debit Order">Debit Order</option>
                                <option value="Other">Other</option>
                            </select>
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

@section('page-level-scripts')
<script type="text/javascript">
    $(document).ready(function() {
        $('#site_select').on('change', function() {
            var siteId = this.value;
            $("#account_select").html('<option value="">Loading...</option>');
            $("#account_select").prop('disabled', true);

            $.ajax({
                url: "{{ route('get-accounts-by-site') }}",
                type: "POST",
                data: {
                    site_id: siteId,
                    _token: '{{ csrf_token() }}'
                },
                dataType: 'json',
                success: function(res) {
                    if (res.status == 200 && res.data.length > 0) {
                        $('#account_select').html('<option value="" disabled selected>-- Select Account --</option>');
                        $.each(res.data, function(key, value) {
                            $("#account_select").append('<option value="' + value.id + '">' + value.account_name + ' (' + value.account_number + ')</option>');
                        });
                        $("#account_select").prop('disabled', false);
                    } else {
                        $('#account_select').html('<option value="">No accounts found for this site</option>');
                    }
                },
                error: function() {
                    $('#account_select').html('<option value="">Error loading accounts</option>');
                }
            });
        });
    });
</script>
@endsection
