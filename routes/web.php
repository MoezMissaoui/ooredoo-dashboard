<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\Auth\AuthController;
use App\Http\Controllers\Auth\InvitationController;
use App\Http\Controllers\Auth\PasswordController;
use App\Http\Controllers\Admin\UserManagementController;
use App\Http\Controllers\Admin\EklektikCronController;
use App\Http\Controllers\SubStoreController;
use App\Http\Controllers\Api\DataController;
use App\Http\Controllers\EklektikSyncController;

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
    
    // Routes de gestion des mots de passe
    Route::get('/password/forgot', [PasswordController::class, 'showForgotPasswordForm'])->name('password.forgot');
    Route::post('/password/send-reset', [PasswordController::class, 'sendResetLink'])->name('password.send-reset');
    Route::get('/password/reset/{token}', [PasswordController::class, 'showResetForm'])->name('password.reset.form');
    Route::post('/password/reset', [PasswordController::class, 'resetPassword'])->name('password.reset');
    Route::get('/password/first-login/{token}', [PasswordController::class, 'showFirstLoginForm'])->name('password.first-login');
    Route::post('/password/first-login', [PasswordController::class, 'processFirstLogin'])->name('password.first-login.process');

});

// Route de déconnexion (protégée)
Route::post('/logout', [AuthController::class, 'logout'])->middleware('auth')->name('auth.logout');

// Routes d'invitation (accessibles même si connecté)
Route::get('/invitation/{token}', [AuthController::class, 'processInvitation'])->name('auth.invitation');

// Route de test des graphiques Eklektik
// Routes de test supprimées - graphiques Eklektik intégrés au dashboard principal
Route::post('/invitation/accept', [InvitationController::class, 'acceptInvitation'])->name('auth.invitation.accept');

