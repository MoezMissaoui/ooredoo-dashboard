<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\Auth\AuthController;
use App\Http\Controllers\Auth\InvitationController;
use App\Http\Controllers\Admin\UserManagementController;
use App\Http\Controllers\Api\DataController;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "web" middleware group. Make something great!
|
*/

// Routes d'authentification (publiques)
Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'showLogin'])->name('auth.login');
    Route::post('/login', [AuthController::class, 'login']);
    
    Route::get('/otp/request', [AuthController::class, 'showOtpRequest'])->name('auth.otp.request');
    Route::post('/otp/send', [AuthController::class, 'sendOtp'])->name('auth.otp.send');
    Route::get('/otp/verify', [AuthController::class, 'showOtpVerify'])->name('auth.otp.verify');
    Route::post('/otp/verify', [AuthController::class, 'verifyOtp']);
    Route::post('/otp/resend', [AuthController::class, 'resendOtp'])->name('auth.otp.resend');
    

});

// Route de déconnexion (protégée)
Route::post('/logout', [AuthController::class, 'logout'])->middleware('auth')->name('auth.logout');

// Routes d'invitation (accessibles même si connecté)
Route::get('/invitation/{token}', [AuthController::class, 'processInvitation'])->name('auth.invitation');
Route::post('/invitation/accept', [InvitationController::class, 'acceptInvitation'])->name('auth.invitation.accept');

// Dashboard routes (protégées par authentification)
Route::middleware('auth')->group(function () {
    Route::get('/', [DashboardController::class, 'index'])->name('dashboard');
    Route::get('/dashboard', [DashboardController::class, 'dashboard'])->name('dashboard.view');
            Route::get('/dashboard/config', [DashboardController::class, 'getConfig'])->name('dashboard.config');
        
        // API routes pour données dashboard
        Route::get('/api/operators', [DataController::class, 'getUserOperators'])->name('api.user.operators');
        Route::get('/api/dashboard/data', [DataController::class, 'getDashboardData'])->name('api.dashboard.data');
        Route::get('/api/dashboard/operators', [DataController::class, 'getAvailableOperators'])->name('api.dashboard.operators');
        Route::get('/api/dashboard/partners', [DataController::class, 'getPartnersList'])->name('api.dashboard.partners');
        Route::get('/api/dashboard/kpis', [DataController::class, 'getKpis'])->name('api.dashboard.kpis');
        Route::get('/api/dashboard/merchants', [DataController::class, 'getMerchants'])->name('api.dashboard.merchants');
        Route::get('/api/dashboard/transactions', [DataController::class, 'getTransactions'])->name('api.dashboard.transactions');
        Route::get('/api/dashboard/subscriptions', [DataController::class, 'getSubscriptions'])->name('api.dashboard.subscriptions');
    
    // Routes d'administration (Super Admin et Admin uniquement)
    Route::middleware(['auth', 'role:super_admin,admin'])->prefix('admin')->name('admin.')->group(function () {
        Route::get('/users', [UserManagementController::class, 'index'])->name('users.index');
        Route::get('/users/create', [UserManagementController::class, 'create'])->name('users.create');
        Route::post('/users', [UserManagementController::class, 'store'])->name('users.store');
        Route::get('/users/{user}/edit', [UserManagementController::class, 'edit'])->name('users.edit');
        Route::put('/users/{user}', [UserManagementController::class, 'update'])->name('users.update');
        Route::delete('/users/{user}', [UserManagementController::class, 'destroy'])->name('users.destroy');
        
        // Invitations
        Route::get('/invitations', [InvitationController::class, 'index'])->name('invitations.index');
        Route::get('/invitations/create', [InvitationController::class, 'create'])->name('invitations.create');
        Route::post('/invitations', [InvitationController::class, 'store'])->name('invitations.store');
        Route::post('/invitations/{invitation}/resend', [InvitationController::class, 'resend'])->name('invitations.resend');
        Route::patch('/invitations/{invitation}/cancel', [InvitationController::class, 'cancel'])->name('invitations.cancel');
        Route::delete('/invitations/{invitation}', [InvitationController::class, 'destroy'])->name('invitations.destroy');
    });
});

Route::get('/test', function () {
    return view('welcome');
})->name('test');
