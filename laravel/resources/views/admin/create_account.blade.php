@extends('admin.layouts.main')
@section('title', 'Accounts')

@section('content')
    <div class="container-fluid">
        <h1 class="h3 mb-2 custom-text-heading">Create New Account</h1>
        <div class="cust-form-wrapper">
            <div class="row">
                <div class="col-md-8">
                    <form method="POST" action="{{ route('add-account') }}">
                        
                        <!-- 1. User Selection -->
                        <div class="form-group">
                            <label>User (Account Owner): </label>
                            <select class="form-control" id="user-select" name="user_id" required>
                                <option disabled selected value="">--Select User--</option>
                                @foreach($users as $user)
                                    <option value="{{ $user->id }}">{{ $user->name }}</option>
                                @endforeach
                            </select>
                        </div>

                        <!-- 2. Location Address (Site) -->
                        <div class="form-group">
                            <label>Location Address: </label>
                            <select class="form-control" id="site-select" name="site_id" required disabled>
                                <option disabled selected value="">--Select User First--</option>
                            </select>
                        </div>

                        <!-- 3. Region Selection -->
                        <div class="form-group">
                            <label>Region: </label>
                            <select class="form-control" id="region-select" name="region_id" required>
                                <option disabled selected value="">--Select Region--</option>
                                @foreach($regions as $region)
                                    <option value="{{ $region->id }}">{{ $region->name }}</option>
                                @endforeach
                            </select>
                        </div>

                        <!-- 4. Tariff Template (populated via AJAX based on Region) -->
                        <div class="form-group">
                            <label>Tariff Template: </label>
                            <select class="form-control" id="tariff-template-select" name="tariff_template_id" required disabled>
                                <option disabled selected value="">--Select Region First--</option>
                            </select>
                            <small class="form-text text-muted">Select a region first to see available tariff templates.</small>
                        </div>

                        <!-- 5. Emails (Auto-Fetched from Region) -->
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>Water Email (Auto): </label>
                                    <input type="text" id="water-email" class="form-control" readonly placeholder="N/A">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>Electricity Email (Auto): </label>
                                    <input type="text" id="elec-email" class="form-control" readonly placeholder="N/A">
                                </div>
                            </div>
                        </div>

                        <!-- 6. Account Name -->
                        <div class="form-group">
                            <label>Account Name (As per bill): </label>
                            <input type="text" class="form-control" placeholder="Enter name" name="title" required>
                        </div>
                        
                        <!-- 7. Account Number -->
                        <div class="form-group">
                            <label>Account Number (As per bill): </label>
                            <input type="text" class="form-control" placeholder="Enter account number" name="number" required>
                        </div>
                        
                        <!-- 8. Account Description -->
                        <div class="form-group">
                            <label>Account Description: </label>
                            <input type="text" name="optional_info" class="form-control" placeholder="e.g. Cottage, Main House">
                        </div>

                        <!-- 9. Bill Day & 10. Read Day -->
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>Bill Day: </label>
                                    <input type="number" id="bill-day" min="1" max="31" class="form-control" placeholder="1-31" name="billing_date" required>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>Read Day (Auto: Bill Day - 5): </label>
                                    <input type="text" id="read-day" class="form-control" readonly placeholder="Auto-calculated">
                                </div>
                            </div>
                        </div>
                        
                        <br>
                        @csrf
                        <button type="submit" class="btn btn-primary">Create Account</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('page-level-scripts')
    <script type="text/javascript">
        $(document).ready(function() {
            
            // 1. Load Sites
            $(document).on("change", '#user-select', function () {
                let user_id = $(this).val();
                $.ajax({
                    type: 'POST',
                    dataType: 'JSON',
                    url: '{{ route("get-sites-by-user") }}',
                    data: { user_id: user_id, _token: '{{ csrf_token() }}' },
                    success: function (result) {
                        $('#site-select').empty();
                        $('#site-select').append('<option disabled selected value="">--Select Location Address--</option>');
                        
                        window.siteData = {}; 
                        $.each(result.data, function(key, value) {
                            window.siteData[value.id] = value;
                            $('#site-select').append($('<option>', { value: value.id, text: value.address }));
                        });
                        $('#site-select').prop('disabled', false);
                    }
                });
            });

            // 2. Auto-Fill Emails when site changes
            $(document).on("change", '#site-select', function () {
                let siteId = $(this).val();
                let site = window.siteData[siteId];
                if(site && site.region) {
                    $('#water-email').val(site.region.water_email || 'N/A');
                    $('#elec-email').val(site.region.electricity_email || 'N/A');
                }
            });

            // 3. Load Tariff Templates when Region changes
            $(document).on("change", '#region-select', function () {
                let region_id = $(this).val();
                if(region_id) {
                    $.ajax({
                        type: 'GET',
                        dataType: 'JSON',
                        url: '/admin/tariff-templates/by-region/' + region_id,
                        success: function (result) {
                            $('#tariff-template-select').empty();
                            $('#tariff-template-select').append('<option disabled selected value="">--Select Tariff Template--</option>');
                            
                            if(result.data && result.data.length > 0) {
                                $.each(result.data, function(key, value) {
                                    let displayText = value.template_name + ' (' + value.start_date + ' to ' + value.end_date + ')';
                                    $('#tariff-template-select').append($('<option>', { value: value.id, text: displayText }));
                                });
                                $('#tariff-template-select').prop('disabled', false);
                            } else {
                                $('#tariff-template-select').append('<option disabled value="">No tariff templates available for this region</option>');
                            }
                        }
                    });
                }
            });

            // 4. Auto-Calc Read Day
            $(document).on("keyup change", '#bill-day', function() {
                let billDay = parseInt($(this).val());
                if(billDay) {
                    let readDay = billDay - 5;
                    if(readDay < 1) readDay = 30 + readDay; 
                    $('#read-day').val(readDay);
                } else {
                    $('#read-day').val('');
                }
            });
        });
    </script>
@endsection
