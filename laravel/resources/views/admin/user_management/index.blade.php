@extends('admin.layouts.main')
@section('title', 'User Management')

@section('content')
    <div class="container-fluid">
        <h1 class="h3 mb-2 custom-text-heading">User Management</h1>
        <p class="mb-4">Complete user setup from one screen - manage users, sites, accounts, meters, and readings all in one place.</p>

        <!-- User Management Vue Component -->
        <div id="user-management-app" data-props="{{ json_encode([
            'csrfToken' => csrf_token(),
            'users' => $users,
            'regions' => $regions,
            'meterTypes' => $meterTypes,
            'apiUrls' => [
                'search' => route('user-management.search'),
                'store' => route('user-management.store'),
                'update' => route('user-management.update', ['id' => '__ID__']),
                'delete' => route('user-management.destroy', ['id' => '__ID__']),
                'getUserData' => route('user-management.show', ['id' => '__ID__']),
                'generateTestUser' => route('user-management.generate-test'),
                'deleteTestUsers' => route('user-management.delete-test'),
                'cloneUser' => route('user-management.clone', ['id' => '__ID__']),
            ]
        ]) }}">
        </div>
    </div>
@endsection

@section('script')
    <script src="{{ mix('js/app.js') }}"></script>
@endsection
