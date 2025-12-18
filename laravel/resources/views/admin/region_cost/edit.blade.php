@extends('admin.layouts.main')
@section('title', 'Edit Cost Template')

@section('content')
<!-- Begin Page Content -->
<div class="container-fluid">

    <!-- Page Heading -->
    <div style="float: right;">
        <button type="button" class="btn btn-success" onclick="showCopyModal()">
            <i class="fas fa-copy"></i> Make a Copy
        </button>
    </div>
    <h1 class="h3 mb-2 custom-text-heading">Edit Cost Template</h1>
    
    <!-- Parent Hierarchy Display -->
    @if($region_cost->parent_id)
    <div class="alert alert-info mb-3">
        <strong><i class="fas fa-sitemap"></i> Tariff Hierarchy:</strong>
        <div class="mt-2" id="hierarchy-display">
            @php
                $hierarchy = [];
                $current = $region_cost;
                while ($current->parent_id) {
                    $parent = \App\Models\RegionsAccountTypeCost::find($current->parent_id);
                    if ($parent) {
                        array_unshift($hierarchy, $parent);
                        $current = $parent;
                    } else {
                        break;
                    }
                }
            @endphp
            @foreach($hierarchy as $index => $ancestor)
                <span class="badge badge-secondary">{{ $ancestor->template_name }}</span>
                <i class="fas fa-arrow-right mx-1"></i>
            @endforeach
            <span class="badge badge-primary">{{ $region_cost->template_name }}</span>
            <span class="text-muted ml-2">(Date Child)</span>
        </div>
    </div>
    @endif

    <!-- Copy Modal -->
    <div class="modal fade" id="copyModal" tabindex="-1" role="dialog" aria-labelledby="copyModalLabel" aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header bg-success text-white">
                    <h5 class="modal-title" id="copyModalLabel">
                        <i class="fas fa-copy"></i> Copy Tariff Template
                    </h5>
                    <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <form method="POST" action="{{ route('copy-region-cost') }}">
                    @csrf
                    <input type="hidden" name="id" value="{{ $region_cost->id }}" />
                    <div class="modal-body">
                        <p class="mb-3">How would you like to create the copy?</p>
                        
                        <div class="form-group">
                            <div class="custom-control custom-radio mb-3">
                                <input type="radio" id="copyIndependent" name="is_date_child" value="0" class="custom-control-input" checked>
                                <label class="custom-control-label" for="copyIndependent">
                                    <strong>Independent Copy</strong>
                                    <br><small class="text-muted">Create a standalone tariff with no relationship to the original.</small>
                                </label>
                            </div>
                            <div class="custom-control custom-radio">
                                <input type="radio" id="copyDateChild" name="is_date_child" value="1" class="custom-control-input">
                                <label class="custom-control-label" for="copyDateChild">
                                    <strong>Date Child</strong>
                                    <br><small class="text-muted">Create a child tariff linked to this parent. Use for date-range variants of the same tariff.</small>
                                </label>
                            </div>
                        </div>
                        
                        <div class="alert alert-warning mt-3" style="font-size: 13px;">
                            <i class="fas fa-info-circle"></i> 
                            <strong>Overlap Handling:</strong> When date ranges overlap between parent and child, the <strong>lower tariff rate</strong> will be applied.
                        </div>
                        
                        <!-- Show current hierarchy if this is already a child -->
                        @if($region_cost->parent_id)
                        <div class="alert alert-info mt-2" style="font-size: 13px;">
                            <i class="fas fa-sitemap"></i> 
                            <strong>Note:</strong> This tariff is already a date child. A new copy will extend the existing hierarchy.
                        </div>
                        @endif
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-success">
                            <i class="fas fa-copy"></i> Create Copy
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <div id="region-cost-app" 
         data-props="{{ json_encode([
             'regions' => $regions,
             'accountTypes' => $account_type,
             'csrfToken' => csrf_token(),
             'submitUrl' => route('update-region-cost'),
             'cancelUrl' => route('region-cost'),
             'getEmailUrl' => route('get-email-region', ['id' => '__ID__']),
             'existingData' => $region_cost
         ]) }}">
        <div class="text-center py-5">
            <div class="spinner-border" role="status">
                <span class="sr-only">Loading...</span>
            </div>
        </div>
    </div>
</div>
<!-- /.container-fluid -->
@endsection

@section('script')
<script src="{{ mix('js/app.js') }}"></script>
<script>
function showCopyModal() {
    $('#copyModal').modal('show');
}
</script>
@endsection
