<?php

namespace App\Modules\Console\LoginActivities\Presentation\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Console\LoginActivities\Infrastructure\Models\LoginActivity;
use App\Modules\Console\SystemSettings\Application\Services\SystemSettingService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class LoginActivityController extends Controller
{
    public function __construct(
        private readonly SystemSettingService $settings,
    ) {}

    public function index(Request $request): Response
    {
        $this->authorize('viewAny', LoginActivity::class);

        $search = $request->string('search')->toString();
        $event = $request->string('event')->toString();
        $status = $request->string('status')->toString();
        $perPage = $this->settings->resolvePerPage($request);

        $activities = LoginActivity::query()
            ->with('user:id,name,email')
            ->when($search, function ($query) use ($search) {
                $query->where(function ($query) use ($search) {
                    $query
                        ->where('email', 'like', "%{$search}%")
                        ->orWhere('ip_address', 'like', "%{$search}%")
                        ->orWhere('message', 'like', "%{$search}%")
                        ->orWhereHas('user', function ($query) use ($search) {
                            $query
                                ->where('name', 'like', "%{$search}%")
                                ->orWhere('email', 'like', "%{$search}%");
                        });
                });
            })
            ->when($event, fn ($query) => $query->where('event', $event))
            ->when($status === 'success', fn ($query) => $query->where('successful', true))
            ->when($status === 'failed', fn ($query) => $query->where('successful', false))
            ->latest('occurred_at')
            ->paginate($perPage)
            ->withQueryString()
            ->through(fn (LoginActivity $activity) => [
                'id' => $activity->id,
                'user' => $activity->user ? [
                    'id' => $activity->user->id,
                    'name' => $activity->user->name,
                    'email' => $activity->user->email,
                ] : null,
                'email' => $activity->email,
                'event' => $activity->event,
                'successful' => $activity->successful,
                'ip_address' => $activity->ip_address,
                'device' => $activity->device,
                'browser' => $activity->browser,
                'platform' => $activity->platform,
                'message' => $activity->message,
                'user_agent' => $activity->user_agent,
                'occurred_at' => $activity->occurred_at?->format('d M Y H:i'),
            ]);

        return Inertia::render('console/login-activities/index', [
            'activities' => $activities,
            'summary' => [
                'total' => LoginActivity::query()->count(),
                'success' => LoginActivity::query()->where('successful', true)->count(),
                'failed' => LoginActivity::query()->where('successful', false)->count(),
                'today' => LoginActivity::query()->whereDate('occurred_at', today())->count(),
            ],
            'events' => LoginActivity::query()->select('event')->distinct()->orderBy('event')->pluck('event')->values(),
            'filters' => [
                'search' => $search,
                'event' => $event ?: null,
                'status' => $status ?: null,
                'per_page' => $perPage,
            ],
        ]);
    }
}
