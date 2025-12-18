@extends('admin.layouts.main')
@section('title', 'Page Management')

@section('content')
<div class="container-fluid">
    <!-- Page Heading -->
    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <h1 class="h3 mb-0 text-gray-800">
            <i class="fas fa-file-alt mr-2"></i>Page Management
        </h1>
        <a href="{{ route('pages-create') }}" class="btn btn-primary">
            <i class="fas fa-plus mr-2"></i>Add New Page
        </a>
    </div>

    <!-- Info Cards -->
    <div class="row mb-4">
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-primary shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">Header Tabs</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">
                                {{ $pages->where('page_type', 'single')->whereNull('parent_id')->count() }}
                            </div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-columns fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-success shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-success text-uppercase mb-1">Menu Groups</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">
                                {{ $pages->where('page_type', 'parent')->count() }}
                            </div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-bars fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-info shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-info text-uppercase mb-1">Child Pages</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">
                                {{ $pages->sum(function($p) { return $p->children->count(); }) }}
                            </div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-sitemap fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-warning shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">Total Pages</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">
                                {{ $pages->count() + $pages->sum(function($p) { return $p->children->count(); }) }}
                            </div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-copy fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Legend -->
    <div class="alert alert-info mb-4">
        <strong><i class="fas fa-info-circle mr-2"></i>Page Types:</strong>
        <span class="badge badge-primary ml-3"><i class="fas fa-columns mr-1"></i> Single Page</span> = Appears as a tab in the app header
        <span class="badge badge-success ml-3"><i class="fas fa-bars mr-1"></i> Parent Page</span> = Appears in hamburger menu with expandable children
    </div>

    <!-- Pages List -->
    <div class="card shadow mb-4">
        <div class="card-header py-3">
            <h6 class="m-0 font-weight-bold text-primary">All Pages</h6>
        </div>
        <div class="card-body">
            @if($pages->count() > 0)
            <div class="table-responsive">
                <table class="table table-bordered" id="pagesTable">
                    <thead>
                        <tr>
                            <th width="50">#</th>
                            <th>Title</th>
                            <th width="120">Type</th>
                            <th width="100">Status</th>
                            <th width="100">In Nav</th>
                            <th width="80">Order</th>
                            <th width="180">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($pages as $page)
                        <tr class="{{ $page->is_active ? '' : 'table-secondary' }}">
                            <td>{{ $loop->iteration }}</td>
                            <td>
                                @if($page->icon)
                                    <i class="{{ $page->icon }} mr-2 text-primary"></i>
                                @endif
                                <strong>{{ $page->title }}</strong>
                                <br>
                                <small class="text-muted">/{{ $page->slug }}</small>
                            </td>
                            <td>
                                @if($page->page_type == 'single')
                                    <span class="badge badge-primary"><i class="fas fa-columns mr-1"></i> Header Tab</span>
                                @else
                                    <span class="badge badge-success"><i class="fas fa-bars mr-1"></i> Menu Group</span>
                                @endif
                            </td>
                            <td>
                                <span class="badge badge-{{ $page->is_active ? 'success' : 'secondary' }}">
                                    {{ $page->is_active ? 'Active' : 'Inactive' }}
                                </span>
                            </td>
                            <td>
                                <span class="badge badge-{{ $page->show_in_navigation ? 'info' : 'secondary' }}">
                                    {{ $page->show_in_navigation ? 'Yes' : 'No' }}
                                </span>
                            </td>
                            <td>{{ $page->sort_order }}</td>
                            <td>
                                <a href="{{ route('pages-edit', $page->id) }}" class="btn btn-sm btn-warning" title="Edit">
                                    <i class="fas fa-edit"></i>
                                </a>
                                <a href="{{ route('pages-preview', $page->id) }}" class="btn btn-sm btn-info" title="Preview" target="_blank">
                                    <i class="fas fa-eye"></i>
                                </a>
                                <a href="{{ route('pages-delete', $page->id) }}" 
                                   class="btn btn-sm btn-danger" 
                                   title="Delete"
                                   onclick="return confirm('Are you sure you want to delete this page?')">
                                    <i class="fas fa-trash"></i>
                                </a>
                            </td>
                        </tr>
                        
                        {{-- Child pages --}}
                        @foreach($page->children as $child)
                        <tr class="{{ $child->is_active ? 'bg-light' : 'table-secondary' }}">
                            <td></td>
                            <td class="pl-5">
                                <i class="fas fa-level-up-alt fa-rotate-90 mr-2 text-muted"></i>
                                @if($child->icon)
                                    <i class="{{ $child->icon }} mr-2 text-info"></i>
                                @endif
                                {{ $child->title }}
                                <br>
                                <small class="text-muted pl-4">/{{ $page->slug }}/{{ $child->slug }}</small>
                            </td>
                            <td>
                                <span class="badge badge-light"><i class="fas fa-file mr-1"></i> Child Page</span>
                            </td>
                            <td>
                                <span class="badge badge-{{ $child->is_active ? 'success' : 'secondary' }}">
                                    {{ $child->is_active ? 'Active' : 'Inactive' }}
                                </span>
                            </td>
                            <td>
                                <span class="badge badge-{{ $child->show_in_navigation ? 'info' : 'secondary' }}">
                                    {{ $child->show_in_navigation ? 'Yes' : 'No' }}
                                </span>
                            </td>
                            <td>{{ $child->sort_order }}</td>
                            <td>
                                <a href="{{ route('pages-edit', $child->id) }}" class="btn btn-sm btn-warning" title="Edit">
                                    <i class="fas fa-edit"></i>
                                </a>
                                <a href="{{ route('pages-preview', $child->id) }}" class="btn btn-sm btn-info" title="Preview" target="_blank">
                                    <i class="fas fa-eye"></i>
                                </a>
                                <a href="{{ route('pages-delete', $child->id) }}" 
                                   class="btn btn-sm btn-danger" 
                                   title="Delete"
                                   onclick="return confirm('Are you sure you want to delete this page?')">
                                    <i class="fas fa-trash"></i>
                                </a>
                            </td>
                        </tr>
                        @endforeach
                        @endforeach
                    </tbody>
                </table>
            </div>
            @else
            <div class="text-center py-5">
                <i class="fas fa-file-alt fa-4x text-gray-300 mb-3"></i>
                <h5 class="text-gray-600">No pages created yet</h5>
                <p class="text-muted">Start by creating your first page</p>
                <a href="{{ route('pages-create') }}" class="btn btn-primary mt-2">
                    <i class="fas fa-plus mr-2"></i>Create First Page
                </a>
            </div>
            @endif
        </div>
    </div>
</div>
@endsection

@section('page-level-scripts')
<script>
$(document).ready(function() {
    // Initialize DataTable but keep the custom structure
    // $('#pagesTable').DataTable();
});
</script>
@endsection






