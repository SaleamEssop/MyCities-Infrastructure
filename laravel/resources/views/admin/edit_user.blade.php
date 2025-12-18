@extends('admin.layouts.main')
@section('title', 'Users')

@section('content')
    <!-- Begin Page Content -->
    <div class="container-fluid">

        <!-- Page Heading -->
        <h1 class="h3 mb-2 custom-text-heading">Edit user</h1>

        <div class="cust-form-wrapper">
            <div class="row">
                <div class="col-md-6">
                    <form method="POST" action="{{ route('edit-user') }}">
                        <div class="form-group">
                            <label><strong>Name :</strong></label>
                            <input type="text" class="form-control" placeholder="Enter name" name="name" value="{{$user->name}}" required>
                        </div>
                        <div class="form-group">
                            <label><strong>Contact Number :</strong></label>
                            <input type="text" class="form-control" placeholder="Enter contact number" value="{{$user->contact_number}}" name="contact_number" required>
                        </div>
                        <div class="form-group">
                            <label><strong>Email :</strong></label>
                            <input type="email" name="email" value="{{$user->email}}" class="form-control" id="exampleInputEmail1" required aria-describedby="emailHelp" placeholder="Enter email">
                        </div>
                        <div class="form-group">
                            <label><strong>Password :</strong></label>
                            <input type="password" name="password" class="form-control" id="exampleInputPassword1" placeholder="Enter new Password">
                        </div>
                        @csrf
                        <input type="hidden" name="user_id" value="{{$user->id}}">
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
