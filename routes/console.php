<?php

use App\Modules\Console\SchedulerMonitors\Application\Services\SchedulerMonitorService;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::call(fn () => app(SchedulerMonitorService::class)->recordHeartbeat())
    ->name('scheduler-monitor:heartbeat')
    ->everyMinute()
    ->withoutOverlapping();
