@extends('admin.layouts.main')
@section('title', 'Meter Readings')

@section('content')
    <!-- Begin Page Content -->
    <div class="container-fluid">

        <!-- Page Heading -->
        <h1 class="h3 mb-2 custom-text-heading">Meter Readings</h1>

        <!-- DataTales Example -->
        <div class="card shadow mb-4">
            <div class="card-header py-3">
                <h6 class="m-0 font-weight-bold">List of meters readings for each meter</h6>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-bordered" id="acc-dataTable" width="100%" cellspacing="0">
                        <thead>
                        <tr>
                            <th>#</th>
                            <th>Reading Image</th>
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
                            <th>Reading Image</th>
                            <th>Meter Title</th>
                            <th>Meter Reading</th>
                            <th>Meter Reading Date</th>
                            <th>Created Date</th>
                            <th>Action</th>
                        </tr>
                        </tfoot>
                        <tbody>
                        @foreach($meterReadings as $meterReading)
                            <tr>
                                <td>{{ $loop->iteration }}</td>
                                <td>
                                    @if(!empty($meterReading->reading_image))
                                        <img src="{{ $meterReading->reading_image }}" width="100" height="100">
                                    @else
                                        -
                                    @endif
                                </td>
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
