<?php


use App\Http\Controllers\MeterController;
use Illuminate\Support\Facades\Route;


Route::group(['middleware' => 'auth:sanctum'], function () {
    Route::get('/{meter_id}/cost-estimation', [MeterController::class, 'costEstimation']);
    Route::get('/account/{account_id}/complete-bill', [MeterController::class, 'completeBill']);
});
