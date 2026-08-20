<?php

use App\Modules\Console\UserManagements\Presentation\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth'])->group(function () {
    Route::resource('users', UserController::class)->only(['index', 'store', 'update', 'destroy']);
    Route::patch('users/{user}/restore', [UserController::class, 'restore'])->name('users.restore');
    Route::delete('users/{user}/force', [UserController::class, 'forceDestroy'])->name('users.force-destroy');
    Route::post('users/impersonate/stop', [UserController::class, 'stopImpersonating'])->name('users.impersonate.stop');
    Route::post('users/{user}/impersonate', [UserController::class, 'impersonate'])->name('users.impersonate');
});
