<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $page->meta_title ?? $page->title }} - Preview</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css" rel="stylesheet">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: #f8f9fa;
            color: #333;
            line-height: 1.6;
        }

        .preview-banner {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 12px 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            z-index: 1000;
        }

        .preview-banner .badge {
            background: rgba(255,255,255,0.2);
            padding: 5px 12px;
            border-radius: 20px;
            font-size: 12px;
        }

        .preview-banner a {
            color: white;
            text-decoration: none;
            background: rgba(255,255,255,0.2);
            padding: 8px 16px;
            border-radius: 6px;
            font-size: 14px;
            transition: background 0.2s;
        }

        .preview-banner a:hover {
            background: rgba(255,255,255,0.3);
        }

        .page-container {
            max-width: 800px;
            margin: 80px auto 40px;
            padding: 0 20px;
        }

        .page-header {
            background: white;
            border-radius: 12px;
            padding: 30px;
            margin-bottom: 20px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
        }

        .page-header h1 {
            font-size: 32px;
            font-weight: 700;
            color: #1a202c;
            margin-bottom: 10px;
        }

        .page-header .meta {
            color: #718096;
            font-size: 14px;
        }

        .page-header .meta i {
            margin-right: 5px;
        }

        .breadcrumbs {
            padding: 15px 0;
            font-size: 14px;
            color: #718096;
        }

        .breadcrumbs a {
            color: #667eea;
            text-decoration: none;
        }

        .breadcrumbs a:hover {
            text-decoration: underline;
        }

        .page-content {
            background: white;
            border-radius: 12px;
            padding: 40px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
        }

        .page-content img {
            max-width: 100%;
            height: auto;
            border-radius: 8px;
            margin: 20px 0;
        }

        .page-content h2 {
            font-size: 24px;
            margin: 30px 0 15px;
            color: #1a202c;
        }

        .page-content h3 {
            font-size: 20px;
            margin: 25px 0 12px;
            color: #2d3748;
        }

        .page-content p {
            margin-bottom: 15px;
            color: #4a5568;
        }

        .page-content ul, .page-content ol {
            margin: 15px 0;
            padding-left: 30px;
        }

        .page-content li {
            margin-bottom: 8px;
        }

        .page-content a {
            color: #667eea;
        }

        .page-content blockquote {
            border-left: 4px solid #667eea;
            padding: 15px 20px;
            background: #f7fafc;
            margin: 20px 0;
            font-style: italic;
            color: #4a5568;
        }

        .children-nav {
            background: white;
            border-radius: 12px;
            padding: 25px;
            margin-top: 20px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
        }

        .children-nav h3 {
            font-size: 18px;
            margin-bottom: 15px;
            color: #1a202c;
        }

        .children-list {
            list-style: none;
        }

        .children-list li {
            border-bottom: 1px solid #e2e8f0;
        }

        .children-list li:last-child {
            border-bottom: none;
        }

        .children-list a {
            display: block;
            padding: 12px 0;
            color: #4a5568;
            text-decoration: none;
            transition: color 0.2s;
        }

        .children-list a:hover {
            color: #667eea;
        }

        .children-list i {
            margin-right: 10px;
            color: #667eea;
        }

        .empty-content {
            text-align: center;
            padding: 60px 20px;
            color: #718096;
        }

        .empty-content i {
            font-size: 48px;
            margin-bottom: 20px;
            opacity: 0.5;
        }

        @media (max-width: 768px) {
            .page-header h1 {
                font-size: 24px;
            }
            
            .page-content {
                padding: 25px;
            }
        }
    </style>
</head>
<body>
    <div class="preview-banner">
        <div>
            <span class="badge"><i class="fas fa-eye mr-2"></i>PREVIEW MODE</span>
            <span class="ml-3">{{ $page->title }}</span>
        </div>
        <a href="{{ route('pages-edit', $page->id) }}">
            <i class="fas fa-edit mr-2"></i>Edit Page
        </a>
    </div>

    <div class="page-container">
        @if($page->parent)
        <div class="breadcrumbs">
            <a href="#">Home</a>
            <span class="mx-2">/</span>
            <a href="#">{{ $page->parent->title }}</a>
            <span class="mx-2">/</span>
            <span>{{ $page->title }}</span>
        </div>
        @endif

        <div class="page-header">
            <h1>
                @if($page->icon)<i class="{{ $page->icon }} mr-2"></i>@endif
                {{ $page->title }}
            </h1>
            <div class="meta">
                <span><i class="fas fa-calendar"></i> {{ $page->updated_at->format('M d, Y') }}</span>
                <span class="ml-3"><i class="fas fa-link"></i> {{ $page->url }}</span>
            </div>
        </div>

        <div class="page-content">
            @if($page->content)
                {!! $page->content !!}
            @else
                <div class="empty-content">
                    <i class="fas fa-file-alt"></i>
                    <h3>No Content Yet</h3>
                    <p>This page doesn't have any content. Edit the page to add content.</p>
                </div>
            @endif
        </div>

        @if($page->page_type == 'parent' && $page->children->count() > 0)
        <div class="children-nav">
            <h3><i class="fas fa-sitemap mr-2"></i>In This Section</h3>
            <ul class="children-list">
                @foreach($page->children as $child)
                <li>
                    <a href="{{ route('pages-preview', $child->id) }}">
                        @if($child->icon)<i class="{{ $child->icon }}"></i>@else<i class="fas fa-file"></i>@endif
                        {{ $child->title }}
                    </a>
                </li>
                @endforeach
            </ul>
        </div>
        @endif
    </div>
</body>
</html>






