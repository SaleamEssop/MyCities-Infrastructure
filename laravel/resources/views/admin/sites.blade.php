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
                        <tfoot>
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
                        </tfoot>
                        <tbody>
                        @foreach($sites as $site)
                            <tr>
                                <td>{{ $loop->iteration }}</td>
                                <td>{{ $site->region->name ?? '-' }}</td>
                                <td>{{ $site->user->name ?? '-' }}</td>
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
                                    <a href="{{ url('admin/site/delete/'.$site->id) }}" onclick="return confirm('Are you sure you want to delete this site? Please note all of the data under this site will also be deleted.')" class="btn btn-danger btn-circle">
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
            $('#sites-dataTable').dataTable();
        });
    </script>
@endsection
