<?php

use App\Modules\Console\LoginActivities\Presentation\Http\Controllers\LoginActivityController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth'])->group(function () {
    Route::get('login-activities', [LoginActivityController::class, 'index'])->name('login-activities.index');
});
