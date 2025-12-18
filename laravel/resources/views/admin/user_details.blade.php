@extends('admin.layouts.main')
@section('title', 'User Details')

@section('content')
    <!-- Begin Page Content -->
    <div class="container-fluid">

        <div class="container">
            <div class="card border-warning ">
                <div class="card-header" style="font-weight: bold;font-size: 20px">Sites</div>
                <div class="card-body">
                    <blockquote class="blockquote"></blockquote>
                    <div class="accordion" id="accorID">

                        @foreach($userDetails->sites as $site)
                            <div class="card" style="cursor: pointer">
                                <div class="card-header" id="header-{{ $site->id }}" data-toggle="collapse" data-target="#collapse-{{ $site->id }}" aria-expanded="true" aria-controls="collapse-{{ $site->id }}">
                                    <h5 class="mb-0">
                                        <button id="aciklamaTooltip" type="button" class="btn btn-light">Site: {{ $site->title }}</button>
                                    </h5>
                                </div>
                                <div class="collapse" id="collapse-{{ $site->id }}" aria-labelledby="header-{{ $site->id }}" data-parent="#accorID">
                                    <div class="card-body">

                                        <table class="table">
                                            <thead>
                                            <tr>
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
                                            <tr>
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
                                            </tbody>
                                        </table>
                                        <h6> >> Accounts under "{{ $site->title  }}" Site: </h6>
                                        <div class="accordion" id="accorAcc">
                                            @foreach($site->account as $account)
                                                <div class="card" style="cursor: pointer">
                                                    <div class="card-header" id="header-{{ $account->id }}" data-toggle="collapse" data-target="#collapse-{{ $account->id }}" aria-expanded="true" aria-controls="collapse-{{ $account->id }}">
                                                        <h5 class="mb-0">
                                                            <button id="aciklamaTooltip" type="button" class="btn btn-light">Account: {{ $account->account_name }}</button>
                                                        </h5>
                                                    </div>
                                                    <div class="collapse" id="collapse-{{ $account->id }}" aria-labelledby="header-{{ $account->id }}" data-parent="#accorAcc">
                                                        <div class="card-body">
                                                            <table class="table">
                                                                <thead>
                                                                <tr>
                                                                    <th>Site</th>
                                                                    <th>Account Name</th>
                                                                    <th>Account Number</th>
                                                                    <th>Optional Information</th>
                                                                    <th>Created Date</th>
                                                                    <th>Action</th>
                                                                </tr>
                                                                </thead>
                                                                <tbody>
                                                                <tr>
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
                                                                </tbody>
                                                            </table>

                                                            @if(count($account->meters) > 0)

                                                                <h6> >> Meters under "{{ $account->account_name  }}" Account: </h6>
                                                                <div class="accordion" id="accorMet">
                                                                    @foreach($account->meters as $meter)
                                                                        <div class="card" style="cursor: pointer">
                                                                            <div class="card-header" id="header-{{ $meter->id }}" data-toggle="collapse" data-target="#collapse-{{ $meter->id }}" aria-expanded="true" aria-controls="collapse-{{ $meter->id }}">
                                                                                <h5 class="mb-0">
                                                                                    <button id="aciklamaTooltip" type="button" class="btn btn-light">Meter: {{ $meter->meter_title }}</button>
                                                                                </h5>
                                                                            </div>
                                                                            <div class="collapse" id="collapse-{{ $meter->id }}" aria-labelledby="header-{{ $meter->id }}" data-parent="#accorMet">
                                                                                <div class="card-body">
                                                                                    <table class="table">
                                                                                        <thead>
                                                                                        <tr>
                                                                                            <th>Account Name</th>
                                                                                            <th>Meter Type</th>
                                                                                            <th>Meter Title</th>
                                                                                            <th>Meter Number</th>
                                                                                            <th>Created Date</th>
                                                                                            <th>Action</th>
                                                                                        </tr>
                                                                                        </thead>
                                                                                        <tbody>
                                                                                        <tr>
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
                                                                                        </tbody>
                                                                                    </table>

                                                                                    @if(count($meter->readings) > 0)

                                                                                        <h6> >> Readings under "{{ $meter->meter_title }}" Meter: </h6>
                                                                                        <div class="accordion" id="accorType">
                                                                                            @foreach($meter->readings as $meterReading)
                                                                                                <div class="card" style="cursor: pointer">
                                                                                                    <div class="card-header" id="header-{{ $meterReading->id }}" data-toggle="collapse" data-target="#collapse-{{ $meterReading->id }}" aria-expanded="true" aria-controls="collapse-{{ $meterReading->id }}">
                                                                                                        <h5 class="mb-0">
                                                                                                            <button id="aciklamaTooltip" type="button" class="btn btn-light">Reading: {{ $meterReading->reading_date }}</button>
                                                                                                        </h5>
                                                                                                    </div>
                                                                                                    <div class="collapse" id="collapse-{{ $meterReading->id }}" aria-labelledby="header-{{ $meterReading->id }}" data-parent="#accorType">
                                                                                                        <div class="card-body">
                                                                                                            <table class="table">
                                                                                                                <thead>
                                                                                                                <tr>
                                                                                                                    <th>Meter Title</th>
                                                                                                                    <th>Meter Reading</th>
                                                                                                                    <th>Meter Reading Date</th>
                                                                                                                    <th>Created Date</th>
                                                                                                                    <th>Action</th>
                                                                                                                </tr>
                                                                                                                </thead>
                                                                                                                <tbody>
                                                                                                                <tr>
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
                                                                                                                </tbody>
                                                                                                            </table>
                                                                                                        </div>
                                                                                                    </div>
                                                                                                </div>
                                                                                            @endforeach
                                                                                        </div>
                                                                                    @endif

                                                                                </div>
                                                                            </div>
                                                                        </div>
                                                                    @endforeach
                                                                </div>
                                                            @endif
                                                        </div>
                                                    </div>
                                                </div>
                                            @endforeach
                                        </div>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
                <button class="btn btn-warning dropdown-toggle" style="font-weight: bold" type="button" data-toggle="collapse2"
                        data-target="#collapseYetenekID">End</button>
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
        });
    </script>
@endsection
