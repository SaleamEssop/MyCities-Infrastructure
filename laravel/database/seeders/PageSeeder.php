<?php

namespace Database\Seeders;

use App\Models\Page;
use Illuminate\Database\Seeder;

class PageSeeder extends Seeder
{
    /**
     * Run the database seeds.
     * Uses upsert pattern - safe to run multiple times.
     * System pages are identified by slug and updated if they exist.
     *
     * @return void
     */
    public function run()
    {
        $this->seedSystemPages();
        $this->command->info('Sample pages created/updated successfully!');
    }

    /**
     * Upsert a page by slug (system identifier)
     */
    private function upsertPage(array $data, ?int $parentId = null): Page
    {
        $page = Page::updateOrCreate(
            ['slug' => $data['slug']], // Find by slug (unique identifier)
            array_merge($data, ['parent_id' => $parentId, 'is_demo' => 1]) // Mark as demo content
        );
        return $page;
    }

    private function seedSystemPages()
    {
        // ============================================
        // HEADER TAB PAGES (Single pages - appear in header)
        // ============================================

        // 1. Home/Welcome Page
        $this->upsertPage([
            'title' => 'Home',
            'slug' => 'home',
            'page_type' => 'single',
            'icon' => 'fas fa-home',
            'sort_order' => 1,
            'is_active' => true,
            'show_in_navigation' => true,
            'content' => '
<div style="text-align: center; padding: 20px;">
    <h1 style="color: #2c3e50; font-size: 32px; margin-bottom: 20px;">Welcome to MyCities</h1>
    <p style="font-size: 18px; color: #555; max-width: 600px; margin: 0 auto 30px;">
        Your trusted partner for water and electricity meter management. Track your usage, manage your accounts, and stay in control of your utilities.
    </p>
    <img src="https://images.unsplash.com/photo-1486406146926-c627a92ad1ab?w=800&q=80" alt="City Skyline" style="width: 100%; max-width: 700px; border-radius: 12px; box-shadow: 0 4px 20px rgba(0,0,0,0.15); margin-bottom: 30px;">
    
    <div style="display: flex; flex-wrap: wrap; justify-content: center; gap: 20px; margin-top: 30px;">
        <div style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 25px; border-radius: 12px; width: 200px; text-align: center;">
            <i class="fas fa-tint" style="font-size: 36px; margin-bottom: 10px;"></i>
            <h3>Water Meters</h3>
            <p style="font-size: 14px; opacity: 0.9;">Track water consumption</p>
        </div>
        <div style="background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%); color: white; padding: 25px; border-radius: 12px; width: 200px; text-align: center;">
            <i class="fas fa-bolt" style="font-size: 36px; margin-bottom: 10px;"></i>
            <h3>Electricity</h3>
            <p style="font-size: 14px; opacity: 0.9;">Monitor power usage</p>
        </div>
        <div style="background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%); color: white; padding: 25px; border-radius: 12px; width: 200px; text-align: center;">
            <i class="fas fa-chart-line" style="font-size: 36px; margin-bottom: 10px;"></i>
            <h3>Reports</h3>
            <p style="font-size: 14px; opacity: 0.9;">Detailed analytics</p>
        </div>
    </div>
</div>
            ',
        ]);

        // 2. About Us Page
        $this->upsertPage([
            'title' => 'About Us',
            'slug' => 'about',
            'page_type' => 'single',
            'icon' => 'fas fa-info-circle',
            'sort_order' => 2,
            'is_active' => true,
            'show_in_navigation' => true,
            'content' => '
<div style="padding: 20px;">
    <h1 style="color: #2c3e50; text-align: center; margin-bottom: 30px;">About MyCities</h1>
    <p style="color: #555; line-height: 1.8; font-size: 16px; max-width: 700px; margin: 0 auto;">
        MyCities was founded with a simple goal: to make utility management easier for everyone. We believe that tracking your water and electricity usage should not be complicated. Our platform empowers property owners, tenants, and managers to monitor consumption, predict costs, and make informed decisions.
    </p>
</div>
            ',
        ]);

        // 3. Contact Page
        $this->upsertPage([
            'title' => 'Contact',
            'slug' => 'contact',
            'page_type' => 'single',
            'icon' => 'fas fa-envelope',
            'sort_order' => 3,
            'is_active' => true,
            'show_in_navigation' => true,
            'content' => '
<div style="padding: 20px; max-width: 800px; margin: 0 auto;">
    <h1 style="color: #2c3e50; text-align: center; margin-bottom: 30px;">Contact Us</h1>
    <div style="display: flex; flex-wrap: wrap; gap: 30px; margin-bottom: 40px;">
        <div style="flex: 1; min-width: 250px; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 30px; border-radius: 12px; text-align: center;">
            <i class="fas fa-phone" style="font-size: 40px; margin-bottom: 15px;"></i>
            <h3>Call Us</h3>
            <p style="font-size: 18px; margin-top: 10px;">+27 11 123 4567</p>
        </div>
        <div style="flex: 1; min-width: 250px; background: linear-gradient(135deg, #11998e 0%, #38ef7d 100%); color: white; padding: 30px; border-radius: 12px; text-align: center;">
            <i class="fas fa-envelope" style="font-size: 40px; margin-bottom: 15px;"></i>
            <h3>Email Us</h3>
            <p style="font-size: 18px; margin-top: 10px;">info@mycities.co.za</p>
        </div>
    </div>
</div>
            ',
        ]);

        // ============================================
        // MENU GROUP: Services
        // ============================================
        $servicesPage = $this->upsertPage([
            'title' => 'Services',
            'slug' => 'services',
            'page_type' => 'parent',
            'icon' => 'fas fa-cogs',
            'sort_order' => 4,
            'is_active' => true,
            'show_in_navigation' => true,
            'content' => '<div style="padding: 20px; text-align: center;"><h1>Our Services</h1><p>Comprehensive utility management solutions.</p></div>',
        ]);

        // Child: Water Monitoring
        $this->upsertPage([
            'title' => 'Water Monitoring',
            'slug' => 'water-monitoring',
            'page_type' => 'single',
            'icon' => 'fas fa-tint',
            'sort_order' => 1,
            'is_active' => true,
            'show_in_navigation' => true,
            'content' => '<div style="padding: 20px;"><h1 style="color: #3498db;">Water Monitoring</h1><p>Track every drop with our advanced monitoring system.</p></div>',
        ], $servicesPage->id);

        // Child: Electricity Tracking
        $this->upsertPage([
            'title' => 'Electricity Tracking',
            'slug' => 'electricity-tracking',
            'page_type' => 'single',
            'icon' => 'fas fa-bolt',
            'sort_order' => 2,
            'is_active' => true,
            'show_in_navigation' => true,
            'content' => '<div style="padding: 20px;"><h1 style="color: #f39c12;">Electricity Tracking</h1><p>Monitor your power usage with precision.</p></div>',
        ], $servicesPage->id);

        // ============================================
        // MENU GROUP: Help
        // ============================================
        $helpPage = $this->upsertPage([
            'title' => 'Help',
            'slug' => 'help',
            'page_type' => 'parent',
            'icon' => 'fas fa-question-circle',
            'sort_order' => 5,
            'is_active' => true,
            'show_in_navigation' => true,
            'content' => '<div style="padding: 20px; text-align: center;"><h1>Help & Support</h1><p>Find answers to common questions.</p></div>',
        ]);

        // Child: FAQ
        $this->upsertPage([
            'title' => 'FAQ',
            'slug' => 'faq',
            'page_type' => 'single',
            'icon' => 'fas fa-question',
            'sort_order' => 1,
            'is_active' => true,
            'show_in_navigation' => true,
            'content' => '<div style="padding: 20px;"><h1>Frequently Asked Questions</h1><p>Common questions answered here.</p></div>',
        ], $helpPage->id);

        // Child: Getting Started
        $this->upsertPage([
            'title' => 'Getting Started',
            'slug' => 'getting-started',
            'page_type' => 'single',
            'icon' => 'fas fa-play-circle',
            'sort_order' => 2,
            'is_active' => true,
            'show_in_navigation' => true,
            'content' => '<div style="padding: 20px;"><h1>Getting Started with MyCities</h1><p>Step-by-step guide to get you started.</p></div>',
        ], $helpPage->id);
    }
}