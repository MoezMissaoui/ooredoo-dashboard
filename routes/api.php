<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\DataController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

// Dashboard API routes
Route::prefix('dashboard')->name('api.dashboard.')->group(function () {
    Route::get('/data', [DataController::class, 'getDashboardData'])->name('data');
    Route::get('/operators', [DataController::class, 'getAvailableOperators'])->name('operators');
    Route::get('/partners', [DataController::class, 'getPartnersList'])->name('partners');
    Route::get('/kpis', [DataController::class, 'getKpis'])->name('kpis');
    Route::get('/merchants', [DataController::class, 'getMerchants'])->name('merchants');
    Route::get('/transactions', [DataController::class, 'getTransactions'])->name('transactions');
    Route::get('/subscriptions', [DataController::class, 'getSubscriptions'])->name('subscriptions');
});
