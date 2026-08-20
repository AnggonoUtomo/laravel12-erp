<?php

use App\Modules\Console\QueueMonitors\Presentation\Http\Controllers\QueueMonitorController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth'])->prefix('queue-monitor')->name('queue-monitor.')->group(function () {
    Route::get('/', [QueueMonitorController::class, 'index'])->name('index');
    Route::post('failed/{uuid}/retry', [QueueMonitorController::class, 'retry'])->name('failed.retry');
    Route::delete('failed/{uuid}', [QueueMonitorController::class, 'destroy'])->name('failed.destroy');
    Route::delete('failed', [QueueMonitorController::class, 'flush'])->name('failed.flush');
});
