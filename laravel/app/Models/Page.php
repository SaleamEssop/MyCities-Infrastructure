<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Page extends Model
{
    use HasFactory;

    protected $fillable = [
        'parent_id',
        'title',
        'slug',
        'content',
        'page_type',
        'icon',
        'sort_order',
        'is_active',
        'show_in_navigation',
        'meta_title',
        'meta_description',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'show_in_navigation' => 'boolean',
        'sort_order' => 'integer',
    ];

    /**
     * Boot function to auto-generate slug
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($page) {
            if (empty($page->slug)) {
                $page->slug = Str::slug($page->title);
            }
            // Ensure unique slug
            $originalSlug = $page->slug;
            $count = 1;
            while (static::where('slug', $page->slug)->exists()) {
                $page->slug = $originalSlug . '-' . $count++;
            }
        });

        static::updating(function ($page) {
            if ($page->isDirty('title') && !$page->isDirty('slug')) {
                $page->slug = Str::slug($page->title);
                // Ensure unique slug
                $originalSlug = $page->slug;
                $count = 1;
                while (static::where('slug', $page->slug)->where('id', '!=', $page->id)->exists()) {
                    $page->slug = $originalSlug . '-' . $count++;
                }
            }
        });
    }

    /**
     * Get the parent page
     */
    public function parent()
    {
        return $this->belongsTo(Page::class, 'parent_id');
    }

    /**
     * Get child pages
     */
    public function children()
    {
        return $this->hasMany(Page::class, 'parent_id')->orderBy('sort_order');
    }

    /**
     * Get active child pages
     */
    public function activeChildren()
    {
        return $this->hasMany(Page::class, 'parent_id')
            ->where('is_active', true)
            ->orderBy('sort_order');
    }

    /**
     * Scope for active pages
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope for navigation pages
     */
    public function scopeInNavigation($query)
    {
        return $query->where('show_in_navigation', true);
    }

    /**
     * Scope for root/parent level pages (no parent)
     */
    public function scopeRoot($query)
    {
        return $query->whereNull('parent_id');
    }

    /**
     * Scope for single pages (header tabs)
     */
    public function scopeSingle($query)
    {
        return $query->where('page_type', 'single');
    }

    /**
     * Scope for parent pages (hamburger menu)
     */
    public function scopeParentType($query)
    {
        return $query->where('page_type', 'parent');
    }

    /**
     * Check if page has children
     */
    public function hasChildren()
    {
        return $this->children()->count() > 0;
    }

    /**
     * Check if page is a child
     */
    public function isChild()
    {
        return !is_null($this->parent_id);
    }

    /**
     * Get full URL path
     */
    public function getUrlAttribute()
    {
        if ($this->isChild() && $this->parent) {
            return '/' . $this->parent->slug . '/' . $this->slug;
        }
        return '/' . $this->slug;
    }

    /**
     * Get breadcrumb trail
     */
    public function getBreadcrumbs()
    {
        $breadcrumbs = collect([$this]);
        $page = $this;
        
        while ($page->parent) {
            $breadcrumbs->prepend($page->parent);
            $page = $page->parent;
        }
        
        return $breadcrumbs;
    }
}






