@extends('admin.layouts.main')
@section('title', 'Create Page')

@section('content')
<div class="container-fluid">
    <!-- Page Heading -->
    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <h1 class="h3 mb-0 text-gray-800">
            <i class="fas fa-plus-circle mr-2"></i>Create New Page
        </h1>
        <a href="{{ route('pages-list') }}" class="btn btn-secondary">
            <i class="fas fa-arrow-left mr-2"></i>Back to Pages
        </a>
    </div>

    <div class="row">
        <div class="col-lg-8">
            <!-- Main Content Card -->
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">Page Content</h6>
                </div>
                <div class="card-body">
                    <form method="POST" action="{{ route('pages-store') }}" id="pageForm">
                        @csrf
                        
                        <div class="form-group">
                            <label for="title"><strong>Page Title <span class="text-danger">*</span></strong></label>
                            <input type="text" 
                                   class="form-control form-control-lg" 
                                   id="title" 
                                   name="title" 
                                   placeholder="Enter page title"
                                   required>
                        </div>

                        <div class="form-group">
                            <label for="slug"><strong>URL Slug</strong></label>
                            <div class="input-group">
                                <div class="input-group-prepend">
                                    <span class="input-group-text">/</span>
                                </div>
                                <input type="text" 
                                       class="form-control" 
                                       id="slug" 
                                       name="slug" 
                                       placeholder="auto-generated-from-title">
                            </div>
                            <small class="text-muted">Leave empty to auto-generate from title</small>
                        </div>

                        <div class="form-group">
                            <label for="page_content"><strong>Page Content</strong></label>
                            <textarea class="form-control" 
                                      id="page_content" 
                                      name="page_content" 
                                      rows="15"></textarea>
                        </div>

                        <hr>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="meta_title"><strong>SEO Title</strong></label>
                                    <input type="text" class="form-control" id="meta_title" name="meta_title" placeholder="SEO title (optional)">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="icon"><strong>Icon Class</strong></label>
                                    <input type="text" class="form-control" id="icon" name="icon" placeholder="e.g., fas fa-home">
                                    <small class="text-muted">FontAwesome icon class</small>
                                </div>
                            </div>
                        </div>

                        <div class="form-group">
                            <label for="meta_description"><strong>SEO Description</strong></label>
                            <textarea class="form-control" id="meta_description" name="meta_description" rows="2" placeholder="SEO description (optional)"></textarea>
                        </div>
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            <!-- Settings Card -->
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">Page Settings</h6>
                </div>
                <div class="card-body">
                    <div class="form-group">
                        <label><strong>Page Type <span class="text-danger">*</span></strong></label>
                        <div class="mt-2">
                            <div class="custom-control custom-radio mb-2">
                                <input type="radio" id="type_single" name="page_type" value="single" class="custom-control-input" checked>
                                <label class="custom-control-label" for="type_single">
                                    <i class="fas fa-columns text-primary mr-1"></i>
                                    <strong>Single Page (Header Tab)</strong>
                                    <br><small class="text-muted">Shows as a tab in the app header</small>
                                </label>
                            </div>
                            <div class="custom-control custom-radio mb-2">
                                <input type="radio" id="type_parent" name="page_type" value="parent" class="custom-control-input">
                                <label class="custom-control-label" for="type_parent">
                                    <i class="fas fa-bars text-success mr-1"></i>
                                    <strong>Parent Page (Menu Group)</strong>
                                    <br><small class="text-muted">Expandable menu with child pages</small>
                                </label>
                            </div>
                        </div>
                    </div>

                    <div class="form-group" id="parentSelectGroup" style="display: none;">
                        <label for="parent_id"><strong>Parent Page</strong></label>
                        <select class="form-control" id="parent_id" name="parent_id">
                            <option value="">-- No Parent (Root Level) --</option>
                            @foreach($parentPages as $parent)
                                <option value="{{ $parent->id }}">{{ $parent->title }}</option>
                            @endforeach
                        </select>
                        <small class="text-muted">Add as child to a menu group</small>
                    </div>

                    <div class="form-group">
                        <label for="sort_order"><strong>Sort Order</strong></label>
                        <input type="number" class="form-control" id="sort_order" name="sort_order" value="0" min="0">
                        <small class="text-muted">Lower numbers appear first</small>
                    </div>

                    <hr>

                    <div class="form-group">
                        <div class="custom-control custom-switch mb-2">
                            <input type="checkbox" class="custom-control-input" id="is_active" name="is_active" checked>
                            <label class="custom-control-label" for="is_active">
                                <strong>Active</strong>
                                <br><small class="text-muted">Page is visible</small>
                            </label>
                        </div>
                    </div>

                    <div class="form-group">
                        <div class="custom-control custom-switch">
                            <input type="checkbox" class="custom-control-input" id="show_in_navigation" name="show_in_navigation" checked>
                            <label class="custom-control-label" for="show_in_navigation">
                                <strong>Show in Navigation</strong>
                                <br><small class="text-muted">Display in app menu</small>
                            </label>
                        </div>
                    </div>

                    <hr>

                    <button type="submit" class="btn btn-primary btn-lg btn-block">
                        <i class="fas fa-save mr-2"></i>Create Page
                    </button>
                    </form>
                </div>
            </div>

            <!-- Help Card -->
            <div class="card shadow mb-4 border-left-info">
                <div class="card-body">
                    <h6 class="font-weight-bold text-info"><i class="fas fa-lightbulb mr-2"></i>Tips</h6>
                    <ul class="small mb-0">
                        <li><strong>Single Page</strong>: Creates a standalone tab in the app header</li>
                        <li><strong>Parent Page</strong>: Creates a menu group that can contain child pages</li>
                        <li>To add a child page, first create a Parent Page, then create a new page and select it as parent</li>
                        <li>Use the rich text editor to add formatted content, images, and links</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@section('script')
<script src="https://cdn.ckeditor.com/4.22.1/full/ckeditor.js"></script>
<script>
    CKEDITOR.replace('page_content', {
        height: 400,
        removeButtons: '',
        toolbarGroups: [
            { name: 'document', groups: [ 'mode', 'document', 'doctools' ] },
            { name: 'clipboard', groups: [ 'clipboard', 'undo' ] },
            { name: 'editing', groups: [ 'find', 'selection', 'spellchecker', 'editing' ] },
            { name: 'forms', groups: [ 'forms' ] },
            '/',
            { name: 'basicstyles', groups: [ 'basicstyles', 'cleanup' ] },
            { name: 'paragraph', groups: [ 'list', 'indent', 'blocks', 'align', 'bidi', 'paragraph' ] },
            { name: 'links', groups: [ 'links' ] },
            { name: 'insert', groups: [ 'insert' ] },
            '/',
            { name: 'styles', groups: [ 'styles' ] },
            { name: 'colors', groups: [ 'colors' ] },
            { name: 'tools', groups: [ 'tools' ] },
            { name: 'others', groups: [ 'others' ] },
            { name: 'about', groups: [ 'about' ] }
        ]
    });

    $('#title').on('blur', function() {
        if ($('#slug').val() === '') {
            var slug = $(this).val()
                .toLowerCase()
                .replace(/[^a-z0-9]+/g, '-')
                .replace(/(^-|-$)/g, '');
            $('#slug').val(slug);
        }
    });

    $('input[name="page_type"]').on('change', function() {
        if ($(this).val() === 'single') {
            $('#parentSelectGroup').slideDown();
        } else {
            $('#parentSelectGroup').slideUp();
            $('#parent_id').val('');
        }
    });
    
    $('input[name="page_type"]:checked').trigger('change');
</script>
@endsection
