@extends('admin.layouts.main')
@section('title', 'Sites')

@section('content')
    <!-- Begin Page Content -->
    <div class="container-fluid">

        <!-- Page Heading -->
        <h1 class="h3 mb-2 custom-text-heading">Sites</h1>

        <!-- DataTales Example -->
        <div class="card shadow mb-4">
            <div class="card-header py-3">
                <h6 class="m-0 font-weight-bold">List of sites added by users</h6>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-bordered" id="sites-dataTable" width="100%" cellspacing="0">
                        <thead>
                        <tr>
                            <th>#</th>
                            <th>Region</th>
                            <th>User</th>
                            <th>Title</th>
                            <th>Lat</th>
                            <th>Lng</th>
                            <th>Address</th>
                            <th>Email</th>
                            <th>Created Date</th>
                            <th>Actions</th>
                        </tr>
                        </thead>
                        <tbody>
                        @foreach($userDetails->sites as $site)
                            <tr>
                                <td>{{ $loop->iteration }}</td>
                                <td>{{ $site->region->name ?? '-' }}</td>
                                <td>{{ $site->user->name }}</td>
                                <td>{{ $site->title }}</td>
                                <td>{{ $site->lat }}</td>
                                <td>{{ $site->lng }}</td>
                                <td>{{ $site->address }}</td>
                                <td>{{ $site->email }}</td>
                                <td>{{ $site->created_at }}</td>
                                <td>
                                    <a href="{{ url('admin/site/edit/'.$site->id) }}" class="btn btn-warning btn-circle">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                    <a href="{{ url('admin/site/delete/'.$site->id) }}" onclick="return confirm('Are you sure you want to delete this site?')" class="btn btn-danger btn-circle">
                                        <i class="fas fa-trash"></i>
                                    </a>
                                </td>
                            </tr>
                        @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>


        <h1 class="h3 mb-2 custom-text-heading">Accounts</h1>

        <!-- DataTales Example -->
        <div class="card shadow mb-4">
            <div class="card-header py-3">
                <h6 class="m-0 font-weight-bold">List of account added under sites</h6>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-bordered" id="acc-dataTable" width="100%" cellspacing="0">
                        <thead>
                        <tr>
                            <th>#</th>
                            <th>Site</th>
                            <th>Account Name</th>
                            <th>Account Number</th>
                            <th>Optional Information</th>
                            <th>Created Date</th>
                            <th>Action</th>
                        </tr>
                        </thead>
                        <tbody>
                        @foreach($userDetails->sites as $site)
                            @foreach($site->account as $account)
                                <tr>
                                    <td>{{ $loop->iteration }}</td>
                                    <td>{{ $account->site->title }}</td>
                                    <td>{{ $account->account_name }}</td>
                                    <td>{{ $account->account_number }}</td>
                                    <td>{{ $account->optional_information }}</td>
                                    <td>{{ $account->created_at }}</td>
                                    <td>
                                        <a href="{{ url('admin/account/edit/'.$account->id) }}" class="btn btn-warning btn-circle">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                        <a href="{{ url('admin/account/delete/'.$account->id) }}" onclick="return confirm('Are you sure you want to delete this account?')" class="btn btn-danger btn-circle">
                                            <i class="fas fa-trash"></i>
                                        </a>
                                    </td>
                                </tr>
                            @endforeach
                        @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <h1 class="h3 mb-2 custom-text-heading">Meters</h1>

        <!-- DataTales Example -->
        <div class="card shadow mb-4">
            <div class="card-header py-3">
                <h6 class="m-0 font-weight-bold">List of meters added under accounts</h6>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-bordered" id="meter-dataTable" width="100%" cellspacing="0">
                        <thead>
                        <tr>
                            <th>#</th>
                            <th>Account Name</th>
                            <th>Meter Type</th>
                            <th>Meter Title</th>
                            <th>Meter Number</th>
                            <th>Created Date</th>
                            <th>Action</th>
                        </tr>
                        </thead>
                        <tbody>
                        @foreach($userDetails->sites as $site)
                            @foreach($site->account as $account)
                                @foreach($account->meters as $meter)
                                    <tr>
                                        <td>{{ $loop->iteration }}</td>
                                        <td>{{ $meter->account->account_name ?? '-' }}</td>
                                        <td>{{ $meter->meterTypes->title }}</td>
                                        <td>{{ $meter->meter_title }}</td>
                                        <td>{{ $meter->meter_number }}</td>
                                        <td>{{ $meter->created_at }}</td>
                                        <td>
                                            <a href="{{ url('admin/meter/edit/'.$meter->id) }}" class="btn btn-warning btn-circle">
                                                <i class="fas fa-edit"></i>
                                            </a>
                                            <a href="{{ url('admin/meter/delete/'.$meter->id) }}" onclick="return confirm('Are you sure you want to delete this meter?')" class="btn btn-danger btn-circle">
                                                <i class="fas fa-trash"></i>
                                            </a>
                                        </td>
                                    </tr>
                                @endforeach
                            @endforeach
                        @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <h1 class="h3 mb-2 custom-text-heading">Meter Readings</h1>

        <!-- DataTales Example -->
        <div class="card shadow mb-4">
            <div class="card-header py-3">
                <h6 class="m-0 font-weight-bold">List of readings recorded under meters</h6>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-bordered" id="reading-dataTable" width="100%" cellspacing="0">
                        <thead>
                        <tr>
                            <th>#</th>
                            <th>Meter Title</th>
                            <th>Meter Reading</th>
                            <th>Meter Reading Date</th>
                            <th>Created Date</th>
                            <th>Action</th>
                        </tr>
                        </thead>
                        <tfoot>
                        <tr>
                            <th>#</th>
                            <th>Meter Title</th>
                            <th>Meter Reading</th>
                            <th>Meter Reading Date</th>
                            <th>Created Date</th>
                            <th>Action</th>
                        </tr>
                        </tfoot>
                        <tbody>
                        @foreach($userDetails->sites as $site)
                            @foreach($site->account as $account)
                                @foreach($account->meters as $meter)
                                    @foreach($meter->readings as $meterReading)
                                        <tr>
                                            <td>{{ $loop->iteration }}</td>
                                            <td>{{ $meterReading->meter->meter_title ?? '-' }}</td>
                                            <td>{{ $meterReading->reading_value }}</td>
                                            <td>{{ $meterReading->reading_date }}</td>
                                            <td>{{ $meterReading->created_at }}</td>
                                            <td>
                                                <a href="{{ url('admin/meter-reading/edit/'.$meterReading->id) }}" class="btn btn-warning btn-circle">
                                                    <i class="fas fa-edit"></i>
                                                </a>
                                                <a href="{{ url('admin/meter-reading/delete/'.$meterReading->id) }}" onclick="return confirm('Are you sure you want to delete this meter reading?')" class="btn btn-danger btn-circle">
                                                    <i class="fas fa-trash"></i>
                                                </a>
                                            </td>
                                        </tr>
                                    @endforeach
                                @endforeach
                            @endforeach
                        @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
    <!-- /.container-fluid -->
@endsection

@section('page-level-scripts')
    <script type="text/javascript">
        $(document).ready(function() {
            $('#sites-dataTable').dataTable();
            $('#acc-dataTable').dataTable();
            $('#meter-dataTable').dataTable();
            $('#reading-dataTable').dataTable();
        });
    </script>
@endsection
