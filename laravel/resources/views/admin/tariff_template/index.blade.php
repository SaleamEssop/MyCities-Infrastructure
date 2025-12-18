@extends('admin.layouts.main')
@section('title', 'Tariff Templates')

@section('content')
<div class="container-fluid">
    <h1 class="h3 mb-2 text-gray-800">Tariff Templates</h1>
    <p class="mb-4">Manage billing templates for each region.</p>

    @if(Session::has('alert-message'))
        <div class="alert {{ Session::get('alert-class', 'alert-info') }} alert-dismissible fade show">
            {{ Session::get('alert-message') }}
            <button type="button" class="close" data-dismiss="alert">&times;</button>
        </div>
    @endif

    <div class="card shadow mb-4">
        <div class="card-header py-3">
            <a href="{{ route('tariff-template-create') }}" class="btn btn-primary btn-sm float-right">Add New Template</a>
            <h6 class="m-0 font-weight-bold text-primary">Tariff Templates</h6>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered" width="100%" cellspacing="0">
                    <thead>
                        <tr>
                            <th>Template Name</th>
                            <th>Hierarchy</th>
                            <th>Region</th>
                            <th>Date Range</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @if(isset($costs) && $costs->count() > 0)
                            @foreach($costs as $cost)
                            <tr>
                                <td>
                                    {{ $cost->template_name ?? 'N/A' }}
                                    @if($cost->parent_id)
                                        <br><small class="text-muted"><i class="fas fa-level-up-alt fa-rotate-90"></i> Date Child</small>
                                    @endif
                                </td>
                                <td>
                                    @if($cost->parent_id)
                                        @php
                                            $hierarchy = [];
                                            $current = $cost;
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
                                        <div style="font-size: 12px;">
                                            @foreach($hierarchy as $ancestor)
                                                <span class="badge badge-secondary" title="{{ $ancestor->template_name }}">
                                                    {{ Str::limit($ancestor->template_name, 15) }}
                                                </span>
                                                <i class="fas fa-arrow-right text-muted" style="font-size: 10px;"></i>
                                            @endforeach
                                            <span class="badge badge-primary" title="{{ $cost->template_name }}">
                                                {{ Str::limit($cost->template_name, 15) }}
                                            </span>
                                        </div>
                                    @else
                                        @php
                                            $childCount = \App\Models\RegionsAccountTypeCost::where('parent_id', $cost->id)->count();
                                        @endphp
                                        @if($childCount > 0)
                                            <span class="badge badge-info">
                                                <i class="fas fa-sitemap"></i> Parent ({{ $childCount }} {{ Str::plural('child', $childCount) }})
                                            </span>
                                        @else
                                            <span class="text-muted">—</span>
                                        @endif
                                    @endif
                                </td>
                                <td>{{ $cost->region->name ?? 'Unknown' }}</td>
                                <td>{{ $cost->start_date ?? 'N/A' }} - {{ $cost->end_date ?? 'N/A' }}</td>
                                <td>
                                    @if($cost->is_active)
                                        <span class="badge badge-success">Active</span>
                                    @else
                                        <span class="badge badge-secondary">Inactive</span>
                                    @endif
                                </td>
                                <td>
                                    <a href="{{ route('tariff-template-edit', ['id' => $cost->id]) }}" class="btn btn-info btn-sm">Edit</a>
                                    <a href="{{ url('admin/tariff_template/delete/'.$cost->id) }}" class="btn btn-danger btn-sm" onclick="return confirm('Delete this template?')">Delete</a>
                                </td>
                            </tr>
                            @endforeach
                        @else
                            <tr><td colspan="6" class="text-center">No templates defined yet.</td></tr>
                        @endif
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection
