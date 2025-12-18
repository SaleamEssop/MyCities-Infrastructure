@extends('admin.layouts.main')
@section('title', 'Users')

@section('content')
    <!-- Begin Page Content -->
    <div class="container-fluid">

        <!-- Page Heading -->
        <h1 class="h3 mb-2 custom-text-heading">Users</h1>

        <!-- DataTales Example -->
        <div class="card shadow mb-4">
            <div class="card-header py-3">
                <h6 class="m-0 font-weight-bold">List of Users</h6>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-bordered" id="user-dataTable" width="100%" cellspacing="0">
                        <thead>
                        <tr>
                            <th>#</th>
                            <th>Name</th>
                            <th>Email</th>
                            <th>Contact number</th>
                            <th>Is Admin</th>
                            <th>Created Date</th>
                            <th>Actions</th>
                        </tr>
                        </thead>
                        <tfoot>
                        <tr>
                            <th>#</th>
                            <th>Name</th>
                            <th>Email</th>
                            <th>Contact number</th>
                            <th>Is Admin</th>
                            <th>Created Date</th>
                            <th>Actions</th>
                        </tr>
                        </tfoot>
                        <tbody>
                        @foreach($users as $user)
                            <tr>
                                <td>{{ $loop->iteration }}</td>
                                <td>{{ $user->name }}</td>
                                <td>{{ $user->email }}</td>
                                <td>{{ $user->contact_number }}</td>
                                <td>{{ $user->is_admin }}</td>
                                <td>{{ $user->created_at }}</td>
                                <td>
                                    <a href="{{ url('admin/user/details-v2/'.$user->id) }}" class="btn btn-success btn-circle">
                                        <i class="fas fa-book"></i>
                                    </a>
                                    <a href="{{ url('admin/user/details/'.$user->id) }}" class="btn btn-primary btn-circle">
                                        <i class="fas fa-book"></i>
                                    </a>
                                    <a href="{{ url('admin/user/edit/'.$user->id) }}" class="btn btn-warning btn-circle">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                    <a href="{{ url('admin/user/delete/'.$user->id) }}" onclick="return confirm('Are you sure you want to delete this user? Please note that all data under this user will also get deleted.')" class="btn btn-danger btn-circle">
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
            $('#user-dataTable').dataTable();
        });
    </script>
@endsection
