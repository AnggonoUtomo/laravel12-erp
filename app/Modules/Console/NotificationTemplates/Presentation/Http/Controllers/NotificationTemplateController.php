<?php

namespace App\Modules\Console\NotificationTemplates\Presentation\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Console\NotificationTemplates\Application\Services\NotificationTemplateService;
use App\Modules\Console\NotificationTemplates\Infrastructure\Models\NotificationTemplate;
use App\Modules\Console\NotificationTemplates\Presentation\Http\Requests\UpdateNotificationTemplateRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class NotificationTemplateController extends Controller
{
    public function __construct(
        private readonly NotificationTemplateService $templates,
    ) {}

    public function index(Request $request): Response
    {
        $this->authorize('viewAny', NotificationTemplate::class);

        return Inertia::render('console/notification-templates/index', [
            'templates' => $this->templates->templates()->map(fn (NotificationTemplate $template) => [
                'id' => $template->id,
                'key' => $template->key,
                'name' => $template->name,
                'channel' => $template->channel,
                'subject' => $template->subject,
                'body' => $template->body,
                'variables' => $template->variables ?? [],
                'active' => $template->active,
                'updated_at' => $template->updated_at?->diffForHumans(),
            ]),
            'can' => [
                'update' => $request->user()?->can('notification-templates.update') ?? false,
            ],
        ]);
    }

    public function update(UpdateNotificationTemplateRequest $request, NotificationTemplate $notificationTemplate): RedirectResponse
    {
        $this->templates->update($notificationTemplate, $request->validated());

        return back()->with('success', 'Notification template berhasil disimpan.');
    }
}