// Dashboard routes (protégées par authentification)
Route::middleware('auth')->group(function () {
    // Route principale avec redirection intelligente selon le rôle
    Route::get('/', [DashboardController::class, 'index'])->name('dashboard');
    Route::get('/dashboard', [DashboardController::class, 'dashboard'])->name('dashboard.view');
    Route::get('/dashboard/config', [DashboardController::class, 'getConfig'])->name('dashboard.config');
    
    // Dashboard Opérateur (accès restreint)
    Route::middleware(['dashboard.access:operator'])->prefix('operator')->name('operator.')->group(function () {
        Route::get('/', [DashboardController::class, 'dashboard'])->name('dashboard');
    });
    
    // Routes de gestion des mots de passe (utilisateur connecté)
    Route::get('/password/change', [PasswordController::class, 'showChangePasswordForm'])->name('password.change');
    Route::post('/password/change', [PasswordController::class, 'changePassword']);
        
        // API routes pour données dashboard
        Route::get('/api/operators', [DataController::class, 'getUserOperators'])->name('api.user.operators');
        Route::get('/api/dashboard/data', [DataController::class, 'getDashboardData'])->name('api.dashboard.data');
        Route::get('/api/dashboard/operators', [DataController::class, 'getAvailableOperators'])->name('api.dashboard.operators');
        Route::get('/api/dashboard/partners', [DataController::class, 'getPartnersList'])->name('api.dashboard.partners');
        Route::get('/api/dashboard/kpis', [DataController::class, 'getKpis'])->name('api.dashboard.kpis');
        Route::get('/api/dashboard/merchants', [DataController::class, 'getMerchants'])->name('api.dashboard.merchants');
        Route::get('/api/dashboard/transactions', [DataController::class, 'getTransactions'])->name('api.dashboard.transactions');
        Route::get('/api/dashboard/subscriptions', [DataController::class, 'getSubscriptions'])->name('api.dashboard.subscriptions');
    
    // Dashboard Sub-Stores (accès restreint)
    Route::middleware(['auth', 'dashboard.access:sub-store'])->prefix('sub-stores')->name('sub-stores.')->group(function () {
        Route::get('/', [SubStoreController::class, 'index'])->name('dashboard');
        Route::get('/api/sub-stores', [SubStoreController::class, 'getSubStores'])->name('api.sub-stores');
        Route::get('/api/dashboard/data', [SubStoreController::class, 'getDashboardData'])->name('api.dashboard.data');
        // Endpoint asynchrone pour expirations (léger et mis en cache)
        Route::get('/api/expirations', [SubStoreController::class, 'getExpirationsAsync'])->name('api.expirations');
    });

    // Routes d'administration (Super Admin et Admin uniquement)
    Route::middleware(['auth', 'dashboard.access:admin'])->prefix('admin')->name('admin.')->group(function () {
        Route::get('/users', [UserManagementController::class, 'index'])->name('users.index');
        Route::get('/users/create', [UserManagementController::class, 'create'])->name('users.create');
        Route::post('/users', [UserManagementController::class, 'store'])->name('users.store');
        Route::get('/users/{user}/edit', [UserManagementController::class, 'edit'])->name('users.edit');
        Route::put('/users/{user}', [UserManagementController::class, 'update'])->name('users.update');
        Route::delete('/users/{user}', [UserManagementController::class, 'destroy'])->name('users.destroy');
        
        // Actions supplémentaires pour les utilisateurs
        Route::post('/users/{user}/reset-password', [UserManagementController::class, 'resetPassword'])->name('users.reset-password');
        Route::post('/users/{user}/suspend', [UserManagementController::class, 'suspend'])->name('users.suspend');
        Route::post('/users/{user}/unsuspend', [UserManagementController::class, 'unsuspend'])->name('users.unsuspend');
        
        // Invitations
        Route::get('/invitations', [InvitationController::class, 'index'])->name('invitations.index');
        Route::get('/invitations/create', [InvitationController::class, 'create'])->name('invitations.create');
        Route::post('/invitations', [InvitationController::class, 'store'])->name('invitations.store');
        Route::post('/invitations/{invitation}/resend', [InvitationController::class, 'resend'])->name('invitations.resend');
        Route::patch('/invitations/{invitation}/cancel', [InvitationController::class, 'cancel'])->name('invitations.cancel');
        Route::delete('/invitations/{invitation}', [InvitationController::class, 'destroy'])->name('invitations.destroy');
        
        // Configuration du Cron Eklektik (Super Admin seulement)
        Route::middleware('role:super_admin')->group(function () {
            Route::get('/eklektik-cron', [EklektikCronController::class, 'index'])->name('admin.eklektik-cron');
            Route::get('/eklektik-cron/config', [EklektikCronController::class, 'index'])->name('admin.eklektik-cron.config');
            Route::post('/eklektik-cron/config', [EklektikCronController::class, 'updateConfig'])->name('admin.eklektik-cron.update');
            Route::post('/eklektik-cron/test', [EklektikCronController::class, 'testCron'])->name('admin.eklektik-cron.test');
            Route::post('/eklektik-cron/run', [EklektikCronController::class, 'runCron'])->name('admin.eklektik-cron.run');
            Route::post('/eklektik-cron/reset', [EklektikCronController::class, 'resetToDefault'])->name('admin.eklektik-cron.reset');
            Route::get('/eklektik-cron/statistics', [EklektikCronController::class, 'getCronStatus'])->name('admin.eklektik-cron.statistics');
        });
        
        // Gestion des Synchronisations Eklektik (Super Admin et Admin)
        Route::get('/eklektik-sync', [EklektikSyncController::class, 'index'])->name('eklektik.sync');
        Route::post('/eklektik-sync', [EklektikSyncController::class, 'sync'])->name('eklektik.sync');
        Route::get('/eklektik-sync/status', [EklektikSyncController::class, 'status'])->name('eklektik.status');
        Route::get('/eklektik-sync/logs', [EklektikSyncController::class, 'logs'])->name('eklektik.logs');
        
        // Dashboard Eklektik Intégré (Super Admin et Admin)
        Route::get('/eklektik-dashboard', function() {
            return view('eklektik.dashboard');
        })->name('eklektik.dashboard');
    });
});

Route::get('/test', function () {
    return view('welcome');
})->name('test');
