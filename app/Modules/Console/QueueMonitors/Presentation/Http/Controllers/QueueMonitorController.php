<?php

namespace App\Modules\Console\QueueMonitors\Presentation\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Console\QueueMonitors\Application\Services\QueueMonitorService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class QueueMonitorController extends Controller
{
    public function __construct(
        private readonly QueueMonitorService $queues,
    ) {}

    public function index(Request $request): Response
    {
        abort_unless($request->user()?->can('queue-monitor.view'), 403);

        $queue = $request->string('queue')->toString();

        return Inertia::render('console/queue-monitor/index', [
            'overview' => $this->queues->overview(),
            'pendingJobs' => $this->queues->pendingJobs($request),
            'failedJobs' => $this->queues->failedJobs($request),
            'queues' => $this->queues->queues(),
            'filters' => [
                'queue' => $queue ?: null,
                'per_page' => $request->integer('per_page') ?: null,
            ],
            'can' => [
                'manage' => $request->user()?->can('queue-monitor.manage'),
            ],
        ]);
    }

    public function retry(Request $request, string $uuid): RedirectResponse
    {
        abort_unless($request->user()?->can('queue-monitor.manage'), 403);

        $this->queues->retry($uuid);

        return back()->with('success', 'Failed job dikirim ulang ke queue.');
    }

    public function destroy(Request $request, string $uuid): RedirectResponse
    {
        abort_unless($request->user()?->can('queue-monitor.manage'), 403);

        $this->queues->forget($uuid);

        return back()->with('success', 'Failed job berhasil dihapus.');
    }

    public function flush(Request $request): RedirectResponse
    {
        abort_unless($request->user()?->can('queue-monitor.manage'), 403);

        $this->queues->flushFailed();

        return back()->with('success', 'Semua failed jobs berhasil dibersihkan.');
    }
}
