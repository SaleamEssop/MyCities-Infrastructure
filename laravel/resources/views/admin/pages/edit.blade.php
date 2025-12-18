@extends('admin.layouts.main')
@section('title', 'Edit Page')

@section('content')
<div class="container-fluid">
    <!-- Page Heading -->
    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <h1 class="h3 mb-0 text-gray-800">
            <i class="fas fa-edit mr-2"></i>Edit Page: {{ $page->title }}
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
                    <form method="POST" action="{{ route('pages-update') }}" id="pageForm">
                        @csrf
                        <input type="hidden" name="page_id" value="{{ $page->id }}">
                        
                        <div class="form-group">
                            <label for="title"><strong>Page Title <span class="text-danger">*</span></strong></label>
                            <input type="text" 
                                   class="form-control form-control-lg" 
                                   id="title" 
                                   name="title" 
                                   value="{{ $page->title }}"
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
                                       value="{{ $page->slug }}"
                                       placeholder="url-slug">
                            </div>
                            <small class="text-muted">Current URL: <code>{{ $page->url }}</code></small>
                        </div>

                        <div class="form-group">
                            <label for="page_content"><strong>Page Content</strong></label>
                            <textarea class="form-control" 
                                      id="page_content" 
                                      name="page_content" 
                                      rows="15">{!! $page->content !!}</textarea>
                        </div>

                        <hr>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="meta_title"><strong>SEO Title</strong></label>
                                    <input type="text" class="form-control" id="meta_title" name="meta_title" value="{{ $page->meta_title }}" placeholder="SEO title (optional)">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="icon"><strong>Icon Class</strong></label>
                                    <input type="text" class="form-control" id="icon" name="icon" value="{{ $page->icon }}" placeholder="e.g., fas fa-home">
                                    <small class="text-muted">FontAwesome icon class</small>
                                </div>
                            </div>
                        </div>

                        <div class="form-group">
                            <label for="meta_description"><strong>SEO Description</strong></label>
                            <textarea class="form-control" id="meta_description" name="meta_description" rows="2" placeholder="SEO description (optional)">{{ $page->meta_description }}</textarea>
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
                                <input type="radio" id="type_single" name="page_type" value="single" class="custom-control-input" {{ $page->page_type == 'single' ? 'checked' : '' }}>
                                <label class="custom-control-label" for="type_single">
                                    <i class="fas fa-columns text-primary mr-1"></i>
                                    <strong>Single Page (Header Tab)</strong>
                                    <br><small class="text-muted">Shows as a tab in the app header</small>
                                </label>
                            </div>
                            <div class="custom-control custom-radio mb-2">
                                <input type="radio" id="type_parent" name="page_type" value="parent" class="custom-control-input" {{ $page->page_type == 'parent' ? 'checked' : '' }}>
                                <label class="custom-control-label" for="type_parent">
                                    <i class="fas fa-bars text-success mr-1"></i>
                                    <strong>Parent Page (Menu Group)</strong>
                                    <br><small class="text-muted">Expandable menu with child pages</small>
                                </label>
                            </div>
                        </div>
                    </div>

                    @if($page->page_type != 'parent' || !$page->hasChildren())
                    <div class="form-group" id="parentSelectGroup" style="{{ $page->page_type == 'parent' ? 'display:none;' : '' }}">
                        <label for="parent_id"><strong>Parent Page</strong></label>
                        <select class="form-control" id="parent_id" name="parent_id">
                            <option value="">-- No Parent (Root Level) --</option>
                            @foreach($parentPages as $parent)
                                <option value="{{ $parent->id }}" {{ $page->parent_id == $parent->id ? 'selected' : '' }}>{{ $parent->title }}</option>
                            @endforeach
                        </select>
                        <small class="text-muted">Add as child to a menu group</small>
                    </div>
                    @endif

                    <div class="form-group">
                        <label for="sort_order"><strong>Sort Order</strong></label>
                        <input type="number" class="form-control" id="sort_order" name="sort_order" value="{{ $page->sort_order }}" min="0">
                        <small class="text-muted">Lower numbers appear first</small>
                    </div>

                    <hr>

                    <div class="form-group">
                        <div class="custom-control custom-switch mb-2">
                            <input type="checkbox" class="custom-control-input" id="is_active" name="is_active" {{ $page->is_active ? 'checked' : '' }}>
                            <label class="custom-control-label" for="is_active">
                                <strong>Active</strong>
                                <br><small class="text-muted">Page is visible</small>
                            </label>
                        </div>
                    </div>

                    <div class="form-group">
                        <div class="custom-control custom-switch">
                            <input type="checkbox" class="custom-control-input" id="show_in_navigation" name="show_in_navigation" {{ $page->show_in_navigation ? 'checked' : '' }}>
                            <label class="custom-control-label" for="show_in_navigation">
                                <strong>Show in Navigation</strong>
                                <br><small class="text-muted">Display in app menu</small>
                            </label>
                        </div>
                    </div>

                    <hr>

                    <button type="submit" class="btn btn-primary btn-lg btn-block">
                        <i class="fas fa-save mr-2"></i>Update Page
                    </button>
                    </form>

                    <a href="{{ route('pages-preview', $page->id) }}" class="btn btn-info btn-block mt-2" target="_blank">
                        <i class="fas fa-eye mr-2"></i>Preview Page
                    </a>
                </div>
            </div>

            <!-- Page Info Card -->
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-secondary">Page Info</h6>
                </div>
                <div class="card-body small">
                    <p><strong>ID:</strong> {{ $page->id }}</p>
                    <p><strong>Created:</strong> {{ $page->created_at->format('M d, Y H:i') }}</p>
                    <p><strong>Updated:</strong> {{ $page->updated_at->format('M d, Y H:i') }}</p>
                    @if($page->hasChildren())
                    <p><strong>Child Pages:</strong> {{ $page->children->count() }}</p>
                    @endif
                    @if($page->parent)
                    <p><strong>Parent:</strong> {{ $page->parent->title }}</p>
                    @endif
                </div>
            </div>

            @if($page->page_type == 'parent' && $page->hasChildren())
            <!-- Children List -->
            <div class="card shadow mb-4 border-left-success">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-success">
                        <i class="fas fa-sitemap mr-2"></i>Child Pages ({{ $page->children->count() }})
                    </h6>
                </div>
                <div class="card-body">
                    <ul class="list-group list-group-flush">
                        @foreach($page->children as $child)
                        <li class="list-group-item d-flex justify-content-between align-items-center py-2">
                            <span>
                                @if($child->icon)<i class="{{ $child->icon }} mr-2"></i>@endif
                                {{ $child->title }}
                            </span>
                            <a href="{{ route('pages-edit', $child->id) }}" class="btn btn-sm btn-outline-primary">
                                <i class="fas fa-edit"></i>
                            </a>
                        </li>
                        @endforeach
                    </ul>
                    <a href="{{ route('pages-create') }}?parent={{ $page->id }}" class="btn btn-success btn-sm btn-block mt-3">
                        <i class="fas fa-plus mr-1"></i>Add Child Page
                    </a>
                </div>
            </div>
            @endif
        </div>
    </div>
