<?php

use App\Modules\Console\AccessControls\Presentation\Http\Controllers\AccessControlController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth'])->prefix('access-control')->name('access-control.')->group(function () {
    Route::get('/', [AccessControlController::class, 'index'])->name('index');
    Route::post('roles', [AccessControlController::class, 'storeRole'])->name('roles.store');
    Route::put('roles/{role}', [AccessControlController::class, 'updateRole'])->name('roles.update');
    Route::put('roles/{role}/permissions', [AccessControlController::class, 'syncPermissions'])->name('roles.permissions.sync');
    Route::delete('roles/{role}', [AccessControlController::class, 'destroyRole'])->name('roles.destroy');
    Route::post('permissions', [AccessControlController::class, 'storePermission'])->name('permissions.store');
    Route::delete('permissions/{permission}', [AccessControlController::class, 'destroyPermission'])->name('permissions.destroy');
});
