@extends('admin.layouts.main')
@section('title', 'Meter Readings')

@section('content')
<!-- Begin Page Content -->
<div class="container-fluid">

    <!-- Page Heading -->
    <div class="cust-page-head">
        <h1 class="h3 mb-2 custom-text-heading">Ads Categories</h1>
        <button type="button" class="btn btn-primary btn-circle" data-toggle="modal" data-target="#catModal">
            <i class="fas fa-plus-square"></i>
        </button>
    </div>

    <!-- DataTales Example -->
    <div class="card shadow mb-4">
        <div class="card-header py-3">
            <h6 class="m-0 font-weight-bold">List of categories for Ads</h6>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered" id="acc-dataTable" width="100%" cellspacing="0">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Category Name</th>
                            <th>Parent Category</th>
                            <th>Created Date</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tfoot>
                        <tr>
                            <th>#</th>
                            <th>Category Name</th>
                            <th>Parent Category</th>
                            <th>Created Date</th>
                            <th>Action</th>
                        </tr>
                    </tfoot>
                    <tbody>
                        @foreach($categories as $category)
                        <tr>
                            <td>{{ $loop->iteration }}</td>
                            <td>{{ $category->name }}</td>

                            @if(isset($category->child_display))
                            <td>{{ $category->child_display->name }}</td>
                            @else
                            <td> - </td>
                            @endif
                            <td>{{ $category->created_at }}</td>
                            <td>
                                <a href="#" id="updateCatBtn" data-id="{{ $category->id }}" data-title="{{ $category->name }}" data-parent-id="{{ $category->parent_id }}" data-toggle="modal" data-target="#updateModal" class="btn btn-warning btn-circle">
                                    <i class="fas fa-edit"></i>
                                </a>
                                <a href="{{ url('admin/ads-category/delete/'.$category->id) }}" onclick="return confirm('Are you sure you want to delete this category?')" class="btn btn-danger btn-circle">
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
<div class="modal fade" id="catModal" tabindex="-1" role="dialog" aria-labelledby="costModalLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="costModalLabel">Add new Category</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <form method="POST" action="{{ route('add-ads-category') }}">
                <div class="modal-body">
                    <div class="form-group">
                        <label><strong>Category Name:</strong></label>
                        <input placeholder="Enter new category name" type="text" class="form-control" name="category_name" required />
                    </div>
                    <div class="form-group">
                        <label><strong>Parent Category:</strong></label>
                        <select class="form-control" name="parent_id">
                            <option value=""></option>
                            @foreach($parent_categories as $key => $cat)
                            <option value="{{$key}}">{{$cat}}</option>
                            @endforeach
                        </select>
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
                <h5 class="modal-title" id="costModalLabel">Update Category</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <form method="POST" action="{{ route('edit-ads-category') }}">
                <div class="modal-body">
                    <div class="form-group">
                        <label><strong>Category Name:</strong></label>
                        <input id="upd-name" placeholder="Enter category name" type="text" class="form-control" name="category_name" required />
                    </div>
                    <div class="form-group">
                        <label><strong>Parent Category:</strong></label>
                        <select class="form-control" name="parent_id" id="edit_parent_id">
                            <option value=""></option>
                            @foreach($parent_categories as $key => $cat)
                            <option value="{{$key}}">{{$cat}}</option>
                            @endforeach
                        </select>
                    </div>
                    <input type="hidden" name="category_id" id="upd-id" />
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

        $(document).on("click", "#updateCatBtn", function() {
            var ID = $(this).data('id');
            var title = $(this).data('title');
            var parent_id = $(this).data('parent-id');
            console.log(parent_id);
            $('#edit_parent_id').val(parent_id);
            // Now add these values to the fields
            $("#upd-id").val(ID);
            $("#upd-name").val(title);
        });
    });
</script>
@endsection