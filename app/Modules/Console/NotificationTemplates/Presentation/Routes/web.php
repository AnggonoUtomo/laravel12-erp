<?php

use App\Modules\Console\NotificationTemplates\Presentation\Http\Controllers\NotificationTemplateController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth'])->prefix('notification-templates')->name('notification-templates.')->group(function () {
    Route::get('/', [NotificationTemplateController::class, 'index'])->name('index');
    Route::put('{notificationTemplate}', [NotificationTemplateController::class, 'update'])->name('update');
});
