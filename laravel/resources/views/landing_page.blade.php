<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <title>MyCities - {{ $landingTitle }}</title>
    <link href="https://fonts.googleapis.com/css?family=Nunito:200,300,400,600,700,800,900" rel="stylesheet">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        html, body { height: 100%; font-family: 'Nunito', sans-serif; }
        
        .hero-section {
            min-height: 100vh;
            background-image: url('{{ $backgroundUrl }}');
            background-size: cover;
            background-position: center;
            background-attachment: fixed;
            display: flex;
            align-items: center;
            justify-content: center;
            position: relative;
        }
        
        .hero-section::before {
            content: '';
            position: absolute;
            top: 0; left: 0; right: 0; bottom: 0;
            background: rgba(0,0,0,0.3);
        }
        
        .hero-content {
            position: relative;
            z-index: 1;
            background: rgba(180, 100, 80, 0.7);
            padding: 40px 60px;
            border-radius: 8px;
            max-width: 600px;
            text-align: center;
            color: white;
        }
        
        .hero-content h1 {
            font-size: 28px;
            font-weight: 300;
            font-style: italic;
            margin-bottom: 30px;
            line-height: 1.4;
        }
        
        .login-btn {
            display: inline-block;
            background: rgba(139, 69, 61, 0.9);
            color: white;
            padding: 15px 50px;
            text-decoration: none;
            font-size: 16px;
            font-weight: 600;
            border-radius: 4px;
            transition: background 0.3s;
        }
        
        .login-btn:hover {
            background: rgba(139, 69, 61, 1);
        }
        
        .content-section {
            padding: 60px 20px;
            max-width: 1200px;
            margin: 0 auto;
        }
        
        .page-content {
            background: white;
            padding: 40px;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            margin-bottom: 30px;
        }
        
        .page-content h2 {
            color: #333;
            margin-bottom: 20px;
            font-size: 24px;
        }
        
        .page-content p, .page-content div {
            color: #666;
            line-height: 1.8;
        }
        
        .pages-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 30px;
            margin-top: 40px;
        }
        
        .page-card {
            background: white;
            padding: 30px;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        
        .page-card h3 {
            color: #333;
            margin-bottom: 15px;
            font-size: 20px;
        }
        
        .page-card p {
            color: #666;
            line-height: 1.6;
            font-size: 14px;
        }
        
        footer {
            background: #333;
            color: white;
            text-align: center;
            padding: 20px;
            font-size: 14px;
        }
    </style>
</head>
<body>
    <!-- Hero Section -->
    <section class="hero-section">
        <div class="hero-content">
            <h1>{{ $landingSubtitle }}</h1>
            <a href="{{ url('/web-app') }}" class="login-btn">Login / Register</a>
        </div>
    </section>
    
    <!-- Content Section - Pages from Editor -->
    @if($homePage && $homePage->content)
    <section class="content-section">
        <div class="page-content">
            {!! $homePage->content !!}
        </div>
    </section>
    @endif
    
    <!-- Additional Pages -->
    @if($pages && $pages->count() > 0)
    <section class="content-section">
        <div class="pages-grid">
            @foreach($pages as $page)
            <div class="page-card">
                <h3>{{ $page->title }}</h3>
                <p>{!! \Illuminate\Support\Str::limit(strip_tags($page->content), 200) !!}</p>
            </div>
            @endforeach
        </div>
    </section>
    @endif
    
    <footer>
        &copy; {{ date('Y') }} MyCities. All rights reserved.
    </footer>
</body>
</html>