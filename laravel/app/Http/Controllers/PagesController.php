<?php

namespace App\Http\Controllers;

use App\Models\Page;
use App\Models\Settings;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Str;

class PagesController extends Controller
{
    /**
     * Display list of all pages in tree structure
     */
    public function index()
    {
        $settings = Settings::first();
        $demoMode = $settings->demo_mode ?? true;

        // Get root pages with their children
        if ($demoMode) {
            // Demo mode: show all pages including demo content
            $pages = Page::root()
                ->with(['children' => function($q) {
                    $q->orderBy('sort_order');
                }])
                ->orderBy('sort_order')
                ->get();
        } else {
            // Production mode: hide demo pages
            $pages = Page::root()
                ->where('is_demo', false)
                ->with(['children' => function($q) {
                    $q->where('is_demo', false)->orderBy('sort_order');
                }])
                ->orderBy('sort_order')
                ->get();
        }

        return view('admin.pages.index', compact('pages'));
    }

    /**
     * Show create page form
     */
    public function create()
    {
        // Get parent pages for dropdown (only 'parent' type pages)
        $parentPages = Page::root()->parentType()->orderBy('title')->get();
        
        return view('admin.pages.create', compact('parentPages'));
    }

    /**
     * Store a new page
     */
    public function store(Request $request)
    {
        $request->validate([
            'title' => 'required|string|max:255',
            'page_type' => 'required|in:single,parent',
            'parent_id' => 'nullable|exists:pages,id',
            'content' => 'nullable|string',
            'icon' => 'nullable|string|max:100',
            'sort_order' => 'nullable|integer',
        ]);

        $data = [
            'title' => $request->title,
            'slug' => $request->slug ? Str::slug($request->slug) : null,
            'page_type' => $request->page_type,
            'parent_id' => $request->parent_id,
            'content' => $request->input('page_content'),
            'icon' => $request->icon,
            'sort_order' => $request->sort_order ?? 0,
            'is_active' => $request->has('is_active'),
            'show_in_navigation' => $request->has('show_in_navigation'),
            'meta_title' => $request->meta_title,
            'meta_description' => $request->meta_description,
        ];

        // If parent_id is set, this is a child page
        if ($request->parent_id) {
            $data['page_type'] = 'single'; // Child pages are always single type
        }

        Page::create($data);

        Session::flash('alert-class', 'alert-success');
        Session::flash('alert-message', 'Page created successfully!');
        
        return redirect()->route('pages-list');
    }

    /**
     * Show edit page form
     */
    public function edit($id)
    {
        $page = Page::findOrFail($id);
        $parentPages = Page::root()
            ->parentType()
            ->where('id', '!=', $id)
            ->orderBy('title')
            ->get();
        
        return view('admin.pages.edit', compact('page', 'parentPages'));
    }

    /**
     * Update a page
     */
    public function update(Request $request)
    {
        $request->validate([
            'page_id' => 'required|exists:pages,id',
            'title' => 'required|string|max:255',
            'page_type' => 'required|in:single,parent',
        ]);

        $page = Page::findOrFail($request->page_id);

        $data = [
            'title' => $request->title,
            'page_type' => $request->page_type,
            'parent_id' => $request->parent_id,
            'content' => $request->input('page_content'),
            'icon' => $request->icon,
            'sort_order' => $request->sort_order ?? 0,
            'is_active' => $request->has('is_active'),
            'show_in_navigation' => $request->has('show_in_navigation'),
            'meta_title' => $request->meta_title,
            'meta_description' => $request->meta_description,
        ];

        // Update slug if provided
        if ($request->slug && $request->slug !== $page->slug) {
            $data['slug'] = Str::slug($request->slug);
        }

        // If changing to parent type, remove parent_id
        if ($request->page_type === 'parent') {
            $data['parent_id'] = null;
        }

        $page->update($data);

        Session::flash('alert-class', 'alert-success');
        Session::flash('alert-message', 'Page updated successfully!');
        
        return redirect()->route('pages-list');
    }

    /**
     * Delete a page
     */
    public function destroy($id)
    {
        $page = Page::findOrFail($id);
        
        // Check if page has children
        if ($page->hasChildren()) {
            Session::flash('alert-class', 'alert-danger');
            Session::flash('alert-message', 'Cannot delete page with child pages. Delete children first.');
            return redirect()->route('pages-list');
        }

        $page->delete();

        Session::flash('alert-class', 'alert-success');
        Session::flash('alert-message', 'Page deleted successfully!');
        
        return redirect()->route('pages-list');
    }

    /**
     * Toggle page active status (AJAX)
     */
    public function toggleActive($id)
    {
        $page = Page::findOrFail($id);
        $page->is_active = !$page->is_active;
        $page->save();

        return response()->json([
            'status' => 200,
            'is_active' => $page->is_active,
            'message' => $page->is_active ? 'Page activated' : 'Page deactivated'
        ]);
    }

    /**
     * Update sort order (AJAX)
     */
    public function updateOrder(Request $request)
    {
        $request->validate([
            'pages' => 'required|array',
            'pages.*.id' => 'required|exists:pages,id',
            'pages.*.sort_order' => 'required|integer',
        ]);

        foreach ($request->pages as $pageData) {
            Page::where('id', $pageData['id'])->update(['sort_order' => $pageData['sort_order']]);
        }

        return response()->json([
            'status' => 200,
            'message' => 'Order updated successfully'
        ]);
    }

    /**
     * Preview page content
     */
    public function preview($id)
    {
        $page = Page::with('children')->findOrFail($id);
        return view('admin.pages.preview', compact('page'));
    }
}






