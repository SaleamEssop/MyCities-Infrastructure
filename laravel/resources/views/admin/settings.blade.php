@extends('admin.layouts.main')
@section('title', 'Application Settings')

@section('content')
    <div class="container-fluid">
        <div class="cust-page-head mb-3">
            <h1 class="h3 mb-2 custom-text-heading">Application Settings</h1>
        </div>

        @if(Session::has('alert-message'))
            <div class="alert {{ Session::get('alert-class') }}">
                {{ Session::get('alert-message') }}
            </div>
        @endif

        <div class="card shadow mb-4">
            <div class="card-header py-3">
                <h6 class="m-0 font-weight-bold">Demo / Production Mode</h6>
            </div>
            <div class="card-body">
                <form method="POST" action="{{ route('settings.update') }}">
                    @csrf

                    <div class="form-group">
                        <label><strong>Application Mode</strong></label>
                        <div class="mt-2">
                            <div class="custom-control custom-radio mb-3">
                                <input type="radio" id="production_mode" name="demo_mode" value="0" 
                                       class="custom-control-input" {{ !($settings->demo_mode ?? true) ? 'checked' : '' }}>
                                <label class="custom-control-label" for="production_mode">
                                    <strong>Production Mode</strong> (Live Data Only)
                                    <br>
                                    <small class="text-muted">Hides all demo/test users, ads, and pages. Only shows real production content.</small>
                                </label>
                            </div>

                            <div class="custom-control custom-radio">
                                <input type="radio" id="demo_mode" name="demo_mode" value="1" 
                                       class="custom-control-input" {{ ($settings->demo_mode ?? true) ? 'checked' : '' }}>
                                <label class="custom-control-label" for="demo_mode">
                                    <strong>Demo Mode</strong> (Include Test Content)
                                    <br>
                                    <small class="text-muted">Shows all content including seed users, test ads, and demo pages. Useful for development and testing.</small>
                                </label>
                            </div>
                        </div>
                    </div>

                    <hr class="my-4">

                    <div class="alert alert-info">
                        <strong><i class="fas fa-info-circle"></i> What changes when Demo Mode is enabled:</strong>
                        <ul class="mb-0 mt-2">
                            <li>Demo/test users (like demo1@mycities.co.za) are visible in User Accounts</li>
                            <li>Seed advertisements and test images are shown in Ads management</li>
                            <li>Demo pages are visible in Pages management</li>
                            <li>All test data created by seeders is accessible</li>
                        </ul>
                    </div>

                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save"></i> Save Settings
                    </button>
                </form>
            </div>
        </div>
    </div>
@endsection
