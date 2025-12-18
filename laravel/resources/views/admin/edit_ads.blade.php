@extends('admin.layouts.main')
@section('title', 'Meter Readings')

@section('content')
    <!-- Begin Page Content -->
    <div class="container-fluid">

        <!-- Page Heading -->
        <div class="cust-page-head">
            <h1 class="h3 mb-2 custom-text-heading">Ads</h1>
            <button type="button" class="btn btn-primary btn-circle" data-toggle="modal" data-target="#catModal">
                <i class="fas fa-plus-square"></i>
            </button>
        </div>

        <div class="card-header py-3">
            <h6 class="m-0 font-weight-bold text-primary">Edit</h6>
        </div>

        <div class="row">
            <div class="col-md-6">
                <form method="POST" action="{{ route('edit-ad') }}" enctype="multipart/form-data">
                    <img src="{{ $ad->image }}" width="200" height="200" />
                    <div class="form-group">
                        <label><strong>Ad Image :</strong></label>
                        <input type="file" name="ad_image" />
                    </div>
                    <div class="form-group">
                        <label><strong>Category :</strong></label>
                        <select class="form-control" name="ads_category_id">
                            <option disabled>--Select Category--</option>
                            @foreach($categories as $category)
                                <option {{ ($category->id == $ad->ads_category_id) ? 'selected' : '' }} value="{{ $category->id }}">{{ $category->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="form-group">
                        <label><strong>Name :</strong></label>
                        <input placeholder="Enter new ad name" type="text" value="{{ $ad->name }}" class="form-control" name="ad_name" required />
                    </div>
                    <div class="form-group">
                        <label><strong>Url :</strong></label>
                        <input placeholder="Enter new ad url" type="text" value="{{ $ad->url }}" class="form-control" name="ad_url" required />
                    </div>
                    <div class="form-group">
                        <label><strong>Price :</strong></label>
                        <input placeholder="Enter new ad price" type="number" value="{{ $ad->price }}" class="form-control" name="ad_price" required />
                    </div>
                    <div class="form-group">
                        <label><strong>Priority :</strong></label>
                        <input placeholder="Enter new ad priority" type="number" value="{{ $ad->priority }}" class="form-control" name="ad_priority" />
                    </div>
                    <div class="form-group">
                        <label><strong>Description :</strong></label>
                        <textarea class="form-control" name="description-editor">
                            {{ $ad->description }}
                        </textarea>
                    </div>
                    @csrf
                    <input type="hidden" name="ad_id" value="{{ $ad->id }}" />
                    <button type="submit" class="btn btn-primary">Save changes</button>
                </form>
            </div>
        </div>
    </div>
    <!-- /.container-fluid -->
@endsection

@section('page-level-scripts')
    <script src="//cdn.ckeditor.com/4.14.1/standard/ckeditor.js"></script>
    <script src="//cdn.ckeditor.com/4.14.1/standard/adapters/jquery.js"></script>
    <script type="text/javascript">
        $(document).ready(function() {
            $('#acc-dataTable').dataTable();
            //$('.ckeditor').ckeditor();

            CKEDITOR.replace('description-editor', {
                filebrowserUploadUrl: "{{route('ckeditor.image-upload', ['_token' => csrf_token() ])}}",
                filebrowserUploadMethod: 'form'
            });
        });
    </script>
@endsection
