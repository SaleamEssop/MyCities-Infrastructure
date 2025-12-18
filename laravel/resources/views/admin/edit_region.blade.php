@extends('admin.layouts.main')
@section('title', 'Edit Region')

@section('content')
<!-- Begin Page Content -->
<div class="container-fluid">

    <!-- Page Heading -->
    <h1 class="h3 mb-2 custom-text-heading">Edit Region</h1>

    <div class="cust-form-wrapper">
        <div class="row">
            <div class="col-md-6">
                <form method="POST" action="{{ route('edit-region') }}">
                    <input type="hidden" name="id" value="{{ $region->id }}" />
                    
                    <div class="form-group">
                        <label><strong>Region Name :</strong></label>
                        <input placeholder="Enter region name" type="text" class="form-control" name="name" value="{{ $region->name }}" required />
                    </div>
                    <div class="form-group">
                        <label><strong>Water Email :</strong></label>
                        <input placeholder="Enter Water Email" type="email" class="form-control" name="water_email" value="{{ $region->water_email }}" />
                    </div>
                    <div class="form-group">
                        <label><strong>Electricity Email :</strong></label>
                        <input placeholder="Enter Electricity Email" type="email" class="form-control" name="electricity_email" value="{{ $region->electricity_email }}" />
                    </div>
                    <hr>
                    
                    @csrf
                    <button type="submit" class="btn btn-warning">Update</button>
                    <a href="{{ route('regions-list') }}" class="btn btn-secondary">Cancel</a>
                </form>
            </div>
        </div>
    </div>
</div>
<!-- /.container-fluid -->
@endsection

@section('page-level-scripts')
<script type="text/javascript">
    $(document).ready(function() {
        // Any additional JS can go here
    });
</script>
@endsection
