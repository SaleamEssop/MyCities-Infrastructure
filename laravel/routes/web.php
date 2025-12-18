<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AdminController;
use App\Http\Controllers\AdsController;
use App\Http\Controllers\PagesController;
use App\Http\Controllers\RegionsCostController;
use App\Http\Controllers\TariffTemplateController;
use App\Http\Controllers\Admin\UserManagementController;
use App\Http\Controllers\Admin\UserAccountSetupController;
use App\Http\Controllers\Admin\UserAccountManagerController;
use Illuminate\Support\Facades\Auth;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
*/

Route::get('/', function () {
    return view('landing_page');
});

Route::get('/app', function () {
    return view('web_app_blade');
});

Route::get('/admin/login', function() {
    return view('admin.login');
})->name('login');

Route::post('/admin/login', [AdminController::class, 'login'])->name('admin.login');

// Admin Forgot Password
Route::get('/admin/forgot-password', function() {
    return view('admin.forgot-password');
})->name('admin.forgot-password');

Route::post('/admin/forgot-password', [AdminController::class, 'forgotPassword'])->name('admin.forgot-password.submit');

// Admin routes
Route::middleware(['auth'])->prefix('admin')->group(function() {
    Route::get('/', function () {
        return view('admin.home');
    });

    Route::get('/logout', function() {
        Auth::logout();
        return redirect('/admin/login');
    })->name('admin.logout');

    // --- USER ACCOUNTS - SETUP (Wizard Flow) ---
    Route::get('user-accounts/setup', [UserAccountSetupController::class, 'index'])->name('user-accounts.setup');
    Route::post('user-accounts/setup', [UserAccountSetupController::class, 'store'])->name('user-accounts.setup.store');
    Route::post('user-accounts/setup/user-only', [UserAccountSetupController::class, 'storeUserOnly'])->name('user-accounts.setup.store-user-only');
    Route::post('user-accounts/setup/validate-email', [UserAccountSetupController::class, 'validateEmail'])->name('user-accounts.setup.validate-email');
    Route::post('user-accounts/setup/validate-phone', [UserAccountSetupController::class, 'validatePhone'])->name('user-accounts.setup.validate-phone');
    Route::get('user-accounts/setup/tariffs/{regionId}', [UserAccountSetupController::class, 'getTariffTemplatesByRegion'])->name('user-accounts.setup.tariffs');
    Route::get('user-accounts/setup/tariff-details/{tariffId}', [UserAccountSetupController::class, 'getTariffDetails'])->name('user-accounts.setup.tariff-details');
    Route::post('user-accounts/setup/create-test-user', [UserAccountSetupController::class, 'createTestUser'])->name('user-accounts.setup.create-test-user');

    // --- USER ACCOUNTS - MANAGER (Dashboard) ---
    Route::get('user-accounts/manager', [UserAccountManagerController::class, 'index'])->name('user-accounts.manager');
    Route::get('user-accounts/manager/search', [UserAccountManagerController::class, 'search'])->name('user-accounts.manager.search');
    Route::get('user-accounts/manager/user/{id}', [UserAccountManagerController::class, 'getUserData'])->name('user-accounts.manager.user');
    Route::put('user-accounts/manager/user/{id}', [UserAccountManagerController::class, 'updateUser'])->name('user-accounts.manager.update-user');
    Route::delete('user-accounts/manager/user/{id}', [UserAccountManagerController::class, 'deleteUser'])->name('user-accounts.manager.delete-user');
    Route::put('user-accounts/manager/account/{id}', [UserAccountManagerController::class, 'updateAccount'])->name('user-accounts.manager.update-account');
    Route::post('user-accounts/manager/meter', [UserAccountManagerController::class, 'addMeter'])->name('user-accounts.manager.add-meter');
    Route::put('user-accounts/manager/meter/{id}', [UserAccountManagerController::class, 'updateMeter'])->name('user-accounts.manager.update-meter');
    Route::delete('user-accounts/manager/meter/{id}', [UserAccountManagerController::class, 'deleteMeter'])->name('user-accounts.manager.delete-meter');
    Route::post('user-accounts/manager/reading', [UserAccountManagerController::class, 'addReading'])->name('user-accounts.manager.add-reading');
    Route::get('user-accounts/manager/readings/{meterId}', [UserAccountManagerController::class, 'getReadings'])->name('user-accounts.manager.readings');
    Route::get('user-accounts/manager/tariffs/{regionId}', [UserAccountManagerController::class, 'getTariffTemplatesByRegion'])->name('user-accounts.manager.tariffs');
    // Account billing & payments
    Route::get('user-accounts/manager/billing/{accountId}', [UserAccountManagerController::class, 'getAccountBilling'])->name('user-accounts.manager.billing');
    Route::get('user-accounts/manager/billing-history/{accountId}', [UserAccountManagerController::class, 'getBillingHistory'])->name('user-accounts.manager.billing-history');
    Route::get('user-accounts/billing/{accountId}', [UserAccountManagerController::class, 'showAccountBilling'])->name('user-accounts.billing');
    Route::post('user-accounts/manager/payment', [UserAccountManagerController::class, 'addPayment'])->name('user-accounts.manager.add-payment');
    Route::delete('user-accounts/manager/payment/{id}', [UserAccountManagerController::class, 'deletePayment'])->name('user-accounts.manager.delete-payment');

    // --- ENHANCED USER MANAGEMENT (replaces legacy user routes for main listing) ---
    Route::get('user-management', [UserManagementController::class, 'index'])->name('user-management.index');
    Route::get('user-management/search', [UserManagementController::class, 'search'])->name('user-management.search');
    Route::post('user-management', [UserManagementController::class, 'store'])->name('user-management.store');
    Route::get('user-management/{id}', [UserManagementController::class, 'getUserData'])->name('user-management.show');
    Route::put('user-management/{id}', [UserManagementController::class, 'update'])->name('user-management.update');
    Route::delete('user-management/{id}', [UserManagementController::class, 'destroy'])->name('user-management.destroy');
    Route::post('user-management/generate-test', [UserManagementController::class, 'generateTestUser'])->name('user-management.generate-test');
    Route::delete('user-management/delete-test', [UserManagementController::class, 'deleteTestUsers'])->name('user-management.delete-test');
    Route::post('user-management/clone/{id}', [UserManagementController::class, 'cloneUser'])->name('user-management.clone');

    // --- USERS (Legacy routes - kept for backward compatibility) ---
    Route::get('users', [AdminController::class, 'showUsers'])->name('show-users');
    Route::get('users/add', [AdminController::class, 'addUserForm'])->name('add-user-form');
    Route::post('users/add', [AdminController::class, 'createUser'])->name('add-user'); 
    Route::get('users/edit/{id}', [AdminController::class, 'editUserForm']);
    Route::post('users/edit', [AdminController::class, 'editUser'])->name('edit-user');
    Route::get('users/delete/{id}', [AdminController::class, 'deleteUser']);

    // --- SITES ---
    Route::get('sites', [AdminController::class, 'showSites'])->name('show-sites');
    Route::get('sites/add', [AdminController::class, 'addSiteForm'])->name('create-site-form'); 
    Route::post('sites/add', [AdminController::class, 'createSite'])->name('add-site'); 
    Route::get('sites/edit/{id}', [AdminController::class, 'editSiteForm']);
    Route::post('sites/edit', [AdminController::class, 'editSite'])->name('edit-site');
    Route::get('sites/delete/{id}', [AdminController::class, 'deleteSite']);
    Route::post('sites/get-by-user', [AdminController::class, 'getSitesByUser'])->name('get-sites-by-user');

    // --- ACCOUNTS ---
    Route::get('accounts', [AdminController::class, 'showAccounts'])->name('account-list');
    Route::get('accounts/add', [AdminController::class, 'addAccountForm'])->name('add-account-form'); 
    Route::post('accounts/add', [AdminController::class, 'createAccount'])->name('add-account');
    Route::get('account/edit/{id}', [AdminController::class, 'editAccountForm']);
    Route::post('account/edit', [AdminController::class, 'editAccount'])->name('edit-account');
    Route::get('account/delete/{id}', [AdminController::class, 'deleteAccount']);
    
    // AJAX Routes for Dropdowns & Details
    Route::post('accounts/get-by-site', [AdminController::class, 'getAccountsBySite'])->name('get-accounts-by-site');
    Route::post('accounts/get-details', [AdminController::class, 'getAccountDetails'])->name('get-account-details');

    // --- METERS ---
    Route::get('meters', [AdminController::class, 'showMeters'])->name('meters-list'); 
    Route::get('meters/add', [AdminController::class, 'addMeterForm'])->name('add-meter-form');
    Route::post('meters/add', [AdminController::class, 'createMeter'])->name('add-meter');

    // --- READINGS ---
    Route::get('readings', [AdminController::class, 'showReadings'])->name('meter-reading-list');
    Route::get('readings/add', [AdminController::class, 'addReadingForm'])->name('add-meter-reading-form');
    Route::post('readings/add', [AdminController::class, 'createReading'])->name('add-reading');

    // --- PAYMENTS ---
    Route::get('payments', [AdminController::class, 'showPayments'])->name('payments-list');
    Route::get('payments/add', [AdminController::class, 'addPaymentForm'])->name('add-payment-form');
    Route::post('payments/add', [AdminController::class, 'createPayment'])->name('add-payment');
    Route::get('payments/delete/{id}', [AdminController::class, 'deletePayment']);

    // --- REGION COSTS (Legacy routes - kept for backward compatibility) ---
    Route::get('region_cost', [RegionsCostController::class, 'index'])->name('region-cost');
    Route::get('region_cost/create', [RegionsCostController::class, 'create'])->name('region-cost-create');
    Route::post('region_cost', [RegionsCostController::class, 'store'])->name('region-cost-store');
    Route::get('/region_cost/edit/{id}', [RegionsCostController::class, 'edit'])->name('region-cost-edit');
    Route::post('region_cost/update', [RegionsCostController::class, 'update'])->name('update-region-cost');
    Route::get('/region_cost/delete/{id}', [RegionsCostController::class, 'delete']);
    Route::post('region_cost/copy_record', [RegionsCostController::class, 'copyRecord'])->name('copy-region-cost');
    
    // --- TARIFF TEMPLATES (New routes) ---
    Route::get('tariff_template', [TariffTemplateController::class, 'index'])->name('tariff-template');
    Route::get('tariff_template/create', [TariffTemplateController::class, 'create'])->name('tariff-template-create');
    Route::post('tariff_template', [TariffTemplateController::class, 'store'])->name('tariff-template-store');
    Route::get('/tariff_template/edit/{id}', [TariffTemplateController::class, 'edit'])->name('tariff-template-edit');
    Route::post('tariff_template/update', [TariffTemplateController::class, 'update'])->name('update-tariff-template');
    Route::get('/tariff_template/delete/{id}', [TariffTemplateController::class, 'delete']);
    Route::post('tariff_template/copy_record', [TariffTemplateController::class, 'copyRecord'])->name('copy-tariff-template');
    
    // --- TARIFF TEMPLATES BY REGION (AJAX endpoint) ---
    Route::get('tariff-templates/by-region/{regionId}', [AdminController::class, 'getTariffTemplatesByRegion'])->name('get-tariff-templates-by-region');

    // --- REGIONS ---
    Route::get('regions', [AdminController::class, 'showRegions'])->name('regions-list');
    Route::post('/region/edit', [AdminController::class, 'editRegion'])->name('edit-region');
    Route::get('region/add', [AdminController::class, 'addRegionForm'])->name('add-region-form');
    Route::get('/region/edit/{id}', [AdminController::class, 'editRegionForm']);
    Route::post('regions/add', [AdminController::class, 'createRegion'])->name('add-region');
    Route::get('/region/delete/{id}', [AdminController::class, 'deleteRegion']);
    Route::get('/region/email/{id}', [AdminController::class, 'getEmailBasedRegion'])->name('get-email-region');

    // --- ALARMS ---
    Route::get('alarms', [AdminController::class, 'showAlarms'])->name('alarms');

    // --- ADS & CONTENT MANAGEMENT ---
    Route::get('ads', [AdsController::class, 'index'])->name('ads-list');
    Route::post('ads/add', [AdsController::class, 'store'])->name('add-ads');
    Route::get('ads/edit/{id}', [AdsController::class, 'edit'])->name('edit-ads-form');
    Route::post('ads/edit', [AdsController::class, 'update'])->name('edit-ad');
    Route::get('ads/delete/{id}', [AdsController::class, 'destroy'])->name('delete-ad');
    
    // --- ADS CATEGORIES ---
    Route::get('ads-categories', [AdsController::class, 'categories'])->name('ads-categories');
    Route::post('ads-category/add', [AdsController::class, 'storeCategory'])->name('add-ads-category');
    Route::post('ads-category/edit', [AdsController::class, 'updateCategory'])->name('edit-ads-category');
    Route::get('ads-category/delete/{id}', [AdsController::class, 'destroyCategory'])->name('delete-ads-category');
    
    // --- CKEDITOR IMAGE UPLOAD ---
    Route::post('ckeditor/upload', [AdsController::class, 'uploadImage'])->name('ckeditor.image-upload');

    // --- PAGE MANAGEMENT ---
    Route::get('pages', [PagesController::class, 'index'])->name('pages-list');
    Route::get('pages/create', [PagesController::class, 'create'])->name('pages-create');
    Route::post('pages/store', [PagesController::class, 'store'])->name('pages-store');
    Route::get('pages/edit/{id}', [PagesController::class, 'edit'])->name('pages-edit');
    Route::post('pages/update', [PagesController::class, 'update'])->name('pages-update');
    Route::get('pages/delete/{id}', [PagesController::class, 'destroy'])->name('pages-delete');
    Route::get('pages/preview/{id}', [PagesController::class, 'preview'])->name('pages-preview');
    Route::post('pages/toggle-active/{id}', [PagesController::class, 'toggleActive'])->name('pages-toggle-active');
    Route::post('pages/update-order', [PagesController::class, 'updateOrder'])->name('pages-update-order');
});
