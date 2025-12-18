<?php


use Illuminate\Support\Facades\Route;
$namespace = 'App\Http\Controllers';
Route::prefix('api/v1/meters')->middleware('web')->namespace($namespace)->group(base_path('routes/api/meters.php'));