</div>
@endsection

@section('page-level-styles')
<style>
    /* TipTap Editor Styles */
    .tiptap-editor-wrapper {
        border: 1px solid #d1d3e2;
        border-radius: 0.35rem;
        overflow: hidden;
    }
    
    .tiptap-toolbar {
        background: #f8f9fc;
        border-bottom: 1px solid #d1d3e2;
        padding: 8px 12px;
        display: flex;
        flex-wrap: wrap;
        gap: 4px;
    }
    
    .tiptap-toolbar .btn-group {
        display: flex;
        gap: 2px;
        margin-right: 8px;
        padding-right: 8px;
        border-right: 1px solid #d1d3e2;
    }
    
    .tiptap-toolbar .btn-group:last-child {
        border-right: none;
    }
    
    .tiptap-toolbar button {
        background: white;
        border: 1px solid #d1d3e2;
        border-radius: 4px;
        padding: 6px 10px;
        cursor: pointer;
        font-size: 14px;
        color: #5a5c69;
        transition: all 0.15s;
    }
    
    .tiptap-toolbar button:hover {
        background: #eaecf4;
        border-color: #bac8f3;
    }
    
    .tiptap-toolbar button.is-active {
        background: #4e73df;
        color: white;
        border-color: #4e73df;
    }
    
    .tiptap-content {
        min-height: 350px;
        padding: 16px;
        background: white;
    }
    
    .tiptap-content:focus {
        outline: none;
    }
    
    /* Editor content styles */
    .tiptap-content h1 { font-size: 2em; font-weight: bold; margin: 0.5em 0; }
    .tiptap-content h2 { font-size: 1.5em; font-weight: bold; margin: 0.5em 0; }
    .tiptap-content h3 { font-size: 1.25em; font-weight: bold; margin: 0.5em 0; }
    .tiptap-content p { margin: 0.5em 0; }
    .tiptap-content ul, .tiptap-content ol { padding-left: 1.5em; margin: 0.5em 0; }
    .tiptap-content blockquote {
        border-left: 3px solid #4e73df;
        padding-left: 1em;
        margin: 1em 0;
        color: #6c757d;
    }
    .tiptap-content code {
        background: #f8f9fc;
        padding: 2px 6px;
        border-radius: 4px;
        font-family: monospace;
    }
    .tiptap-content pre {
        background: #2d2d2d;
        color: #f8f8f2;
        padding: 1em;
        border-radius: 4px;
        overflow-x: auto;
    }
    .tiptap-content img {
        max-width: 100%;
        height: auto;
        margin: 1em 0;
    }
    .tiptap-content a {
        color: #4e73df;
        text-decoration: underline;
    }
    .tiptap-content hr {
        border: none;
        border-top: 2px solid #e3e6f0;
        margin: 1.5em 0;
    }
    
    .ProseMirror {
        min-height: 350px;
    }
    .ProseMirror:focus {
        outline: none;
    }
