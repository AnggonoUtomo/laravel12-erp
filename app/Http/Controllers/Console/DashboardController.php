<?php

namespace App\Http\Controllers\Console;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Modules\Console\AuditLogs\Infrastructure\Models\AuditLog;
use App\Modules\Console\LoginActivities\Models\LoginActivity;
use App\Support\Modules\ModulePermissionRegistry;
use App\Support\Modules\ModuleRegistry;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use App\Models\Role;

class DashboardController extends Controller
{
    public function __invoke(Request $request): Response
    {
        $user = $request->user();
        $canViewConsoleAdmin = $user?->hasAnyPermission(['access-control.view', 'roles.manage', 'users.view']) ?? false;
        $canViewAudit = $user?->can('audit-logs.view') ?? false;
        $canViewLoginActivities = $user?->can('login-activities.view') ?? false;

        return Inertia::render('console/dashboard', [
            'dashboard' => [
                'access' => [
                    'console_admin' => $canViewConsoleAdmin,
                    'audit' => $canViewAudit,
                    'login_activities' => $canViewLoginActivities,
                ],
                'console' => $this->consoleMetrics($canViewConsoleAdmin, count($user?->getUserPermissions() ?? [])),
                'activity' => $this->activityMetrics($canViewAudit, $canViewLoginActivities),
            ],
        ]);
    }

    /**
     * @return array<string, int>
     */
    private function consoleMetrics(bool $canViewConsoleAdmin, int $effectivePermissionCount): array
    {
        $modules = ModuleRegistry::modules();

        return [
            'modules' => $modules->count(),
            'console_modules' => $modules->where('project', 'Console')->count(),
            'permissions' => $canViewConsoleAdmin ? count(ModulePermissionRegistry::permissions()) : $effectivePermissionCount,
            'roles' => $canViewConsoleAdmin ? Role::query()->count() : 0,
            'users' => $canViewConsoleAdmin ? User::query()->count() : 0,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function activityMetrics(bool $canViewAudit, bool $canViewLoginActivities): array
    {
        return [
            'audit_logs' => $canViewAudit ? AuditLog::query()->count() : 0,
            'login_activities' => $canViewLoginActivities ? LoginActivity::query()->count() : 0,
            'recent_audit_logs' => $canViewAudit ? AuditLog::query()
                ->latest()
                ->limit(5)
                ->get(['id', 'module', 'event', 'description', 'created_at'])
                ->map(fn (AuditLog $log): array => [
                    'id' => $log->id,
                    'module' => $log->module,
                    'event' => $log->event,
                    'description' => $log->description,
                    'created_at_human' => $log->created_at?->diffForHumans(),
                ])
                ->all() : [],
            'recent_login_activities' => $canViewLoginActivities ? LoginActivity::query()
                ->latest('occurred_at')
                ->limit(3)
                ->get(['id', 'email', 'event', 'successful', 'occurred_at'])
                ->map(fn (LoginActivity $activity): array => [
                    'id' => $activity->id,
                    'email' => $activity->email,
                    'event' => $activity->event,
                    'successful' => $activity->successful,
                    'occurred_at_human' => $activity->occurred_at?->diffForHumans(),
                ])
                ->all() : [],
        ];
    }
}
