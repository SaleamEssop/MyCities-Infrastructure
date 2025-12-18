@extends('admin.layouts.main')
@section('title', 'Users')

@section('content')
    <!-- Begin Page Content -->
    <div class="container-fluid">

        <!-- Page Heading -->
        <h1 class="h3 mb-2 custom-text-heading">Create new User</h1>

        <div class="cust-form-wrapper">
            <div class="row">
                <div class="col-md-6">
                    <form method="POST" action="{{ route('add-user') }}">
                        <div class="form-group">
                            <label><strong>Name :</strong></label>
                            <input type="text" class="form-control" value="{{ old('name') }}" placeholder="Enter name" name="name" required>
                        </div>
                        <div class="form-group">
                            <label><strong>Contact Number :</strong></label>
                            <input type="text" class="form-control" value="{{ old('contact_number') }}" placeholder="Enter contact number" name="contact_number" required>
                        </div>
                        <div class="form-group">
                            <label><strong>Email :</strong></label>
                            <input type="email" name="email" value="{{ old('email') }}" class="form-control" id="exampleInputEmail1" required aria-describedby="emailHelp" placeholder="Enter email">
                        </div>
                        <div class="form-group">
                            <label><strong>Password :</strong></label>
                            <input type="password" name="password" class="form-control" required id="exampleInputPassword1" placeholder="Password">
                        </div>
                        @csrf
                        <button type="submit" class="btn btn-primary">Submit</button>
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
            $('#user-dataTable').dataTable();
        });
    </script>
@endsection
