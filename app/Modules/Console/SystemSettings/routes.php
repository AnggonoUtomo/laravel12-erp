<?php

use App\Modules\Console\SystemSettings\Http\Controllers\SystemSettingController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth'])->prefix('system-settings')->name('system-settings.')->group(function () {
    Route::get('/', [SystemSettingController::class, 'index'])->name('index');
    Route::put('email', [SystemSettingController::class, 'updateEmail'])->name('email.update');
    Route::post('email/test', [SystemSettingController::class, 'testEmail'])->name('email.test');
    Route::put('branding', [SystemSettingController::class, 'updateBranding'])->name('branding.update');
    Route::put('localization', [SystemSettingController::class, 'updateLocalization'])->name('localization.update');
    Route::put('pagination', [SystemSettingController::class, 'updatePagination'])->name('pagination.update');
    Route::put('security-policy', [SystemSettingController::class, 'updateSecurityPolicy'])->name('security-policy.update');
    Route::put('password-policy', [SystemSettingController::class, 'updatePasswordPolicy'])->name('password-policy.update');
    Route::put('maintenance-mode', [SystemSettingController::class, 'updateMaintenanceMode'])->name('maintenance-mode.update');
});
