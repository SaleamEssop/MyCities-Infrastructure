<?php

namespace App\Http\Controllers;

use App\Models\Ads;
use App\Models\AdsCategory;
use App\Models\Settings;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Storage;

class AdsController extends Controller
{
    /**
     * Display list of all ads
     */
    public function index()
    {
        $settings = Settings::first();
        $demoMode = $settings->demo_mode ?? true;

        if ($demoMode) {
            // Demo mode: show all ads including demo content
            $ads = Ads::with('category')->orderBy('priority')->get();
        } else {
            // Production mode: hide demo ads
            $ads = Ads::with('category')->where('is_demo', false)->orderBy('priority')->get();
        }
        
        $categories = AdsCategory::all();
        return view('admin.ads', compact('ads', 'categories'));
    }

    /**
     * Store a new ad
     */
    public function store(Request $request)
    {
        $request->validate([
            'ad_name' => 'required|string|max:255',
            'ads_category_id' => 'required|exists:ads_categories,id',
            'ad_url' => 'nullable|string',
            'ad_price' => 'nullable|numeric',
            'ad_image' => 'nullable|image|mimes:jpeg,png,jpg,gif,svg|max:2048',
        ]);

        $imagePath = null;
        if ($request->hasFile('ad_image')) {
            $imagePath = $request->file('ad_image')->store('public/ads');
        }

        Ads::create([
            'name' => $request->ad_name,
            'ads_category_id' => $request->ads_category_id,
            'url' => $request->ad_url ?? '',
            'price' => $request->ad_price ?? 0,
            'priority' => $request->ad_priority,
            'image' => $imagePath ?? '',
            'description' => $request->input('description-editor'),
        ]);

        Session::flash('alert-class', 'alert-success');
        Session::flash('alert-message', 'Ad created successfully');
        return redirect()->route('ads-list');
    }

    /**
     * Show edit form for an ad
     */
    public function edit($id)
    {
        $ad = Ads::findOrFail($id);
        $categories = AdsCategory::all();
        return view('admin.edit_ads', compact('ad', 'categories'));
    }

    /**
     * Update an existing ad
     */
    public function update(Request $request)
    {
        $request->validate([
            'ad_id' => 'required|exists:ads,id',
            'ad_name' => 'required|string|max:255',
            'ads_category_id' => 'required|exists:ads_categories,id',
        ]);

        $ad = Ads::findOrFail($request->ad_id);

        $data = [
            'name' => $request->ad_name,
            'ads_category_id' => $request->ads_category_id,
            'url' => $request->ad_url ?? '',
            'price' => $request->ad_price ?? 0,
            'priority' => $request->ad_priority,
            'description' => $request->input('description-editor'),
        ];

        if ($request->hasFile('ad_image')) {
            // Delete old image if exists
            if ($ad->getRawOriginal('image')) {
                Storage::delete($ad->getRawOriginal('image'));
            }
            $data['image'] = $request->file('ad_image')->store('public/ads');
        }

        $ad->update($data);

        Session::flash('alert-class', 'alert-success');
        Session::flash('alert-message', 'Ad updated successfully');
        return redirect()->route('ads-list');
    }

    /**
     * Delete an ad
     */
    public function destroy($id)
    {
        $ad = Ads::findOrFail($id);
        
        // Delete image if exists
        if ($ad->getRawOriginal('image')) {
            Storage::delete($ad->getRawOriginal('image'));
        }
        
        $ad->delete();

        Session::flash('alert-class', 'alert-success');
        Session::flash('alert-message', 'Ad deleted successfully');
        return redirect()->route('ads-list');
    }

    /**
     * Display list of all ad categories
     */
    public function categories()
    {
        $categories = AdsCategory::with('child_display')->get();
        $parent_categories = AdsCategory::pluck('name', 'id')->toArray();
        return view('admin.ads_categories', compact('categories', 'parent_categories'));
    }

