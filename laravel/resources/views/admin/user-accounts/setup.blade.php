@extends('admin.layouts.main')
@section('title', 'User Account Setup')

@section('content')
<div class="container-fluid">
    <h1 class="h3 mb-2 text-gray-800">User Account Setup</h1>
    <p class="mb-4">Create a new user account with region, tariff, and meters.</p>

    @if(Session::has('success'))
        <div class="alert alert-success alert-dismissible fade show">
            <pre style="white-space: pre-wrap; margin: 0;">{{ Session::get('success') }}</pre>
            <button type="button" class="close" data-dismiss="alert">&times;</button>
        </div>
    @endif

    @if(Session::has('error'))
        <div class="alert alert-danger alert-dismissible fade show">
            {{ Session::get('error') }}
            <button type="button" class="close" data-dismiss="alert">&times;</button>
        </div>
    @endif

    <div class="row">
        <!-- Main Setup Form -->
        <div class="col-lg-8">
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">
                        <i class="fas fa-user-plus"></i> New User Account
                    </h6>
                </div>
                <div class="card-body">
                    <form id="setupForm">
                        @csrf
                        
                        <!-- Step 1: User Details -->
                        <div class="form-section">
                            <h5 class="text-primary mb-3">
                                <span class="badge badge-primary mr-2">1</span> User Details
                            </h5>
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label><strong>Full Name *</strong></label>
                                        <input type="text" class="form-control" name="name" id="name" required placeholder="Enter full name">
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label><strong>Email Address *</strong></label>
                                        <input type="email" class="form-control" name="email" id="email" required placeholder="Enter email">
                                        <small id="emailFeedback" class="form-text"></small>
                                    </div>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label><strong>Contact Number *</strong></label>
                                        <input type="text" class="form-control" name="contact_number" id="contact_number" required placeholder="e.g., 0831234567">
                                        <small id="phoneFeedback" class="form-text"></small>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label><strong>Password *</strong></label>
                                        <input type="password" class="form-control" name="password" id="password" required placeholder="Min 6 characters">
                                    </div>
                                </div>
                            </div>
                        </div>

                        <hr>

                        <!-- Step 2: Region & Tariff -->
                        <div class="form-section">
                            <h5 class="text-primary mb-3">
                                <span class="badge badge-primary mr-2">2</span> Region & Tariff
                            </h5>
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label><strong>Select Region *</strong></label>
                                        <select class="form-control" name="region_id" id="region_id" required>
                                            <option value="">-- Select Region --</option>
                                            @foreach($regions as $region)
                                                <option value="{{ $region->id }}">{{ $region->name }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label><strong>Select Tariff Template *</strong></label>
                                        <select class="form-control" name="tariff_template_id" id="tariff_template_id" required disabled>
                                            <option value="">-- Select Region First --</option>
                                        </select>
                                        <small id="tariffInfo" class="form-text text-muted"></small>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <hr>

                        <!-- Step 3: Site & Account -->
                        <div class="form-section">
                            <h5 class="text-primary mb-3">
                                <span class="badge badge-primary mr-2">3</span> Site & Account Details
                            </h5>
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label><strong>Site Title</strong></label>
                                        <input type="text" class="form-control" name="site_title" id="site_title" placeholder="e.g., My Home">
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label><strong>Address</strong></label>
                                        <input type="text" class="form-control" name="address" id="address" placeholder="Street address">
                                    </div>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label><strong>Account Name</strong></label>
                                        <input type="text" class="form-control" name="account_name" id="account_name" placeholder="e.g., Main House">
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label><strong>Account Number</strong></label>
                                        <input type="text" class="form-control" name="account_number" id="account_number" placeholder="Optional - auto-generated if empty">
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Template-Specific Fields Container -->
                            <div id="templateFieldsContainer" style="display: none;">
                                <hr>
                                <h6 class="text-info mb-3"><i class="fas fa-sliders-h"></i> Template-Specific Fields</h6>
                                
                                <!-- Fixed Costs (Read-only) -->
                                <div id="fixedCostsSection" style="display: none;">
                                    <label class="text-muted"><strong>Fixed Costs</strong> <small>(from template - not editable)</small></label>
                                    <div id="fixedCostsList" class="mb-3"></div>
                                </div>
                                
                                <!-- Customer Editable Costs -->
                                <div id="customerCostsSection" style="display: none;">
                                    <label class="text-success"><strong>Customer Editable Costs</strong> <small>(you can modify these)</small></label>
                                    <div id="customerCostsList" class="mb-3"></div>
                                </div>
                            </div>
                        </div>

                        <hr>

                        <!-- Step 4: Meters -->
                        <div class="form-section">
                            <h5 class="text-primary mb-3">
                                <span class="badge badge-primary mr-2">4</span> Meters (Optional)
                            </h5>
                            <div id="metersContainer">
                                <!-- Water Meter -->
                                <div class="meter-item card mb-3" data-meter-type="water">
                                    <div class="card-body">
                                        <div class="row align-items-center">
                                            <div class="col-md-1">
                                                <input type="checkbox" class="meter-enabled" id="waterMeterEnabled" checked>
                                            </div>
                                            <div class="col-md-2">
                                                <i class="fas fa-tint fa-2x text-primary"></i>
                                                <strong>Water</strong>
                                            </div>
                                            <div class="col-md-4">
                                                <input type="text" class="form-control form-control-sm" name="meters[0][meter_number]" id="water_meter_number" placeholder="Meter Number">
                                                <input type="hidden" name="meters[0][meter_type_id]" value="1">
                                                <input type="hidden" name="meters[0][meter_title]" value="Water Meter">
                                            </div>
                                            <div class="col-md-4">
                                                <input type="number" class="form-control form-control-sm" name="meters[0][initial_reading]" id="water_initial_reading" placeholder="Initial Reading (optional)">
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Electricity Meter -->
                                <div class="meter-item card mb-3" data-meter-type="electricity">
                                    <div class="card-body">
                                        <div class="row align-items-center">
                                            <div class="col-md-1">
                                                <input type="checkbox" class="meter-enabled" id="elecMeterEnabled" checked>
                                            </div>
                                            <div class="col-md-2">
                                                <i class="fas fa-bolt fa-2x text-warning"></i>
                                                <strong>Electricity</strong>
                                            </div>
                                            <div class="col-md-4">
                                                <input type="text" class="form-control form-control-sm" name="meters[1][meter_number]" id="elec_meter_number" placeholder="Meter Number">
                                                <input type="hidden" name="meters[1][meter_type_id]" value="2">
                                                <input type="hidden" name="meters[1][meter_title]" value="Electricity Meter">
                                            </div>
                                            <div class="col-md-4">
                                                <input type="number" class="form-control form-control-sm" name="meters[1][initial_reading]" id="elec_initial_reading" placeholder="Initial Reading (optional)">
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <hr>

                        <!-- Submit Buttons -->
                        <div class="form-group">
                            <button type="submit" class="btn btn-primary btn-lg" id="submitFull">
                                <i class="fas fa-user-plus"></i> Create Full Account
                            </button>
                            <button type="button" class="btn btn-secondary" id="submitUserOnly">
                                <i class="fas fa-user"></i> Create User Only
                            </button>
                            <small class="d-block text-muted mt-2">
                                "Create User Only" creates just the user login - they can set up region and account later via the mobile app.
                            </small>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- Quick Test User Creation -->
        <div class="col-lg-4">
            <div class="card shadow mb-4 border-left-success">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-success">
                        <i class="fas fa-magic"></i> Quick Test User
                    </h6>
                </div>
                <div class="card-body">
                    <p class="text-muted small">
                        Create a test user using the form data on the left. Fill in any fields you want to use, 
                        or leave blank for auto-generated values.
                    </p>
                    
                    <form id="testUserForm" action="{{ route('user-accounts.setup.create-test-user') }}" method="POST">
                        @csrf
                        <!-- Hidden fields to pass form data -->
                        <input type="hidden" name="use_form_data" value="1">
                        <input type="hidden" name="form_name" id="form_name_hidden">
                        <input type="hidden" name="form_email" id="form_email_hidden">
                        <input type="hidden" name="form_phone" id="form_phone_hidden">
                        <input type="hidden" name="form_password" id="form_password_hidden">
                        <input type="hidden" name="form_region_id" id="form_region_hidden">
                        <input type="hidden" name="form_tariff_id" id="form_tariff_hidden">
                        <input type="hidden" name="form_site_title" id="form_site_hidden">
                        <input type="hidden" name="form_address" id="form_address_hidden">
                        <input type="hidden" name="form_account_name" id="form_account_hidden">
                        <input type="hidden" name="form_account_number" id="form_accnum_hidden">
                        <input type="hidden" name="form_water_meter" id="form_water_meter_hidden">
                        <input type="hidden" name="form_water_reading" id="form_water_reading_hidden">
                        <input type="hidden" name="form_elec_meter" id="form_elec_meter_hidden">
                        <input type="hidden" name="form_elec_reading" id="form_elec_reading_hidden">
                        
                        <div class="form-group">
                            <label><strong>Seed Data (Months of Readings)</strong></label>
                            <select class="form-control" name="seed_months" id="seed_months">
                                <option value="0">0 Months (Real User - No Test Data)</option>
                                <option value="3">3 Months</option>
                                <option value="4">4 Months</option>
                                <option value="5">5 Months</option>
                                <option value="6" selected>6 Months</option>
                            </select>
                            <small class="text-muted">0 = Real user setup. 3-6 = Test data for billing logic.</small>
                        </div>
                        
                        <div class="form-check mb-2">
                            <input class="form-check-input" type="checkbox" name="create_new_region" id="createNewRegion">
                            <label class="form-check-label" for="createNewRegion">
                                Create new test region
                            </label>
                        </div>
                        <div class="form-check mb-3">
                            <input class="form-check-input" type="checkbox" name="create_new_tariff" id="createNewTariff">
                            <label class="form-check-label" for="createNewTariff">
                                Create new test tariff
                            </label>
                        </div>
                        <button type="submit" class="btn btn-success btn-block" id="createTestUserBtn">
                            <i class="fas fa-bolt"></i> Create Test User
                        </button>
                    </form>
                    
                    <hr>
                    
                    <div class="small text-muted">
                        <strong>Uses form data if filled, otherwise:</strong>
                        <ul class="mb-0 pl-3">
                            <li>Email: <code>demo###@mycities.co.za</code></li>
                            <li>Password: <code>demo123</code></li>
                            <li>Water + Electricity meters</li>
                            <li>Selected months of readings</li>
                        </ul>
                    </div>
                </div>
            </div>

            <!-- Existing Regions Info -->
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-info">
                        <i class="fas fa-info-circle"></i> Available Regions
                    </h6>
                </div>
                <div class="card-body">
                    @if($regions->count() > 0)
                        <ul class="list-unstyled mb-0">
                            @foreach($regions as $region)
                                <li class="mb-2">
                                    <i class="fas fa-map-marker-alt text-primary"></i> 
                                    <strong>{{ $region->name }}</strong>
                                    @php
                                        $tariffCount = \App\Models\RegionsAccountTypeCost::where('region_id', $region->id)->where('is_active', 1)->count();
                                    @endphp
                                    <span class="badge badge-info">{{ $tariffCount }} tariffs</span>
                                </li>
                            @endforeach
                        </ul>
                    @else
                        <p class="text-muted mb-0">No regions configured. <a href="{{ route('region') }}">Add a region</a> first.</p>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Result Modal -->
<div class="modal fade" id="resultModal" tabindex="-1" role="dialog">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="resultModalTitle">Result</h5>
                <button type="button" class="close" data-dismiss="modal">&times;</button>
            </div>
            <div class="modal-body" id="resultModalBody">
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-primary" data-dismiss="modal" onclick="window.location.reload()">OK</button>
            </div>
        </div>
    </div>
</div>
@endsection

@section('script')
<script>
$(document).ready(function() {
    const csrfToken = '{{ csrf_token() }}';
    
    // Sync form data to hidden fields for test user creation
    function syncFormData() {
        $('#form_name_hidden').val($('#name').val());
        $('#form_email_hidden').val($('#email').val());
        $('#form_phone_hidden').val($('#contact_number').val());
        $('#form_password_hidden').val($('#password').val());
        $('#form_region_hidden').val($('#region_id').val());
        $('#form_tariff_hidden').val($('#tariff_template_id').val());
        $('#form_site_hidden').val($('#site_title').val());
        $('#form_address_hidden').val($('#address').val());
        $('#form_account_hidden').val($('#account_name').val());
        $('#form_accnum_hidden').val($('#account_number').val());
        $('#form_water_meter_hidden').val($('#water_meter_number').val());
        $('#form_water_reading_hidden').val($('#water_initial_reading').val());
        $('#form_elec_meter_hidden').val($('#elec_meter_number').val());
        $('#form_elec_reading_hidden').val($('#elec_initial_reading').val());
    }
    
    // Sync on any input change
    $('#setupForm input, #setupForm select').on('change input', syncFormData);
    
    // Also sync before test user form submit
    $('#testUserForm').on('submit', function() {
        syncFormData();
    });
    
    // Region change - load tariffs
    $('#region_id').change(function() {
        const regionId = $(this).val();
        const tariffSelect = $('#tariff_template_id');
        const tariffInfo = $('#tariffInfo');
        
        // Hide template fields
        $('#templateFieldsContainer').hide();
        $('#fixedCostsSection').hide();
        $('#customerCostsSection').hide();
        
        if (!regionId) {
            tariffSelect.html('<option value="">-- Select Region First --</option>').prop('disabled', true);
            tariffInfo.text('');
            return;
        }
        
        tariffSelect.html('<option value="">Loading...</option>').prop('disabled', true);
        
        $.get('{{ route("user-accounts.setup.tariffs", ["regionId" => "__ID__"]) }}'.replace('__ID__', regionId))
            .done(function(response) {
                if (response.data && response.data.length > 0) {
                    let options = '<option value="">-- Select Tariff --</option>';
                    response.data.forEach(function(tariff) {
                        let info = [];
                        if (tariff.is_water) info.push('Water');
                        if (tariff.is_electricity) info.push('Electricity');
                        let dates = '';
                        if (tariff.start_date && tariff.end_date) {
                            dates = ' (' + tariff.start_date + ' to ' + tariff.end_date + ')';
                        }
                        options += '<option value="' + tariff.id + '" data-info="' + info.join(', ') + dates + '">' + 
                                   tariff.template_name + '</option>';
                    });
                    tariffSelect.html(options).prop('disabled', false);
                    tariffInfo.text(response.data.length + ' tariff(s) available');
                } else {
                    tariffSelect.html('<option value="">No tariffs for this region</option>').prop('disabled', true);
                    tariffInfo.html('<span class="text-danger">No active tariffs. <a href="{{ route("tariff-template-create") }}">Create one</a></span>');
                }
            })
            .fail(function() {
                tariffSelect.html('<option value="">Error loading tariffs</option>').prop('disabled', true);
                tariffInfo.text('Failed to load tariffs');
            });
    });
    
    // Tariff change - load template details
    $('#tariff_template_id').change(function() {
        const tariffId = $(this).val();
        const selected = $(this).find(':selected');
        const info = selected.data('info');
        $('#tariffInfo').text(info || '');
        
        if (!tariffId) {
            $('#templateFieldsContainer').hide();
            return;
        }
        
        // Load tariff details
        $.get('/admin/user-accounts/setup/tariff-details/' + tariffId)
            .done(function(response) {
                if (response.status === 200 && response.data) {
                    const data = response.data;
                    
                    // Show container
                    $('#templateFieldsContainer').show();
                    
                    // Fixed Costs
                    if (data.fixed_costs && data.fixed_costs.length > 0) {
                        let html = '';
                        data.fixed_costs.forEach(function(cost) {
                            html += '<div class="row mb-2">' +
                                '<div class="col-md-8">' +
                                '<input type="text" class="form-control form-control-sm bg-light" value="' + (cost.title || cost.name || 'Fixed Cost') + '" readonly>' +
                                '</div>' +
                                '<div class="col-md-4">' +
                                '<div class="input-group input-group-sm">' +
                                '<div class="input-group-prepend"><span class="input-group-text">R</span></div>' +
                                '<input type="text" class="form-control bg-light" value="' + parseFloat(cost.value || cost.amount || 0).toFixed(2) + '" readonly>' +
                                '</div></div></div>';
                        });
                        $('#fixedCostsList').html(html);
                        $('#fixedCostsSection').show();
                    } else {
                        $('#fixedCostsSection').hide();
                    }
                    
                    // Customer Editable Costs
                    if (data.customer_costs && data.customer_costs.length > 0) {
                        let html = '';
                        data.customer_costs.forEach(function(cost, index) {
                            html += '<div class="row mb-2">' +
                                '<div class="col-md-8">' +
                                '<input type="text" class="form-control form-control-sm" name="customer_costs[' + index + '][title]" value="' + (cost.title || cost.name || '') + '" placeholder="Cost Name">' +
                                '</div>' +
                                '<div class="col-md-4">' +
                                '<div class="input-group input-group-sm">' +
                                '<div class="input-group-prepend"><span class="input-group-text">R</span></div>' +
                                '<input type="number" step="0.01" class="form-control" name="customer_costs[' + index + '][value]" value="' + parseFloat(cost.value || cost.amount || 0).toFixed(2) + '">' +
                                '</div></div></div>';
                        });
                        $('#customerCostsList').html(html);
                        $('#customerCostsSection').show();
                    } else {
                        $('#customerCostsSection').hide();
                    }
                }
            })
            .fail(function() {
                $('#templateFieldsContainer').hide();
            });
    });
    
    // Email validation
    $('#email').blur(function() {
        const email = $(this).val();
        if (!email) return;
        
        $.post('{{ route("user-accounts.setup.validate-email") }}', {
            _token: csrfToken,
            email: email
        }).done(function(response) {
            if (response.exists) {
                $('#emailFeedback').html('<span class="text-danger">Email already exists</span>');
                $('#email').addClass('is-invalid');
            } else {
                $('#emailFeedback').html('<span class="text-success">Email available</span>');
                $('#email').removeClass('is-invalid').addClass('is-valid');
            }
        });
    });
    
    // Phone validation
    $('#contact_number').blur(function() {
        const phone = $(this).val();
        if (!phone) return;
        
        $.post('{{ route("user-accounts.setup.validate-phone") }}', {
            _token: csrfToken,
            contact_number: phone
        }).done(function(response) {
            if (response.exists) {
                $('#phoneFeedback').html('<span class="text-danger">Phone number already exists</span>');
                $('#contact_number').addClass('is-invalid');
            } else {
                $('#phoneFeedback').html('<span class="text-success">Phone available</span>');
                $('#contact_number').removeClass('is-invalid').addClass('is-valid');
            }
        });
    });
    
    // Toggle meter inputs
    $('.meter-enabled').change(function() {
        const meterItem = $(this).closest('.meter-item');
        const inputs = meterItem.find('input[type="text"], input[type="number"]');
        inputs.prop('disabled', !$(this).is(':checked'));
    });
    
    // Full account submission
    $('#setupForm').submit(function(e) {
        e.preventDefault();
        
        // Disable unchecked meters
        $('.meter-enabled:not(:checked)').each(function() {
            $(this).closest('.meter-item').find('input').prop('disabled', true);
        });
        
        const formData = $(this).serialize();
        
        $.post('{{ route("user-accounts.setup.store") }}', formData)
            .done(function(response) {
                if (response.status === 200) {
                    $('#resultModalTitle').text('Success').removeClass('text-danger').addClass('text-success');
                    $('#resultModalBody').html(
                        '<div class="alert alert-success">' +
                        '<i class="fas fa-check-circle"></i> ' + response.message +
                        '</div>' +
                        '<p>User ID: ' + response.user_id + '</p>' +
                        '<p>Account ID: ' + response.account_id + '</p>'
                    );
                } else {
                    $('#resultModalTitle').text('Error').removeClass('text-success').addClass('text-danger');
                    $('#resultModalBody').html('<div class="alert alert-danger">' + response.message + '</div>');
                }
                $('#resultModal').modal('show');
            })
            .fail(function(xhr) {
                let errorMsg = 'An error occurred';
                if (xhr.responseJSON && xhr.responseJSON.message) {
                    errorMsg = xhr.responseJSON.message;
                } else if (xhr.responseJSON && xhr.responseJSON.errors) {
                    errorMsg = Object.values(xhr.responseJSON.errors).flat().join('<br>');
                }
                $('#resultModalTitle').text('Error').removeClass('text-success').addClass('text-danger');
                $('#resultModalBody').html('<div class="alert alert-danger">' + errorMsg + '</div>');
                $('#resultModal').modal('show');
            });
    });
    
    // User only submission
    $('#submitUserOnly').click(function() {
        const formData = {
            _token: csrfToken,
            name: $('#name').val(),
            email: $('#email').val(),
            contact_number: $('#contact_number').val(),
            password: $('#password').val()
        };
        
        $.post('{{ route("user-accounts.setup.store-user-only") }}', formData)
            .done(function(response) {
                if (response.status === 200) {
                    $('#resultModalTitle').text('Success').removeClass('text-danger').addClass('text-success');
                    $('#resultModalBody').html(
                        '<div class="alert alert-success">' +
                        '<i class="fas fa-check-circle"></i> ' + response.message +
                        '</div>' +
                        '<p>User ID: ' + response.user_id + '</p>'
                    );
                } else {
                    $('#resultModalTitle').text('Error').removeClass('text-success').addClass('text-danger');
                    $('#resultModalBody').html('<div class="alert alert-danger">' + response.message + '</div>');
                }
                $('#resultModal').modal('show');
            })
            .fail(function(xhr) {
                let errorMsg = 'An error occurred';
                if (xhr.responseJSON && xhr.responseJSON.message) {
                    errorMsg = xhr.responseJSON.message;
                } else if (xhr.responseJSON && xhr.responseJSON.errors) {
                    errorMsg = Object.values(xhr.responseJSON.errors).flat().join('<br>');
                }
                $('#resultModalTitle').text('Error').removeClass('text-success').addClass('text-danger');
                $('#resultModalBody').html('<div class="alert alert-danger">' + errorMsg + '</div>');
                $('#resultModal').modal('show');
            });
    });
});
</script>

<style>
.form-section {
    padding: 15px;
    background: #f8f9fc;
    border-radius: 8px;
    margin-bottom: 20px;
}
.meter-item {
    border-left: 4px solid #4e73df;
}
.meter-item[data-meter-type="electricity"] {
    border-left-color: #f6c23e;
}
#templateFieldsContainer {
    background: #e8f4f8;
    padding: 15px;
    border-radius: 6px;
    border: 1px dashed #17a2b8;
}
</style>
@endsection
