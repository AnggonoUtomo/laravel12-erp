<?php

namespace App\Modules\Console\SchedulerMonitors\Application\Services;

use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

class SchedulerMonitorService
{
    public const HEARTBEAT_CACHE_KEY = 'scheduler-monitor.last_heartbeat';

    /**
     * @return array<string, mixed>
     */
    public function overview(): array
    {
        $events = $this->events();
        $heartbeat = $this->heartbeat();

        return [
            'timezone' => config('app.timezone'),
            'php_binary' => PHP_BINARY,
            'artisan_path' => base_path('artisan'),
            'cron_command' => '* * * * * cd '.base_path().' && '.PHP_BINARY.' artisan schedule:run >> /dev/null 2>&1',
            'tasks' => count($events),
            'due_24h' => collect($events)->where('is_due_soon', true)->count(),
            'heartbeat' => $heartbeat,
        ];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function events(): array
    {
        Artisan::call('schedule:list', [
            '--json' => true,
            '--next' => true,
            '--timezone' => config('app.timezone'),
        ]);

        $decoded = json_decode(Artisan::output(), true);

        if (! is_array($decoded)) {
            return [];
        }

        return collect($decoded)
            ->map(fn (array $event) => $this->normalizeEvent($event))
            ->values()
            ->all();
    }

    public function runDueTasks(): string
    {
        Artisan::call('schedule:run');

        return $this->redactSensitiveText(trim(Artisan::output()) ?: 'Scheduler dijalankan. Tidak ada output dari schedule:run.');
    }

    public function recordHeartbeat(): void
    {
        Cache::put(self::HEARTBEAT_CACHE_KEY, now()->toISOString(), now()->addMinutes(10));
    }

    /**
     * @return array<string, mixed>
     */
    private function heartbeat(): array
    {
        $value = Cache::get(self::HEARTBEAT_CACHE_KEY);
        $lastRunAt = is_string($value) ? Carbon::parse($value) : null;
        $ageSeconds = $lastRunAt ? $lastRunAt->diffInSeconds(now()) : null;

        return [
            'last_run_at' => $lastRunAt?->format('d M Y H:i:s'),
            'age_seconds' => $ageSeconds,
            'status' => match (true) {
                $lastRunAt === null => 'never',
                $ageSeconds <= 120 => 'fresh',
                $ageSeconds <= 600 => 'stale',
                default => 'down',
            },
        ];
    }

    /**
     * @param  array<string, mixed>  $event
     * @return array<string, mixed>
     */
    private function normalizeEvent(array $event): array
    {
        $nextDue = $this->parseDate($event['nextDueDate'] ?? $event['next_due_date'] ?? null);
        $command = (string) ($event['command'] ?? $event['description'] ?? $event['task'] ?? 'Scheduled task');

        return [
            'expression' => (string) ($event['expression'] ?? '-'),
            'command' => $command,
            'description' => Str::limit($command, 140),
            'timezone' => (string) ($event['timezone'] ?? config('app.timezone')),
            'next_due' => $nextDue?->format('d M Y H:i:s'),
            'next_due_human' => $nextDue ? $nextDue->diffForHumans() : '-',
            'is_due_soon' => $nextDue ? $nextDue->lessThanOrEqualTo(now()->addDay()) : false,
        ];
    }

    private function parseDate(mixed $value): ?Carbon
    {
        if (! is_string($value) || $value === '') {
            return null;
        }

        try {
            return Carbon::parse($value);
        } catch (\Throwable) {
            return null;
        }
    }

    private function redactSensitiveText(string $value): string
    {
        return preg_replace(
            '/((?:password|token|secret|api[_-]?key|apikey)\\s*[=:]\\s*)([^\\s,;]+)/i',
            '$1[redacted]',
            $value,
        ) ?? $value;
    }
}