    /**
     * Store a new category
     */
    public function storeCategory(Request $request)
    {
        $request->validate([
            'category_name' => 'required|string|max:255',
        ]);

        AdsCategory::create([
            'name' => $request->category_name,
            'parent_id' => $request->parent_id,
        ]);

        Session::flash('alert-class', 'alert-success');
        Session::flash('alert-message', 'Category created successfully');
        return redirect()->route('ads-categories');
    }

    /**
     * Update a category
     */
    public function updateCategory(Request $request)
    {
        $request->validate([
            'category_id' => 'required|exists:ads_categories,id',
            'category_name' => 'required|string|max:255',
        ]);

        $category = AdsCategory::findOrFail($request->category_id);
        $category->update([
            'name' => $request->category_name,
            'parent_id' => $request->parent_id,
        ]);

        Session::flash('alert-class', 'alert-success');
        Session::flash('alert-message', 'Category updated successfully');
        return redirect()->route('ads-categories');
    }

    /**
     * Delete a category
     */
    public function destroyCategory($id)
    {
        $category = AdsCategory::findOrFail($id);
        
        // Check if category has ads
        if ($category->ads()->count() > 0) {
            Session::flash('alert-class', 'alert-danger');
            Session::flash('alert-message', 'Cannot delete category with ads. Delete the ads first.');
            return redirect()->route('ads-categories');
        }
        
        $category->delete();

        Session::flash('alert-class', 'alert-success');
        Session::flash('alert-message', 'Category deleted successfully');
        return redirect()->route('ads-categories');
    }

    /**
     * Handle CKEditor image upload
     */
    public function uploadImage(Request $request)
    {
        if ($request->hasFile('upload')) {
            $originName = $request->file('upload')->getClientOriginalName();
            $fileName = pathinfo($originName, PATHINFO_FILENAME);
            $extension = $request->file('upload')->getClientOriginalExtension();
            $fileName = $fileName . '_' . time() . '.' . $extension;

            $request->file('upload')->move(public_path('images/uploads'), $fileName);

            $url = asset('images/uploads/' . $fileName);
            return response()->json([
                'fileName' => $fileName,
                'uploaded' => 1,
                'url' => $url
            ]);
        }
    }

    /**
     * Landing page settings (background/title/subtitle)
     */
    public function landingSettings()
    {
        $settings = Settings::first();
        return view('admin.landing_settings', compact('settings'));
    }

    /**
     * Save landing page settings
     */
    public function saveLandingSettings(Request $request)
    {
        $request->validate([
            'landing_title' => 'nullable|string|max:255',
            'landing_subtitle' => 'nullable|string',
            'landing_background' => 'nullable|image|mimes:jpeg,png,jpg,gif,svg,webp|max:4096',
            'landing_background_url' => 'nullable|url',
        ]);

        $settings = Settings::first() ?? new Settings();

        // Remove previous file if we are replacing it
        $replaceImage = false;

        if ($request->hasFile('landing_background')) {
            $replaceImage = true;
            $path = $request->file('landing_background')->store('public/landing');
            $settings->landing_background = $path;
        } elseif ($request->filled('landing_background_url')) {
            $replaceImage = true;
            try {
                $response = Http::timeout(10)->get($request->landing_background_url);
                if ($response->successful()) {
                    $ext = pathinfo(parse_url($request->landing_background_url, PHP_URL_PATH), PATHINFO_EXTENSION) ?: 'jpg';
                    $filename = 'public/landing/url_' . time() . '.' . $ext;
                    Storage::put($filename, $response->body());
                    $settings->landing_background = $filename;
                }
            } catch (\Throwable $e) {
                // ignore and keep old image if download fails
            }
        }

        if ($replaceImage && $settings->getRawOriginal('landing_background')) {
            Storage::delete($settings->getRawOriginal('landing_background'));
        }

        $settings->landing_title = $request->landing_title ?? $settings->landing_title;
        $settings->landing_subtitle = $request->landing_subtitle ?? $settings->landing_subtitle;
        $settings->save();

        Session::flash('alert-class', 'alert-success');
        Session::flash('alert-message', 'Landing page settings updated');

        return redirect()->route('ads.landing-settings');
    }
}






