<?php

namespace App\Modules\Console\SchedulerMonitors\Presentation\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Console\SchedulerMonitors\Application\Services\SchedulerMonitorService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class SchedulerMonitorController extends Controller
{
    public function __construct(
        private readonly SchedulerMonitorService $scheduler,
    ) {}

    public function index(Request $request): Response
    {
        abort_unless($request->user()?->can('scheduler-monitor.view'), 403);

        return Inertia::render('console/scheduler-monitor/index', [
            'overview' => $this->scheduler->overview(),
            'events' => $this->scheduler->events(),
            'can' => [
                'manage' => $request->user()?->can('scheduler-monitor.manage') ?? false,
            ],
        ]);
    }

    public function run(Request $request): RedirectResponse
    {
        abort_unless($request->user()?->can('scheduler-monitor.manage'), 403);

        $output = $this->scheduler->runDueTasks();

        return back()->with('success', $output);
    }
}
