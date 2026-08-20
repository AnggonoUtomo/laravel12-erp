<?php

namespace App\Modules\Console\ActivityCenters\Services;

use App\Models\User;
use App\Modules\Console\AuditLogs\Infrastructure\Models\AuditLog;
use App\Modules\Console\SystemSettings\Application\Services\SystemSettingService;

class ActivityCenterService
{
    public function __construct(
        private readonly SystemSettingService $settings,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function summaryFor(User $user): array
    {
        $readAt = $user->activity_center_read_at;
        $localization = $this->settings->localizationSettings();
        $dateTimeFormat = $localization['datetime_format'];
        $timezone = $localization['timezone'];

        $items = AuditLog::query()
            ->with('actor:id,name,email')
            ->latest()
            ->limit(8)
            ->get()
            ->map(fn (AuditLog $log) => [
                'id' => $log->id,
                'module' => $log->module,
                'event' => $log->event,
                'description' => $log->description,
                'actor' => $log->actor ? [
                    'id' => $log->actor->id,
                    'name' => $log->actor->name,
                    'email' => $log->actor->email,
                ] : null,
                'created_at' => $log->created_at?->timezone($timezone)->format($dateTimeFormat),
                'created_at_human' => $log->created_at?->diffForHumans(),
                'unread' => $readAt === null || $log->created_at?->gt($readAt),
            ])
            ->values()
            ->all();

        return [
            'unread_count' => $this->unreadCount($user),
            'read_at' => $readAt?->timezone($timezone)->format($dateTimeFormat),
            'items' => $items,
        ];
    }

    public function markAsRead(User $user): void
    {
        $user->forceFill([
            'activity_center_read_at' => now(),
        ])->save();
    }

    private function unreadCount(User $user): int
    {
        $readAt = $user->activity_center_read_at;

        return AuditLog::query()
            ->when($readAt, fn ($query) => $query->where('created_at', '>', $readAt))
            ->count();
    }
}
