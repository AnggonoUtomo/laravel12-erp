<?php

use App\Modules\Console\GlobalSearches\Presentation\Http\Controllers\GlobalSearchController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'throttle:30,1'])
    ->prefix('global-search')
    ->name('global-search.')
    ->group(function () {
        Route::get('/', [GlobalSearchController::class, 'index'])->name('index');
    });
