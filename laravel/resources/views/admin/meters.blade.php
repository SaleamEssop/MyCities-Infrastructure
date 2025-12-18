@extends('admin.layouts.main')
@section('title', 'Meters')

@section('content')
    <!-- Begin Page Content -->
    <div class="container-fluid">

        <!-- Page Heading -->
        <h1 class="h3 mb-2 custom-text-heading">Meters</h1>

        <!-- DataTales Example -->
        <div class="card shadow mb-4">
            <div class="card-header py-3">
                <h6 class="m-0 font-weight-bold">List of meters under accounts</h6>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-bordered" id="acc-dataTable" width="100%" cellspacing="0">
                        <thead>
                        <tr>
                            <th>#</th>
                            <th>Account Name</th>
                            <th>Meter Category</th>
                            <th>Meter Type</th>
                            <th>Meter Title</th>
                            <th>Meter Number</th>
                            <th>Created Date</th>
                            <th>Action</th>
                        </tr>
                        </thead>
                        <tfoot>
                        <tr>
                            <th>#</th>
                            <th>Account Name</th>
                            <th>Meter Category</th>
                            <th>Meter Type</th>
                            <th>Meter Title</th>
                            <th>Meter Number</th>
                            <th>Created Date</th>
                            <th>Action</th>
                        </tr>
                        </tfoot>
                        <tbody>
                        @foreach($meters as $meter)
                            <tr>
                                <td>{{ $loop->iteration }}</td>
                                <td>{{ $meter->account->account_name ?? '-' }}</td>
                                <td>{{ $meter->meterCategory->name ?? '-' }}</td>
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
            $('#acc-dataTable').dataTable();
        });
    </script>
@endsection
