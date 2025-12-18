@extends('admin.layouts.main')
@section('title', 'Add Tariff Template')

@section('content')
<div class="container-fluid">
    <!-- Page Heading -->
    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <h1 class="h3 mb-0 text-gray-800 custom-text-heading">Add Tariff Template</h1>
    </div>
    <p class="mb-4">Create a new billing template for a region.</p>
    
    @if(Session::has('alert-message'))
    <div class="alert {{ Session::get('alert-class', 'alert-info') }} alert-dismissible fade show" role="alert">
        {{ Session::get('alert-message') }}
        <button type="button" class="close" data-dismiss="alert" aria-label="Close">
            <span aria-hidden="true">&times;</span>
        </button>
    </div>
    @endif

    @if($errors->any())
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        <strong>Validation Errors:</strong>
        <ul class="mb-0">
            @foreach($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
        <button type="button" class="close" data-dismiss="alert" aria-label="Close">
            <span aria-hidden="true">&times;</span>
        </button>
    </div>
    @endif
    
    <div id="tariff-template-app" 
         data-props="{{ json_encode([
             'regions' => $regions,
             'csrfToken' => csrf_token(),
             'submitUrl' => route('tariff-template-store'),
             'cancelUrl' => route('tariff-template'),
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
