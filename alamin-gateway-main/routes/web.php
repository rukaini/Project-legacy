<?php

use App\Http\Controllers\AdministratorPasswordResetController;
use App\Http\Controllers\ChangePasswordController;
use App\Http\Controllers\GatewayAuthController;
use App\Http\Controllers\OutsiderRegistrationController;
use App\Http\Controllers\PasswordRecoveryController;
use App\Http\Controllers\RedemptionController;
use App\Http\Controllers\SystemController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    $identity = session('gateway.identity');
    if (! is_array($identity)) {
        return redirect()->route('login');
    }

    return redirect()->route('systems.index');
});
Route::get('/login', [GatewayAuthController::class, 'login'])->name('login');
Route::post('/login', [GatewayAuthController::class, 'authenticate'])->middleware('throttle:gateway-login')->name('login.store');
Route::get('/forgot-password', [PasswordRecoveryController::class, 'create'])->name('password.request');
Route::post('/forgot-password', [PasswordRecoveryController::class, 'store'])
    ->middleware('throttle:password-recovery')
    ->name('password.recovery.store');
Route::get('/administrator/reset-password/{token}', [AdministratorPasswordResetController::class, 'edit'])
    ->middleware('throttle:administrator-password-reset')
    ->name('password.admin.reset');
Route::post('/administrator/reset-password/{token}', [AdministratorPasswordResetController::class, 'update'])
    ->middleware('throttle:administrator-password-reset')
    ->name('password.admin.update');
Route::get('/register', [OutsiderRegistrationController::class, 'create'])->name('register');
Route::post('/register', [OutsiderRegistrationController::class, 'store'])
    ->middleware('throttle:outsider-registration')
    ->name('register.store');

Route::middleware('gateway.auth')->group(function (): void {
    Route::get('/change-password', [ChangePasswordController::class, 'edit'])->name('password.change');
    Route::post('/change-password', [ChangePasswordController::class, 'update'])
        ->middleware('throttle:password-change')
        ->name('password.change.update');
    Route::get('/roles', [GatewayAuthController::class, 'roles'])->name('roles.index');
    Route::post('/roles/select', [GatewayAuthController::class, 'selectRole'])->name('roles.select');
    Route::get('/systems', [SystemController::class, 'index'])->name('systems.index');
    Route::post('/systems/{system}/launch', [SystemController::class, 'launch'])
        ->name('systems.launch');
    Route::post('/logout', [GatewayAuthController::class, 'logout'])->name('logout');
});

Route::post('/sso/redeem', RedemptionController::class)->middleware('throttle:sso-redemption')->name('sso.redeem');
