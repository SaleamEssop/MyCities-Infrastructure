@extends('admin.layouts.main')
@section('title', 'User Accounts - Manager')

@section('content')
    <div class="container-fluid">
        <h1 class="h3 mb-2 custom-text-heading">User Accounts - Manager</h1>
        <p class="mb-4">Full management dashboard - view and edit users, accounts, meters, and readings all in one place.</p>

        <!-- Manager Dashboard Vue Component -->
        <div id="user-account-manager-app" data-props="{{ json_encode([
            'csrfToken' => csrf_token(),
            'users' => $users,
            'regions' => $regions,
            'meterTypes' => $meterTypes,
            'apiUrls' => [
                'search' => route('user-accounts.manager.search'),
                'getUserData' => route('user-accounts.manager.user', ['id' => '__ID__']),
                'updateUser' => route('user-accounts.manager.update-user', ['id' => '__ID__']),
                'updateAccount' => route('user-accounts.manager.update-account', ['id' => '__ID__']),
                'addMeter' => route('user-accounts.manager.add-meter'),
                'updateMeter' => route('user-accounts.manager.update-meter', ['id' => '__ID__']),
                'deleteMeter' => route('user-accounts.manager.delete-meter', ['id' => '__ID__']),
                'addReading' => route('user-accounts.manager.add-reading'),
                'getReadings' => route('user-accounts.manager.readings', ['meterId' => '__METER_ID__']),
                'deleteUser' => route('user-accounts.manager.delete-user', ['id' => '__ID__']),
                'getTariffTemplates' => route('user-accounts.manager.tariffs', ['regionId' => '__REGION_ID__']),
                'getAccountBilling' => route('user-accounts.manager.billing', ['accountId' => '__ACCOUNT_ID__']),
                'getBillingHistory' => route('user-accounts.manager.billing-history', ['accountId' => '__ACCOUNT_ID__']),
                'addPayment' => route('user-accounts.manager.add-payment'),
                'deletePayment' => route('user-accounts.manager.delete-payment', ['id' => '__ID__']),
            ]
        ]) }}">
        </div>
    </div>
@endsection

@section('script')
    <script src="{{ mix('js/app.js') }}"></script>
@endsection
