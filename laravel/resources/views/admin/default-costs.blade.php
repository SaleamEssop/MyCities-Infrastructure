@extends('admin.layouts.main')
@section('title', 'Costs')

@section('content')
    <!-- Begin Page Content -->
    <div class="container-fluid">

        <!-- Page Heading -->
        <div class="cust-page-head">
            <h1 class="h3 mb-2 custom-text-heading">Default Costs</h1>
            <button type="button" class="btn btn-warning btn-circle" data-toggle="modal" data-target="#costModal">
                <i class="fas fa-plus-square"></i>
            </button>
        </div>

        <!-- DataTales Example -->
        <div class="card shadow mb-4">
            <div class="card-header py-3">
                <h6 class="m-0 font-weight-bold">List of default costs added by the admin</h6>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-bordered" id="acc-dataTable" width="100%" cellspacing="0">
                        <thead>
                        <tr>
                            <th>#</th>
                            <th>Default Cost Title</th>
                            <th>Default Cost Value</th>
                            <th>Created Date</th>
                            <th>Action</th>
                        </tr>
                        </thead>
                        <tfoot>
                        <tr>
                            <th>#</th>
                            <th>Default Cost Title</th>
                            <th>Default Cost Value</th>
                            <th>Created Date</th>
                            <th>Action</th>
                        </tr>
                        </tfoot>
                        <tbody>
                        @foreach($defaultCosts as $defaultCost)
                            <tr>
                                <td>{{ $loop->iteration }}</td>
                                <td>{{ $defaultCost->title }}</td>
                                <td>{{ $defaultCost->value ?? '-' }}</td>
                                <td>{{ $defaultCost->created_at }}</td>
                                <td>
                                    <a href="#" id="updateCostBtn" data-id="{{ $defaultCost->id }}" data-title="{{ $defaultCost->title }}" data-value="{{ $defaultCost->value }}" data-toggle="modal" data-target="#updateModal" class="btn btn-warning btn-circle">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                    <a href="{{ url('admin/default-cost/delete/'.$defaultCost->id) }}" onclick="return confirm('Are you sure you want to delete this cost?')" class="btn btn-danger btn-circle">
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
                    <h5 class="modal-title" id="costModalLabel">Default Cost</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <form method="POST" action="{{ route('add-default-cost') }}">
                    <div class="modal-body">
                        <div class="form-group">
                            <label><strong>Title:</strong></label>
                            <input placeholder="Enter default cost title" type="text" class="form-control" name="cost_name" required />
                        </div>
                        <div class="form-group">
                            <label><strong>Value:</strong></label>
                            <input placeholder="Enter default cost value(optional)" type="text" class="form-control" name="cost_value" />
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
                    <h5 class="modal-title" id="costModalLabel">Update Default Cost</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <form method="POST" action="{{ route('edit-default-cost') }}">
                    <div class="modal-body">
                        <div class="form-group">
                            <label><strong>Title:</strong></label>
                            <input id="upd-name" placeholder="Enter default cost title" type="text" class="form-control" name="cost_name" required />
                        </div>
                        <div class="form-group">
                            <label><strong>Value:</strong></label>
                            <input id="upd-value" placeholder="Enter default cost value(optional)" type="text" class="form-control" name="cost_value" />
                        </div>
                        <input type="hidden" name="default_cost_id" id="upd-id" />
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
@endsection

@section('page-level-scripts')
    <script type="text/javascript">
        $(document).ready(function() {
            $('#acc-dataTable').dataTable();

            $(document).on("click", "#updateCostBtn", function() {
                var ID = $(this).data('id');
                var title = $(this).data('title');
                var defaultValue = $(this).data('value');

                // Now add these values to the fields
                $("#upd-id").val(ID);
                $("#upd-name").val(title);
                $("#upd-value").val(defaultValue);
            });
        });


    </script>

@endsection
