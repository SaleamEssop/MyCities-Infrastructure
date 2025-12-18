<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

// APIs for mobile app
Route::prefix('v1')->group(function() {
    Route::get('/default-cost/get', [\App\Http\Controllers\ApiController::class, 'getDefaultCosts']);
    Route::get('/ads/get-categories', [\App\Http\Controllers\ApiController::class, 'getAdsCategories']);
    
    // Public Pages API (for app navigation)
    Route::get('/pages/navigation', [\App\Http\Controllers\ApiController::class, 'getNavigationPages']);
    Route::get('/pages/{slug}', [\App\Http\Controllers\ApiController::class, 'getPageBySlug']);
    Route::get('terms-and-conditions', [\App\Http\Controllers\ApiController::class, 'getTC']);
    // Billing (estimate/preview/finalize behave like estimate for now)
    Route::get('/bills', [\App\Http\Controllers\BillController::class, 'index']);
    Route::post('/bills', [\App\Http\Controllers\BillController::class, 'index']); // finalize via mode=finalize
    Route::post('/bills/{id}/recompute', [\App\Http\Controllers\BillController::class, 'recompute']);
    // User related routes
    Route::post('/user/logout', [\App\Http\Controllers\UserController::class, 'logout'])->middleware('auth:sanctum');

    Route::prefix('user')->group(function() {
        Route::post('/register', [\App\Http\Controllers\UserController::class, 'register']);
        Route::post('/login', [\App\Http\Controllers\UserController::class, 'login']);
    });

    // Site login route
    Route::post('/site/login', [\App\Http\Controllers\ApiController::class, 'siteLogin']);

    // Locations/Sites related routes
    Route::group(['middleware' => ['auth:sanctum'],'prefix' => 'sites'],function() {
        Route::post('/add', [\App\Http\Controllers\ApiController::class, 'addSite']);
        Route::post('/update', [\App\Http\Controllers\ApiController::class, 'updateSite']);
        Route::get('/get', [\App\Http\Controllers\ApiController::class, 'getSite']);
        Route::post('/delete', [\App\Http\Controllers\ApiController::class, 'deleteSite']);
    });

    // Account related routes
    Route::group(['middleware' => ['auth:sanctum'], 'prefix' => 'account'],function() {
        Route::post('/add', [\App\Http\Controllers\ApiController::class, 'addAccount']);
        Route::post('/update', [\App\Http\Controllers\ApiController::class, 'updateAccount']);
        Route::get('/get', [\App\Http\Controllers\ApiController::class, 'getAccounts']);
        Route::post('/delete', [\App\Http\Controllers\ApiController::class, 'deleteAccount']);
    });

    // Meters related routes
    Route::group(['middleware' => ['auth:sanctum'], 'prefix' => 'meter'],function() {
        Route::post('/add', [\App\Http\Controllers\ApiController::class, 'addMeter']);
        Route::post('/update', [\App\Http\Controllers\ApiController::class, 'updateMeter']);
        Route::get('/get', [\App\Http\Controllers\ApiController::class, 'getMeter']);
        Route::get('/types', [\App\Http\Controllers\ApiController::class, 'getMeterTypes']);
        Route::post('/delete', [\App\Http\Controllers\ApiController::class, 'deleteMeter']);
        Route::post('/add-readings', [\App\Http\Controllers\ApiController::class, 'addMeterReadings']);
        Route::post('/update-readings', [\App\Http\Controllers\ApiController::class, 'updateMeterReadings']);
        Route::get('/get-readings', [\App\Http\Controllers\ApiController::class, 'getMeterReadings']);
        Route::post('/delete-readings', [\App\Http\Controllers\ApiController::class, 'deleteMeterReading']);
    });

    // Ads related routes
    Route::group(['middleware' => ['auth:sanctum'], 'prefix' => 'ads'],function() {
        Route::get('/get', [\App\Http\Controllers\ApiController::class, 'getAds']);
      //  Route::get('/get-categories', [\App\Http\Controllers\ApiController::class, 'getAdsCategories']);
    });

    // Fixed Cost related routes
    Route::group(['middleware' => ['auth:sanctum'], 'prefix' => 'fixed-cost'],function() {
        Route::get('/get', [\App\Http\Controllers\ApiController::class, 'getFixedCosts']);
        Route::post('/add', [\App\Http\Controllers\ApiController::class, 'addFixedCost']);
    });

    // Default Cost related routes
    Route::group(['middleware' => ['auth:sanctum'], 'prefix' => 'default-cost'],function() {
       // Route::get('/get', [\App\Http\Controllers\ApiController::class, 'getDefaultCosts']);
        Route::post('/add', [\App\Http\Controllers\ApiController::class, 'addDefaultCost']);
    });

    // Regions related routes
    Route::group(['prefix' => 'regions'],function() {
        Route::get('/get', [\App\Http\Controllers\ApiController::class, 'getRegions']);
        Route::get('/getEmails/{id}', [\App\Http\Controllers\ApiController::class, 'getRegionEmails']);
        Route::get('/getEastimateCost', [\App\Http\Controllers\ApiController::class, 'getEastimateCost']);
        Route::get('/getAdditionalCost', [\App\Http\Controllers\ApiController::class, 'getAdditionalCost']);
        Route::get('/getBillday', [\App\Http\Controllers\ApiController::class, 'getBillday']);
        
        
    });

    Route::group(['prefix' => 'accountType'],function() {
        Route::get('/get', [\App\Http\Controllers\ApiController::class, 'getAccountTypes']);
    });

    Route::get('/get-alarms', [\App\Http\Controllers\ApiController::class, 'getAlarms']);
    Route::get('/all-data', [\App\Http\Controllers\ApiController::class, 'getAllData']);
    Route::get('/greetings', function() {
        return "Hello there";
    });

    Route::prefix('forgot-password')->group(function () {
        Route::post('/email-verification', [\App\Http\Controllers\ApiController::class, 'verifyEmail']);
        Route::post('/verify-code', [\App\Http\Controllers\ApiController::class, 'verifyCode']);
        Route::post('/reset-password', [\App\Http\Controllers\ApiController::class, 'resetPassword']);
    });

    // Billing Engine API routes (for Quasar app)
    Route::group(['middleware' => ['auth:sanctum'], 'prefix' => 'billing'], function() {
        // Dashboard - comprehensive data for the dashboard page (no hardcoded values)
        Route::get('/dashboard', [\App\Http\Controllers\Api\BillingController::class, 'getDashboard']);
        Route::get('/project/{account}', [\App\Http\Controllers\Api\BillingController::class, 'getProjectedBill']);
        Route::get('/daily-consumption/{meter}', [\App\Http\Controllers\Api\BillingController::class, 'getDailyConsumption']);
        Route::get('/history', [\App\Http\Controllers\Api\BillingController::class, 'getBillingHistoryForUser']);
        Route::get('/history/{account}', [\App\Http\Controllers\Api\BillingController::class, 'getBillingHistory']);
        Route::get('/dates/{account}', [\App\Http\Controllers\Api\BillingController::class, 'getBillingDates']);
        Route::post('/calculate', [\App\Http\Controllers\Api\BillingController::class, 'calculateBill']);
        // Payments
        Route::get('/payments/{account}', [\App\Http\Controllers\Api\BillingController::class, 'getPayments']);
        Route::post('/payments', [\App\Http\Controllers\Api\BillingController::class, 'addPayment']);
    });

    // Tariff info routes (for Quasar app)
    Route::group(['middleware' => ['auth:sanctum'], 'prefix' => 'tariff'], function() {
        Route::get('/{account}', [\App\Http\Controllers\Api\BillingController::class, 'getAccountTariff']);
        Route::get('/{account}/tiers', [\App\Http\Controllers\Api\BillingController::class, 'getTariffTiers']);
    });

    // Account Setup routes (for Quasar app)
    Route::group(['prefix' => 'setup'], function() {
        // Public routes - no auth needed for initial setup
        Route::get('/regions', [\App\Http\Controllers\Api\SetupController::class, 'getRegions']);
        Route::get('/regions/{region}/tariffs', [\App\Http\Controllers\Api\SetupController::class, 'getTariffsForRegion']);
        Route::get('/tariffs/{tariff}', [\App\Http\Controllers\Api\SetupController::class, 'getTariffDetails']);
        Route::get('/tariffs/{tariff}/preview-bill', [\App\Http\Controllers\Api\SetupController::class, 'previewBill']);
    });

    // Account management routes (authenticated)
    Route::group(['middleware' => ['auth:sanctum'], 'prefix' => 'user-account'], function() {
        Route::get('/current', [\App\Http\Controllers\Api\SetupController::class, 'getCurrentAccount']);
        Route::post('/create', [\App\Http\Controllers\Api\SetupController::class, 'createAccount']);
        Route::post('/update', [\App\Http\Controllers\Api\SetupController::class, 'updateAccount']);
        Route::get('/bill', [\App\Http\Controllers\Api\SetupController::class, 'getCurrentBill']);
    });

    // ============================================
    // Admin API routes (admin-only operations)
    // ============================================
    Route::group(['middleware' => ['auth:sanctum'], 'prefix' => 'admin'], function() {
        // Check admin role
        Route::get('/check-role', [\App\Http\Controllers\Api\AdminReadingsController::class, 'checkAdminRole']);
        
        // Reading management (admin only)
        Route::post('/readings/{id}/edit', [\App\Http\Controllers\Api\AdminReadingsController::class, 'editReading']);
        Route::post('/readings/{id}/delete', [\App\Http\Controllers\Api\AdminReadingsController::class, 'deleteReading']);
        Route::post('/readings/add', [\App\Http\Controllers\Api\AdminReadingsController::class, 'addReading']);
        Route::post('/readings/{id}/flags', [\App\Http\Controllers\Api\AdminReadingsController::class, 'setFlags']);
        
        // Meter reading history (admin view)
        Route::get('/meters/{meterId}/readings', [\App\Http\Controllers\Api\AdminReadingsController::class, 'getReadingHistory']);
        
        // Bill recompute (admin only)
        Route::post('/bills/{billId}/recompute', [\App\Http\Controllers\Api\AdminReadingsController::class, 'recomputeBill']);
        Route::post('/accounts/{accountId}/recompute-bill', [\App\Http\Controllers\Api\AdminReadingsController::class, 'recomputeAccountBill']);
        
        // Audit log
        Route::get('/audit-log', [\App\Http\Controllers\Api\AdminReadingsController::class, 'getAuditLog']);
        
        // Undo actions
        Route::post('/actions/{actionId}/undo', [\App\Http\Controllers\Api\AdminReadingsController::class, 'undoAction']);
    });
});

