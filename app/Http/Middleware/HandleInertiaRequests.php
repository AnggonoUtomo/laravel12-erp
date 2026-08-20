<?php

namespace App\Http\Middleware;

use App\Models\User;
use App\Modules\Console\ActivityCenters\Services\ActivityCenterService;
use App\Modules\Console\SystemSettings\Application\Services\SystemSettingService;
use App\Modules\Console\UserManagements\Application\Services\UserImpersonationService;
use App\Support\Modules\ModuleRegistry;
use Illuminate\Foundation\Inspiring;
use Illuminate\Http\Request;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    /**
     * The root template that's loaded on the first page visit.
     *
     * @see https://inertiajs.com/server-side-setup#root-template
     *
     * @var string
     */
    protected $rootView = 'app';

    /**
     * Determines the current asset version.
     *
     * @see https://inertiajs.com/asset-versioning
     */
    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /**
     * Define the props that are shared by default.
     *
     * @see https://inertiajs.com/shared-data
     *
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        [$message, $author] = str(Inspiring::quotes()->random())->explode('-');
        $user = $request->user();
        $settings = app(SystemSettingService::class);
        $branding = $settings->brandingSettings();

        return array_merge(parent::share($request), [
            'name' => $branding['app_name'],
            'branding' => $branding,
            'localization' => $settings->localizationSettings(),
            'pagination' => $settings->paginationSettings(),
            'navigation' => ModuleRegistry::navigations(),
            'activity_center' => $user && $user->can('activity-center.view')
                ? app(ActivityCenterService::class)->summaryFor($user)
                : [
                    'unread_count' => 0,
                    'read_at' => null,
                    'items' => [],
                ],
            'quote' => ['message' => trim($message), 'author' => trim($author)],
            'flash' => [
                'success' => $request->session()->get('success'),
                'error' => $request->session()->get('error'),
            ],
            'auth' => [
                'user' => $user ? $this->sharedUser($user) : null,
                'roles' => $user ? $user->getUserRoles() : [],
                'permissions' => $user ? $user->getUserPermissions() : [],
                'super' => $user ? $user->isSuperAdmin() : false,
                'impersonation' => [
                    'active' => $request->session()->has(UserImpersonationService::SESSION_IMPERSONATOR_ID),
                    'impersonator' => $request->session()->has(UserImpersonationService::SESSION_IMPERSONATOR_ID)
                        ? [
                            'id' => $request->session()->get(UserImpersonationService::SESSION_IMPERSONATOR_ID),
                            'name' => $request->session()->get(UserImpersonationService::SESSION_IMPERSONATOR_NAME),
                            'email' => $request->session()->get(UserImpersonationService::SESSION_IMPERSONATOR_EMAIL),
                        ]
                        : null,
                ],
            ],
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function sharedUser(User $user): array
    {
        return [
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'avatar' => $user->avatar,
            'email_verified_at' => $user->email_verified_at,
            'created_at' => $user->created_at,
            'updated_at' => $user->updated_at,
        ];
    }
}
