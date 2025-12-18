@extends('admin.layouts.main')
@section('title', 'Meters')

@section('content')
    <!-- Begin Page Content -->
    <div class="container-fluid">

        <!-- Page Heading -->
        <h1 class="h3 mb-2 custom-text-heading">Create new Meter</h1>

        <div class="cust-form-wrapper">
            <div class="row">
                <div class="col-md-6">
                    <form method="POST" action="{{ route('add-meter') }}">
                        <div class="form-group">
                            <label><strong>Account :</strong></label>
                            <select class="form-control" id="exampleFormControlSelect1" name="account_id" required>
                                <option disabled selected value="">--Select Account--</option>
                                @foreach($accounts as $account)
                                    <option value="{{ $account->id }}">{{ $account->account_name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="form-group">
                            <label><strong>Meter Category :</strong></label>
                            <select class="form-control" id="exampleFormControlSelect1" name="meter_cat_id" required>
                                <option disabled selected value="">--Select Meter Category--</option>
                                @foreach($meterCats as $meterCat)
                                    <option value="{{ $meterCat->id }}">{{ $meterCat->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="form-group">
                            <label><strong>Meter Type :</strong></label>
                            <select class="form-control" id="exampleFormControlSelect1" name="meter_type_id" required>
                                <option disabled selected value="">--Select Meter Type--</option>
                                @foreach($meterTypes as $meterType)
                                    <option value="{{ $meterType->id }}">{{ $meterType->title }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="form-group">
                            <label><strong>Meter Title :</strong></label>
                            <input type="text" class="form-control" placeholder="Enter meter title" name="title" required>
                        </div>
                        <div class="form-group">
                            <label><strong>Meter Number :</strong></label>
                            <input type="text" class="form-control" placeholder="Enter meter number" name="number" required>
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
