@extends('admin.layouts.main')
@section('title', 'Regions')

@section('content')
<!-- Begin Page Content -->
<div class="container-fluid">

    <!-- Page Heading -->
    <h1 class="h3 mb-2 custom-text-heading">Add Region</h1>

    <div class="cust-form-wrapper">
        <div class="row">
            <div class="col-md-6">
                <form method="POST" action="{{ route('add-region') }}">
                    <div class="form-group">
                        <label><strong>Region Name :</strong></label>
                        <input placeholder="Enter region name" type="text" class="form-control" name="name" required />
                    </div>
                    <div class="form-group">
                        <label><strong>Water Email :</strong></label>
                        <input placeholder="Enter Water Email" type="email" class="form-control" name="water_email" />
                    </div>
                    <div class="form-group">
                        <label><strong>Electricity Email :</strong></label>
                        <input placeholder="Enter Electricity Email" type="email" class="form-control" name="electricity_email" />
                    </div>
                    <hr>
                    <!-- <h5>Cost Builder</h5>
                        <div class="cust-tabs">ed
                            <nav>
                                <div class="nav nav-tabs nav-fill" id="nav-tab" role="tablist">
                                    <a class="nav-item nav-link active" id="nav-home-tab" data-toggle="tab" href="#nav-home" role="tab" aria-controls="nav-home" aria-selected="true">Water</a>
                                    <a class="nav-item nav-link" id="nav-profile-tab" data-toggle="tab" href="#nav-profile" role="tab" aria-controls="nav-profile" aria-selected="false">Electricity</a>
                                </div>
                            </nav>
                            <div class="tab-content py-3 px-3 px-sm-0" id="nav-tabContent">
                                <div class="tab-pane fade show active" id="nav-home" role="tabpanel" aria-labelledby="nav-home-tab">
                                    <div class="water-cost-container"></div>
                                    <input type="hidden" name="water_type_id" value="{{ $data['water_id'] }}">
                                    <a href="#" id="add-cost" class="btn btn-sm btn-primary btn-circle"><i class="fa fa-plus"></i></a>
                                    <br>
                                    <br>
                                </div>
                                <div class="tab-pane fade" id="nav-profile" role="tabpanel" aria-labelledby="nav-profile-tab">
                                    Loading...
                                    <input type="hidden" name="elect_type_id" value="{{ $data['elect_id'] }}">
                                </div>
                            </div>
                        </div> -->
                    @csrf
                    <button type="submit" class="btn btn-warning">Create</button>
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

        // $("#add-cost").on("click", function () {

        //     var html = '<div class="row">\n' +
        //         '                            <div class="col-md-3">\n' +
        //         '                                <div class="form-group">\n' +
        //         '                                    <input class="form-control" type="number" placeholder="Min litres" name="water_cost_min[]" required/>\n' +
        //         '                                </div>\n' +
        //         '                            </div> <span> - </span>\n' +
        //         '                            <div class="col-md-3">\n' +
        //         '                                <div class="form-group">\n' +
        //         '                                    <input class="form-control" type="number" placeholder="Max litres" name="water_cost_max[]" required/>\n' +
        //         '                                </div>\n' +
        //         '                            </div> <span> = </span>\n' +
        //         '                            <div class="col-md-4">\n' +
        //         '                                <div class="form-group">\n' +
        //         '                                    <input class="form-control" type="number" placeholder="Charges" name="water_cost_amount[]" required/>\n' +
        //         '                                </div>\n' +
        //         '                            </div>\n' +
        //         '                            <div class="col-md-1">\n' +
        //         '                                <a href="#" data-id="" style="margin-top: 6px" class="btn btn-sm btn-circle btn-danger additional-cost-del-btn">\n' +
        //         '                                    <i class="fa fa-trash"></i>\n' +
        //         '                                </a>\n' +
        //         '                            </div>\n' +
        //         '                        </div>'

        //     $(".water-cost-container").append(html);
        // });

        $(document).on("click", '.additional-cost-del-btn', function() {
            $(this).parent().parent().remove();
        });
    });
</script>
@endsection