@extends('admin.layouts.main')
@section('title', 'Regions')

@section('content')
<!-- Begin Page Content -->
<div class="container-fluid">

    <!-- Page Heading -->
    <div class="cust-page-head">
        <h1 class="h3 mb-2 custom-text-heading">Regions</h1>
        <!-- <button type="button" class="btn btn-warning btn-circle" data-toggle="modal" data-target="#costModal">
                <i class="fas fa-plus-square"></i>
            </button> -->
    </div>

    <!-- DataTales Example -->
    <div class="card shadow mb-4">
        <div class="card-header py-3">
            <h6 class="m-0 font-weight-bold">List of regions added by the admin</h6>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered" id="sites-dataTable" width="100%" cellspacing="0">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Name</th>
                            <th>Water Email</th>
                            <th>Electricity Email</th>
                            <th>Created Date</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tfoot>
                        <tr>
                            <th>#</th>
                            <th>Name</th>
                            <th>Water Email</th>
                            <th>Electricity Email</th>
                            <th>Created Date</th>
                            <th>Actions</th>
                        </tr>
                    </tfoot>
                    <tbody>
                        @foreach($regions as $region)
                        <tr>
                            <td>{{ $loop->iteration }}</td>
                            <td>{{ $region->name ?? "" }}</td>
                            <td>{{ $region->water_email ?? "" }}</td>
                            <td>{{ $region->electricity_email ?? "" }}</td>
                            <td>{{ $region->created_at }}</td>
                            <td>
                                <a href="{{ url('admin/region/edit/'.$region->id) }}" class="btn btn-warning btn-circle">
                                    <i class="fas fa-edit"></i>
                                </a>
                                <a href="{{ url('admin/region/delete/'.$region->id) }}" onclick="return confirm('Are you sure you want to delete this region?')" class="btn btn-danger btn-circle">
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

<!-- Modal -->
<div class="modal fade" id="costModal" tabindex="-1" role="dialog" aria-labelledby="costModalLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="costModalLabel">Add New Region</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>

        </div>
    </div>
</div>
@endsection

@section('page-level-scripts')
<script type="text/javascript">
    $(document).ready(function() {
        $('#sites-dataTable').dataTable();
    });
</script>
@endsection