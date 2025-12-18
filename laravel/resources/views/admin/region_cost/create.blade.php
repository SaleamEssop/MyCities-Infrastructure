@extends('admin.layouts.main')
@section('title', 'Add Cost Template')

@section('content')
<div class="container-fluid">
    <!-- Page Heading -->
    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <h1 class="h3 mb-0 text-gray-800 custom-text-heading">Add Cost Template</h1>
    </div>
    <p class="mb-4">Create a new billing template for a region and account type.</p>
    
    <div id="region-cost-app" 
         data-props="{{ json_encode([
             'regions' => $regions,
             'accountTypes' => $account_types,
             'csrfToken' => csrf_token(),
             'submitUrl' => route('region-cost-store'),
             'cancelUrl' => route('region-cost'),
             'getEmailUrl' => route('get-email-region', ['id' => '__ID__'])
         ]) }}">
        <div class="text-center py-5">
            <div class="spinner-border" role="status">
                <span class="sr-only">Loading...</span>
            </div>
        </div>
    </div>
</div>
@endsection

@section('script')
<script src="{{ mix('js/app.js') }}"></script>
@endsection
