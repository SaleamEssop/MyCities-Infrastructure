<?php

namespace App\Http\Controllers;

use App\Models\Page;
use App\Models\Settings;
use Illuminate\Support\Facades\Storage;

class LandingPageController extends Controller
{
    public function show()
    {
        $settings = Settings::first();

        $backgroundUrl = $settings && $settings->landing_background
            ? Storage::url($settings->landing_background)
            : asset('images/bg.webp');

        $landingTitle = $settings->landing_title ?? 'Welcome to MyCities';
        $landingSubtitle = $settings->landing_subtitle
            ?? 'Welcome to the MyCities App where you can manage your meters and much more free of cost!';

        $homePage = Page::where('slug', 'home')->first();
        $pages = Page::where('is_active', 1)
            ->where('slug', '!=', 'home')
            ->orderBy('sort_order')
            ->get();

        return view('landing_page', [
            'settings' => $settings,
            'backgroundUrl' => $backgroundUrl,
            'landingTitle' => $landingTitle,
            'landingSubtitle' => $landingSubtitle,
            'homePage' => $homePage,
            'pages' => $pages,
        ]);
    }
}
