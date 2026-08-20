<?php

namespace App\Modules\Console\ActivityCenters\Presentation\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Console\ActivityCenters\Application\Services\ActivityCenterService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class ActivityCenterController extends Controller
{
    public function __construct(
        private readonly ActivityCenterService $activityCenter,
    ) {}

    public function markAsRead(Request $request): RedirectResponse
    {
        abort_unless($request->user()?->can('activity-center.view'), 403);

        $this->activityCenter->markAsRead($request->user());

        return back();
    }
}
