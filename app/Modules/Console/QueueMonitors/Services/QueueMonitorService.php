<?php

namespace App\Modules\Console\QueueMonitors\Services;

use App\Modules\Console\SystemSettings\Application\Services\SystemSettingService;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator as Paginator;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class QueueMonitorService
{
    public function __construct(
        private readonly SystemSettingService $settings,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function overview(): array
    {
        $jobsTable = $this->jobsTable();
        $failedTable = $this->failedJobsTable();
        $pending = Schema::hasTable($jobsTable) ? DB::table($jobsTable)->count() : null;
        $reserved = Schema::hasTable($jobsTable) ? DB::table($jobsTable)->whereNotNull('reserved_at')->count() : null;
        $failed = Schema::hasTable($failedTable) ? DB::table($failedTable)->count() : null;

        return [
            'connection' => config('queue.default'),
            'jobs_table' => $jobsTable,
            'failed_table' => $failedTable,
            'pending' => $pending,
            'reserved' => $reserved,
            'failed' => $failed,
        ];
    }

    public function pendingJobs(Request $request): LengthAwarePaginator
    {
        $table = $this->jobsTable();

        if (! Schema::hasTable($table)) {
            return $this->emptyPaginator($request);
        }

        return DB::table($table)
            ->when($request->string('queue')->toString(), fn ($query, string $queue) => $query->where('queue', $queue))
            ->latest('id')
            ->paginate($this->settings->resolvePerPage($request), ['*'], 'pending_page')
            ->withQueryString()
            ->through(fn ($job) => [
                'id' => $job->id,
                'queue' => $job->queue,
                'name' => $this->payloadName($job->payload),
                'attempts' => $job->attempts,
                'reserved_at' => $this->timestamp($job->reserved_at),
                'available_at' => $this->timestamp($job->available_at),
                'created_at' => $this->timestamp($job->created_at),
            ]);
    }

    public function failedJobs(Request $request): LengthAwarePaginator
    {
        $table = $this->failedJobsTable();

        if (! Schema::hasTable($table)) {
            return $this->emptyPaginator($request);
        }

        return DB::table($table)
            ->when($request->string('queue')->toString(), fn ($query, string $queue) => $query->where('queue', $queue))
            ->latest('failed_at')
            ->paginate($this->settings->resolvePerPage($request), ['*'], 'failed_page')
            ->withQueryString()
            ->through(fn ($job) => [
                'id' => $job->id,
                'uuid' => $job->uuid,
                'connection' => $job->connection,
                'queue' => $job->queue,
                'name' => $this->payloadName($job->payload),
                'exception' => Str::limit($this->exceptionSummary($job->exception), 260),
                'failed_at' => Carbon::parse($job->failed_at)->format('d M Y H:i:s'),
            ]);
    }

    /**
     * @return array<int, string>
     */
    public function queues(): array
    {
        $tables = [$this->jobsTable(), $this->failedJobsTable()];

        return collect($tables)
            ->filter(fn (string $table) => Schema::hasTable($table))
            ->flatMap(fn (string $table) => DB::table($table)->select('queue')->distinct()->pluck('queue'))
            ->filter()
            ->unique()
            ->sort()
            ->values()
            ->all();
    }

    public function retry(string $uuid): void
    {
        Artisan::call('queue:retry', ['id' => [$uuid]]);
    }

    public function forget(string $uuid): void
    {
        Artisan::call('queue:forget', ['id' => $uuid]);
    }

    public function flushFailed(): void
    {
        Artisan::call('queue:flush');
    }

    private function jobsTable(): string
    {
        return config('queue.connections.'.config('queue.default').'.table', 'jobs');
    }

    private function failedJobsTable(): string
    {
        return config('queue.failed.table', 'failed_jobs');
    }

    private function payloadName(?string $payload): string
    {
        $decoded = json_decode($payload ?: '', true);

        if (! is_array($decoded)) {
            return 'Unknown job';
        }

        return $decoded['displayName']
            ?? data_get($decoded, 'data.commandName')
            ?? data_get($decoded, 'job')
            ?? 'Queued job';
    }

    private function exceptionSummary(?string $exception): string
    {
        $summary = trim(Str::of($exception ?: 'No exception message.')->explode("\n")->first() ?: 'No exception message.');

        return $this->redactSensitiveText($summary);
    }

    private function redactSensitiveText(string $value): string
    {
        return preg_replace(
            '/((?:password|token|secret|api[_-]?key|apikey)\\s*[=:]\\s*)([^\\s,;]+)/i',
            '$1[redacted]',
            $value,
        ) ?? $value;
    }

    private function timestamp(?int $timestamp): ?string
    {
        return $timestamp ? Carbon::createFromTimestamp($timestamp)->format('d M Y H:i:s') : null;
    }

    private function emptyPaginator(Request $request): LengthAwarePaginator
    {
        $page = Paginator::resolveCurrentPage();

        return (new Paginator([], 0, $this->settings->resolvePerPage($request), $page, [
            'path' => Paginator::resolveCurrentPath(),
        ]))->withQueryString();
    }
}
