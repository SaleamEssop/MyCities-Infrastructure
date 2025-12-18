@extends('admin.layouts.main')
@section('title', 'User Accounts')

@section('content')
    <!-- Begin Page Content -->
    <div class="container-fluid">

        <!-- Page Heading -->
        <h1 class="h3 mb-2 custom-text-heading">User Accounts</h1>
        <p class="mb-4">List of utility accounts linked to sites.</p>

        <!-- DataTales Example -->
        <div class="card shadow mb-4">
            <div class="card-header py-3">
                <h6 class="m-0 font-weight-bold">All User Accounts</h6>
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
                            <th>Billing Date</th>
                            <th>Optional Information</th>
                            <th>Created Date</th>
                            <th>Action</th>
                        </tr>
                        </thead>
                        <tfoot>
                        <tr>
                            <th>#</th>
                            <th>Site</th>
                            <th>Account Name</th>
                            <th>Account Number</th>
                            <th>Billing Date</th>
                            <th>Optional Information</th>
                            <th>Created Date</th>
                            <th>Action</th>
                        </tr>
                        </tfoot>
                        <tbody>
                        @foreach($accounts as $account)
                            <tr>
                                <td>{{ $loop->iteration }}</td>
                                <td>{{ $account->site->title ?? '-' }}</td>
                                <td>{{ $account->account_name }}</td>
                                <td>{{ $account->account_number }}</td>
                                <td>{{ $account->billing_date }}</td>
                                <td>{{ $account->optional_information }}</td>
                                <td>{{ $account->created_at }}</td>
                                <td>
                                    <a href="{{ url('admin/account/edit/'.$account->id) }}" class="btn btn-warning btn-circle">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                    <a href="{{ url('admin/account/delete/'.$account->id) }}" onclick="return confirm('Are you sure you want to delete this user account?')" class="btn btn-danger btn-circle">
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
