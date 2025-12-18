@extends('admin.layouts.main')
@section('title', 'Add Site')

@section('content')
    <div class="container-fluid">
        <h1 class="h3 mb-2 custom-text-heading">Add New Site</h1>

        <div class="card shadow mb-4">
            <div class="card-header py-3">
                <h6 class="m-0 font-weight-bold text-primary">Site Details</h6>
            </div>
            <div class="card-body">
                <form method="POST" action="{{ route('add-site') }}">
                    @csrf
                    
                    <div class="form-row">
                        <div class="form-group col-md-6">
                            <label><strong>Site Name / Title:</strong></label>
                            <input type="text" class="form-control" name="title" placeholder="e.g. Medina Towers" required>
                        </div>
                        <div class="form-group col-md-6">
                            <label><strong>Site Owner (Client):</strong></label>
                            <select class="form-control" name="user_id" required>
                                <option value="" disabled selected>-- Select Owner --</option>
                                @foreach($users as $user)
                                    <option value="{{ $user->id }}">{{ $user->name }} ({{ $user->email }})</option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group col-md-6">
                            <label><strong>Region:</strong></label>
                            <select class="form-control" name="region_id" required>
                                <option value="" disabled selected>-- Select Region --</option>
                                @foreach($regions as $region)
                                    <option value="{{ $region->id }}">{{ $region->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="form-group col-md-6">
                            <label><strong>Physical Address:</strong></label>
                            <input type="text" class="form-control" name="address" placeholder="e.g. 123 Main Street, Cape Town" required>
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group col-md-6">
                            <label><strong>Billing Type:</strong></label>
                            <select class="form-control" name="billing_type">
                                <option value="monthly">Monthly</option>
                                <option value="prepaid">Prepaid</option>
                            </select>
                        </div>
                        <div class="form-group col-md-6">
                            <label><strong>Contact Email (Optional):</strong></label>
                            <input type="email" class="form-control" name="email" placeholder="site@example.com">
                        </div>
                    </div>

                    <!-- Hidden fields for Lat/Lng set to 0 so the database doesn't complain -->
                    <input type="hidden" name="lat" value="0">
                    <input type="hidden" name="lng" value="0">

                    <button type="submit" class="btn btn-primary">Create Site</button>
                    <a href="{{ route('show-sites') }}" class="btn btn-secondary">Cancel</a>
                </form>
            </div>
        </div>
    </div>
@endsection
