@extends('admin.layouts.main')
@section('title', 'Costs')

@section('content')
    <!-- Begin Page Content -->
    <div class="container-fluid">

        <!-- Page Heading -->
        <div class="cust-page-head">
            <h1 class="h3 mb-2 custom-text-heading">Region Alarms</h1>
            <button type="button" class="btn btn-warning btn-circle" data-toggle="modal" data-target="#costModal">
                <i class="fas fa-plus-square"></i>
            </button>
        </div>

        <!-- DataTales Example -->
        <div class="card shadow mb-4">
            <div class="card-header py-3">
                <h6 class="m-0 font-weight-bold">List of alarms added for regions</h6>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-bordered" id="acc-dataTable" width="100%" cellspacing="0">
                        <thead>
                        <tr>
                            <th>#</th>
                            <th>Region</th>
                            <th>Date</th>
                            <th>Time</th>
                            <th>Message</th>
                            <th>Created Date</th>
                            <th>Action</th>
                        </tr>
                        </thead>
                        <tfoot>
                        <tr>
                            <th>#</th>
                            <th>Region</th>
                            <th>Date</th>
                            <th>Time</th>
                            <th>Message</th>
                            <th>Created Date</th>
                            <th>Action</th>
                        </tr>
                        </tfoot>
                        <tbody>
                        @foreach($regionAlarms as $regionAlarm)
                            <tr>
                                <td>{{ $loop->iteration }}</td>
                                <td>{{ $regionAlarm->region->name }}</td>
                                <td>{{ $regionAlarm->date }}</td>
                                <td>{{ $regionAlarm->time }}</td>
                                <td>{{ substr($regionAlarm->message, 0, 18) }}...</td>
                                <td>{{ $regionAlarm->created_at }}</td>
                                <td>
                                    <a href="#" id="updateCostBtn" data-id="{{ $regionAlarm->id }}" data-region="{{ $regionAlarm->region_id }}"
                                       data-date="{{ $regionAlarm->date }}" data-time="{{ $regionAlarm->time }}" data-msg="{{ $regionAlarm->message }}" data-toggle="modal" data-target="#updateModal" class="btn btn-warning btn-circle">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                    <a href="{{ url('admin/alarm/delete/'.$regionAlarm->id) }}" onclick="return confirm('Are you sure you want to delete this alarm?')" class="btn btn-danger btn-circle">
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

    <!-- Modal -->
    <div class="modal fade" id="costModal" tabindex="-1" role="dialog" aria-labelledby="costModalLabel" aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="costModalLabel">Add Alarm</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <form method="POST" action="{{ route('add-alarm') }}">
                    <div class="modal-body">
                        <div class="form-group">
                            <label><strong>Region:</strong></label>
                            <select class="form-control" name="region_id" required>
                                <option disabled>--Select Region--</option>
                                @foreach($regions as $region)
                                    <option value="{{ $region->id }}">{{ $region->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="form-group">
                            <label><strong>Date:</strong></label>
                            <input placeholder="Enter alarm date" type="date" class="form-control" name="alarm_date" required />
                        </div>
                        <div class="form-group">
                            <label><strong>Time:</strong></label>
                            <input placeholder="Enter alarm time" type="time" class="form-control" name="alarm_time" required />
                        </div>
                        <div class="form-group">
                            <label><strong>Message:</strong></label>
                            <textarea class="form-control" name="alarm_message" placeholder="Enter alarm message" rows="10" required></textarea>
                        </div>
                        @csrf
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                        <button type="submit" class="btn btn-primary">Save changes</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="modal fade" id="updateModal" tabindex="-1" role="dialog" aria-labelledby="costModalLabel" aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="costModalLabel">Update Alarm</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <form method="POST" action="{{ route('edit-alarm') }}">
                    <div class="modal-body">
                        <div class="form-group">
                            <label><strong>Region:</strong></label>
                            <select class="form-control" name="region_id" id="upd-region" required>
                                <option selected disabled>--Select Region--</option>
                                @foreach($regions as $region)
                                    <option value="{{ $region->id }}">{{ $region->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="form-group">
                            <label><strong>Date:</strong></label>
                            <input placeholder="Enter alarm date" id="upd-date" type="date" class="form-control" name="alarm_date" required />
                        </div>
                        <div class="form-group">
                            <label><strong>Time:</strong></label>
                            <input placeholder="Enter alarm time" id="upd-time" type="time" class="form-control" name="alarm_time" required />
                        </div>
                        <div class="form-group">
                            <label><strong>Message:</strong></label>
                            <textarea class="form-control" id="upd-msg" name="alarm_message" placeholder="Enter alarm message" rows="10" required></textarea>
                        </div>
                        @csrf
                        <input type="hidden" name="alarm_id" id="upd-id" />
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                        <button type="submit" class="btn btn-primary">Save changes</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection

@section('page-level-scripts')
    <script type="text/javascript">
        $(document).ready(function() {
            $('#acc-dataTable').dataTable();

            $(document).on("click", "#updateCostBtn", function() {
                var ID = $(this).data('id');
                var regionID = $(this).data('region');
                var alarmDate = $(this).data('date');
                var alarmTime = $(this).data('time');
                var alarmMsg = $(this).data('msg');

                // Now add these values to the fields
                $("#upd-id").val(ID);
                $("#upd-region").val(regionID).change();
                $('#upd-region option:eq(3)').prop('selected', true)
                $("#upd-date").val(alarmDate);
                $("#upd-time").val(alarmTime);
                $("#upd-msg").val(alarmMsg);
            });
        });
    </script>

@endsection
