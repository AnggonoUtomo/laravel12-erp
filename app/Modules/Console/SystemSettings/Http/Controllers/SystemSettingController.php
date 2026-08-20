<?php

namespace App\Modules\Console\SystemSettings\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Console\SystemSettings\Http\Requests\TestEmailSettingRequest;
use App\Modules\Console\SystemSettings\Http\Requests\UpdateBrandingSettingRequest;
use App\Modules\Console\SystemSettings\Http\Requests\UpdateEmailSettingRequest;
use App\Modules\Console\SystemSettings\Http\Requests\UpdateLocalizationSettingRequest;
use App\Modules\Console\SystemSettings\Http\Requests\UpdateMaintenanceModeRequest;
use App\Modules\Console\SystemSettings\Http\Requests\UpdatePaginationSettingRequest;
use App\Modules\Console\SystemSettings\Http\Requests\UpdatePasswordPolicyRequest;
use App\Modules\Console\SystemSettings\Http\Requests\UpdateSecurityPolicyRequest;
use App\Modules\Console\SystemSettings\Services\SystemSettingService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Throwable;

class SystemSettingController extends Controller
{
    public function __construct(
        private readonly SystemSettingService $settings,
    ) {}

    public function index(Request $request): Response
    {
        $this->authorize('view', SystemSettingService::class);

        return Inertia::render('console/system-settings/index', [
            'emailSettings' => $this->settings->emailSettings(),
            'brandingSettings' => $this->settings->brandingSettings(),
            'localizationSettings' => $this->settings->localizationSettings(),
            'paginationSettings' => $this->settings->paginationSettings(),
            'securityPolicy' => $this->settings->securityPolicySettings(),
            'passwordPolicy' => $this->settings->passwordPolicySettings(),
            'maintenanceMode' => $this->settings->maintenanceModeSettings(),
            'systemHealth' => $this->settings->systemHealth(),
            'environmentInfo' => $this->settings->environmentInfo(),
            'can' => [
                'update' => $request->user()?->can('system-settings.update'),
            ],
        ]);
    }

    public function updateEmail(UpdateEmailSettingRequest $request): RedirectResponse
    {
        $this->settings->updateEmailSettings($request->toDto());

        return back()->with('success', 'Konfigurasi email berhasil disimpan.');
    }

    public function updateBranding(UpdateBrandingSettingRequest $request): RedirectResponse
    {
        $this->settings->updateBrandingSettings($request->toDto());

        return back()->with('success', 'Branding aplikasi berhasil disimpan.');
    }

    public function updateLocalization(UpdateLocalizationSettingRequest $request): RedirectResponse
    {
        $this->settings->updateLocalizationSettings($request->toDto());

        return back()->with('success', 'Timezone dan format tanggal berhasil disimpan.');
    }

    public function updatePagination(UpdatePaginationSettingRequest $request): RedirectResponse
    {
        $this->settings->updatePaginationSettings($request->toDto());

        return back()->with('success', 'Default pagination berhasil disimpan.');
    }

    public function updateSecurityPolicy(UpdateSecurityPolicyRequest $request): RedirectResponse
    {
        $this->settings->updateSecurityPolicy($request->toDto());

        return back()->with('success', 'Security policy berhasil disimpan.');
    }

    public function updatePasswordPolicy(UpdatePasswordPolicyRequest $request): RedirectResponse
    {
        $this->settings->updatePasswordPolicy($request->toDto());

        return back()->with('success', 'Password policy berhasil disimpan.');
    }

    public function updateMaintenanceMode(UpdateMaintenanceModeRequest $request): RedirectResponse
    {
        $this->settings->updateMaintenanceMode($request->toDto());

        return back()->with('success', 'Maintenance mode berhasil disimpan.');
    }

    public function testEmail(TestEmailSettingRequest $request): RedirectResponse
    {
        try {
            $this->settings->sendTestEmail($request->validated('recipient'));

            return back()->with('success', 'Email test berhasil dikirim.');
        } catch (Throwable $exception) {
            report($exception);

            return back()->with('error', 'Email test gagal dikirim. Periksa konfigurasi SMTP dan log aplikasi.');
        }
    }
}
