<?php

use App\Modules\Console\SchedulerMonitors\Presentation\Http\Controllers\SchedulerMonitorController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth'])->prefix('scheduler-monitor')->name('scheduler-monitor.')->group(function () {
    Route::get('/', [SchedulerMonitorController::class, 'index'])->name('index');
    Route::post('run', [SchedulerMonitorController::class, 'run'])->name('run');
});
