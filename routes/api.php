<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\DataController;
use App\Http\Controllers\Api\EklektikController;

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

// API pour récupérer les opérateurs
Route::middleware('auth')->get('/operators', [\App\Http\Controllers\Api\OperatorsController::class, 'getOperators'])->name('api.operators');

// Dashboard API routes
Route::prefix('dashboard')->name('api.dashboard.')->group(function () {
    Route::get('/data', [DataController::class, 'getDashboardData'])->name('data');
    Route::get('/operators', [DataController::class, 'getUserOperators'])->name('operators');
    Route::get('/partners', [DataController::class, 'getPartnersList'])->name('partners');
    Route::get('/kpis', [DataController::class, 'getKpis'])->name('kpis');
    Route::get('/merchants', [DataController::class, 'getMerchants'])->name('merchants');
    Route::get('/transactions', [DataController::class, 'getTransactions'])->name('transactions');
    Route::get('/subscriptions', [DataController::class, 'getSubscriptions'])->name('subscriptions');
});

// Eklektik API routes - Contrôleur consolidé (sans auth pour test)
Route::prefix('eklektik')->name('api.eklektik.')->group(function () {
    Route::get('/numbers', [EklektikController::class, 'getDashboardData'])->name('numbers');
    Route::get('/dashboard-data', [EklektikController::class, 'getDashboardData'])->name('dashboard-data');
    Route::get('/test', [EklektikController::class, 'getDashboardData'])->name('test');
});

// Routes optimisées additionnelles si présentes
if (file_exists(base_path('routes/api_optimized.php'))) {
    require base_path('routes/api_optimized.php');
}
