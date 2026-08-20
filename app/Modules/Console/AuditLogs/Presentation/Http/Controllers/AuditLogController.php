<?php

namespace App\Modules\Console\AuditLogs\Presentation\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Console\AuditLogs\Infrastructure\Models\AuditLog;
use App\Modules\Console\SystemSettings\Application\Services\SystemSettingService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class AuditLogController extends Controller
{
    public function __construct(
        private readonly SystemSettingService $settings,
    ) {}

    public function index(Request $request): Response
    {
        $this->authorize('viewAny', AuditLog::class);

        $search = $request->string('search')->toString();
        $module = $request->string('module')->toString();
        $event = $request->string('event')->toString();
        $perPage = $this->perPage($request);

        $logs = AuditLog::query()
            ->with('actor:id,name,email')
            ->when($search, function ($query) use ($search) {
                $query->where(function ($query) use ($search) {
                    $query
                        ->where('description', 'like', "%{$search}%")
                        ->orWhere('event', 'like', "%{$search}%")
                        ->orWhere('module', 'like', "%{$search}%")
                        ->orWhereHas('actor', function ($query) use ($search) {
                            $query
                                ->where('name', 'like', "%{$search}%")
                                ->orWhere('email', 'like', "%{$search}%");
                        });
                });
            })
            ->when($module, fn ($query) => $query->where('module', $module))
            ->when($event, fn ($query) => $query->where('event', $event))
            ->latest()
            ->paginate($perPage)
            ->withQueryString()
            ->through(fn (AuditLog $log) => [
                'id' => $log->id,
                'module' => $log->module,
                'event' => $log->event,
                'description' => $log->description,
                'actor' => $log->actor ? [
                    'id' => $log->actor->id,
                    'name' => $log->actor->name,
                    'email' => $log->actor->email,
                ] : null,
                'auditable_type' => $log->auditable_type,
                'auditable_id' => $log->auditable_id,
                'old_values' => $log->old_values,
                'new_values' => $log->new_values,
                'ip_address' => $log->ip_address,
                'user_agent' => $log->user_agent,
                'created_at' => $log->created_at?->format('d M Y H:i'),
            ]);

        return Inertia::render('console/audit-logs/index', [
            'logs' => $logs,
            'modules' => AuditLog::query()->select('module')->distinct()->orderBy('module')->pluck('module')->values(),
            'events' => AuditLog::query()->select('event')->distinct()->orderBy('event')->pluck('event')->values(),
            'filters' => [
                'search' => $search,
                'module' => $module ?: null,
                'event' => $event ?: null,
                'per_page' => $perPage,
            ],
        ]);
    }

    private function perPage(Request $request): int
    {
        return $this->settings->resolvePerPage($request);
    }
}