</style>
@endsection

@section('script')
<script type="importmap">
{
    "imports": {
        "@tiptap/core": "https://esm.sh/@tiptap/core@2.1.13",
        "@tiptap/starter-kit": "https://esm.sh/@tiptap/starter-kit@2.1.13",
        "@tiptap/extension-image": "https://esm.sh/@tiptap/extension-image@2.1.13",
        "@tiptap/extension-link": "https://esm.sh/@tiptap/extension-link@2.1.13",
        "@tiptap/extension-underline": "https://esm.sh/@tiptap/extension-underline@2.1.13",
        "@tiptap/extension-text-align": "https://esm.sh/@tiptap/extension-text-align@2.1.13",
        "@tiptap/extension-placeholder": "https://esm.sh/@tiptap/extension-placeholder@2.1.13"
    }
}
</script>

<script type="module">
    import { Editor } from '@tiptap/core';
    import StarterKit from '@tiptap/starter-kit';
    import Image from '@tiptap/extension-image';
    import Link from '@tiptap/extension-link';
    import Underline from '@tiptap/extension-underline';
    import TextAlign from '@tiptap/extension-text-align';
    import Placeholder from '@tiptap/extension-placeholder';

    // Get the textarea and its content
    const textarea = document.getElementById('page_content');
    const initialContent = textarea.value || '<p></p>';
    
    // Hide the original textarea
    textarea.style.display = 'none';
    
    // Create editor wrapper
    const wrapper = document.createElement('div');
    wrapper.className = 'tiptap-editor-wrapper';
    wrapper.innerHTML = `
        <div class="tiptap-toolbar">
            <div class="btn-group">
                <button type="button" data-action="undo" title="Undo"><i class="fas fa-undo"></i></button>
                <button type="button" data-action="redo" title="Redo"><i class="fas fa-redo"></i></button>
            </div>
            <div class="btn-group">
                <button type="button" data-action="bold" title="Bold"><i class="fas fa-bold"></i></button>
                <button type="button" data-action="italic" title="Italic"><i class="fas fa-italic"></i></button>
                <button type="button" data-action="underline" title="Underline"><i class="fas fa-underline"></i></button>
                <button type="button" data-action="strike" title="Strikethrough"><i class="fas fa-strikethrough"></i></button>
            </div>
            <div class="btn-group">
                <button type="button" data-action="heading1" title="Heading 1">H1</button>
                <button type="button" data-action="heading2" title="Heading 2">H2</button>
                <button type="button" data-action="heading3" title="Heading 3">H3</button>
                <button type="button" data-action="paragraph" title="Paragraph">P</button>
            </div>
            <div class="btn-group">
                <button type="button" data-action="alignLeft" title="Align Left"><i class="fas fa-align-left"></i></button>
                <button type="button" data-action="alignCenter" title="Align Center"><i class="fas fa-align-center"></i></button>
                <button type="button" data-action="alignRight" title="Align Right"><i class="fas fa-align-right"></i></button>
                <button type="button" data-action="alignJustify" title="Justify"><i class="fas fa-align-justify"></i></button>
            </div>
            <div class="btn-group">
                <button type="button" data-action="bulletList" title="Bullet List"><i class="fas fa-list-ul"></i></button>
                <button type="button" data-action="orderedList" title="Numbered List"><i class="fas fa-list-ol"></i></button>
                <button type="button" data-action="blockquote" title="Quote"><i class="fas fa-quote-right"></i></button>
            </div>
            <div class="btn-group">
                <button type="button" data-action="link" title="Insert Link"><i class="fas fa-link"></i></button>
                <button type="button" data-action="image" title="Insert Image"><i class="fas fa-image"></i></button>
                <button type="button" data-action="horizontalRule" title="Horizontal Line"><i class="fas fa-minus"></i></button>
            </div>
            <div class="btn-group">
                <button type="button" data-action="code" title="Inline Code"><i class="fas fa-code"></i></button>
                <button type="button" data-action="codeBlock" title="Code Block"><i class="fas fa-file-code"></i></button>
            </div>
            <div class="btn-group">
                <button type="button" data-action="clearFormat" title="Clear Formatting"><i class="fas fa-eraser"></i></button>
            </div>
        </div>
        <div class="tiptap-content" id="tiptap-editor"></div>
    `;
    
    textarea.parentNode.insertBefore(wrapper, textarea);
    
    // Initialize TipTap Editor
    const editor = new Editor({
        element: document.getElementById('tiptap-editor'),
        extensions: [
            StarterKit.configure({
                heading: {
                    levels: [1, 2, 3]
                }
            }),
            Image.configure({
                inline: true,
                allowBase64: true
            }),
            Link.configure({
                openOnClick: false,
                HTMLAttributes: {
                    target: '_blank'
                }
            }),
            Underline,
            TextAlign.configure({
                types: ['heading', 'paragraph']
            }),
            Placeholder.configure({
                placeholder: 'Start writing your page content...'
            })
        ],
        content: initialContent,
        onUpdate: ({ editor }) => {
            textarea.value = editor.getHTML();
        }
    });
    
    // Toolbar button handlers
    const toolbar = wrapper.querySelector('.tiptap-toolbar');
    
    const actions = {
        undo: () => editor.chain().focus().undo().run(),
        redo: () => editor.chain().focus().redo().run(),
        bold: () => editor.chain().focus().toggleBold().run(),
        italic: () => editor.chain().focus().toggleItalic().run(),
        underline: () => editor.chain().focus().toggleUnderline().run(),
        strike: () => editor.chain().focus().toggleStrike().run(),
        heading1: () => editor.chain().focus().toggleHeading({ level: 1 }).run(),
        heading2: () => editor.chain().focus().toggleHeading({ level: 2 }).run(),
        heading3: () => editor.chain().focus().toggleHeading({ level: 3 }).run(),
        paragraph: () => editor.chain().focus().setParagraph().run(),
        alignLeft: () => editor.chain().focus().setTextAlign('left').run(),
        alignCenter: () => editor.chain().focus().setTextAlign('center').run(),
        alignRight: () => editor.chain().focus().setTextAlign('right').run(),
        alignJustify: () => editor.chain().focus().setTextAlign('justify').run(),
        bulletList: () => editor.chain().focus().toggleBulletList().run(),
        orderedList: () => editor.chain().focus().toggleOrderedList().run(),
        blockquote: () => editor.chain().focus().toggleBlockquote().run(),
        code: () => editor.chain().focus().toggleCode().run(),
        codeBlock: () => editor.chain().focus().toggleCodeBlock().run(),
        horizontalRule: () => editor.chain().focus().setHorizontalRule().run(),
        clearFormat: () => editor.chain().focus().unsetAllMarks().clearNodes().run(),
        link: () => {
            const url = prompt('Enter URL:');
            if (url) {
                editor.chain().focus().extendMarkRange('link').setLink({ href: url }).run();
            }
        },
        image: () => {
            const url = prompt('Enter image URL:');
            if (url) {
                editor.chain().focus().setImage({ src: url }).run();
            }
        }
    };
    
    toolbar.addEventListener('click', (e) => {
        const button = e.target.closest('button');
        if (!button) return;
        
        const action = button.dataset.action;
        if (actions[action]) {
            actions[action]();
            updateToolbarState();
        }
    });
    
    // Update active state of buttons
    function updateToolbarState() {
        toolbar.querySelectorAll('button').forEach(btn => {
            const action = btn.dataset.action;
            let isActive = false;
            
            switch(action) {
                case 'bold': isActive = editor.isActive('bold'); break;
                case 'italic': isActive = editor.isActive('italic'); break;
                case 'underline': isActive = editor.isActive('underline'); break;
                case 'strike': isActive = editor.isActive('strike'); break;
                case 'heading1': isActive = editor.isActive('heading', { level: 1 }); break;
                case 'heading2': isActive = editor.isActive('heading', { level: 2 }); break;
                case 'heading3': isActive = editor.isActive('heading', { level: 3 }); break;
                case 'paragraph': isActive = editor.isActive('paragraph'); break;
                case 'bulletList': isActive = editor.isActive('bulletList'); break;
                case 'orderedList': isActive = editor.isActive('orderedList'); break;
                case 'blockquote': isActive = editor.isActive('blockquote'); break;
                case 'code': isActive = editor.isActive('code'); break;
                case 'codeBlock': isActive = editor.isActive('codeBlock'); break;
                case 'alignLeft': isActive = editor.isActive({ textAlign: 'left' }); break;
                case 'alignCenter': isActive = editor.isActive({ textAlign: 'center' }); break;
                case 'alignRight': isActive = editor.isActive({ textAlign: 'right' }); break;
                case 'alignJustify': isActive = editor.isActive({ textAlign: 'justify' }); break;
            }
            
            btn.classList.toggle('is-active', isActive);
        });
    }
    
    editor.on('selectionUpdate', updateToolbarState);
    editor.on('update', updateToolbarState);
    
    // Initial state
    updateToolbarState();
</script>

<script>
    // Page type toggle (non-module script for jQuery)
    $('input[name="page_type"]').on('change', function() {
        if ($(this).val() === 'single') {
            $('#parentSelectGroup').slideDown();
        } else {
            $('#parentSelectGroup').slideUp();
            $('#parent_id').val('');
        }
    });
</script>
@endsection
