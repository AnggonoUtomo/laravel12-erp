<?php

namespace App\Modules\Console\SystemSettings\Application\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Throwable;

class SystemHealthService
{
    public function systemHealth(): array
    {
        $checks = [
            $this->healthCheck('Database', 'Koneksi database dapat menerima query.', function () {
                $startedAt = microtime(true);

                DB::select('select 1');

                return [
                    'value' => config('database.default'),
                    'meta' => round((microtime(true) - $startedAt) * 1000, 2).' ms',
                ];
            }),
            $this->healthCheck('Cache', 'Cache dapat menulis dan membaca nilai sementara.', function () {
                $key = 'system-health:'.Str::uuid();

                Cache::put($key, 'ok', now()->addMinute());
                $ok = Cache::get($key) === 'ok';
                Cache::forget($key);

                if (! $ok) {
                    throw new \RuntimeException('Cache value mismatch.');
                }

                return [
                    'value' => config('cache.default'),
                    'meta' => 'write/read ok',
                ];
            }),
            $this->healthCheck('Storage', 'Folder storage dan cache aplikasi dapat ditulis.', function () {
                $paths = [
                    storage_path('framework/cache'),
                    storage_path('framework/sessions'),
                    storage_path('framework/views'),
                    storage_path('logs'),
                ];

                $blocked = collect($paths)
                    ->filter(fn (string $path) => ! is_dir($path) || ! is_writable($path))
                    ->values();

                if ($blocked->isNotEmpty()) {
                    throw new \RuntimeException('Path tidak writable: '.$blocked->implode(', '));
                }

                return [
                    'value' => 'Writable',
                    'meta' => count($paths).' paths checked',
                ];
            }),
            $this->healthCheck('Storage Link', 'Public storage link untuk asset upload tersedia.', function () {
                $link = public_path('storage');

                if (! file_exists($link)) {
                    throw new \RuntimeException('Jalankan php artisan storage:link.');
                }

                return [
                    'value' => is_link($link) ? 'Linked' : 'Exists',
                    'meta' => $link,
                ];
            }),
            $this->healthCheck('Queue', 'Konfigurasi queue dan jumlah pekerjaan tertunda.', function () {
                $connection = config('queue.default');
                $table = config("queue.connections.{$connection}.table", 'jobs');
                $pending = null;

                if ($connection === 'database' && Schema::hasTable($table)) {
                    $pending = DB::table($table)->count();
                }

                return [
                    'value' => $connection,
                    'meta' => $pending === null ? 'pending tidak tersedia' : "{$pending} pending jobs",
                ];
            }),
            $this->healthCheck('Failed Jobs', 'Jumlah job queue yang gagal.', function () {
                $table = config('queue.failed.table', 'failed_jobs');
                $failed = Schema::hasTable($table) ? DB::table($table)->count() : null;

                return [
                    'value' => $failed === null ? 'Unavailable' : "{$failed} failed",
                    'meta' => $failed === null ? "table {$table} tidak ditemukan" : "table {$table}",
                    'status' => $failed === 0 ? 'ok' : 'warning',
                ];
            }),
            $this->healthCheck('Mail', 'Mailer aktif dan alamat pengirim tersedia.', function () {
                $fromAddress = config('mail.from.address');

                if (blank($fromAddress)) {
                    throw new \RuntimeException('mail.from.address kosong.');
                }

                return [
                    'value' => config('mail.default'),
                    'meta' => $fromAddress,
                ];
            }),
            $this->healthCheck('Maintenance Runtime', 'Status runtime maintenance Laravel.', fn () => [
                'value' => app()->isDownForMaintenance() ? 'Down' : 'Live',
                'meta' => storage_path('framework/down'),
                'status' => app()->isDownForMaintenance() ? 'warning' : 'ok',
            ]),
            $this->healthCheck('App Key', 'APP_KEY tersedia untuk enkripsi session dan cookie.', function () {
                if (blank(config('app.key'))) {
                    throw new \RuntimeException('APP_KEY belum diset.');
                }

                return [
                    'value' => 'Configured',
                    'meta' => config('app.env'),
                ];
            }),
        ];

        $statusCounts = collect($checks)->countBy('status');

        return [
            'summary' => [
                'status' => ($statusCounts->get('error', 0) > 0) ? 'error' : (($statusCounts->get('warning', 0) > 0) ? 'warning' : 'ok'),
                'ok' => $statusCounts->get('ok', 0),
                'warning' => $statusCounts->get('warning', 0),
                'error' => $statusCounts->get('error', 0),
                'checked_at' => now()->format('d M Y H:i:s'),
            ],
            'runtime' => [
                'app_env' => config('app.env'),
                'app_debug' => (bool) config('app.debug'),
                'app_url' => config('app.url'),
                'php_version' => PHP_VERSION,
                'laravel_version' => app()->version(),
                'timezone' => config('app.timezone'),
                'database' => config('database.default'),
                'cache' => config('cache.default'),
                'queue' => config('queue.default'),
                'filesystem' => config('filesystems.default'),
            ],
            'checks' => $checks,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function environmentInfo(): array
    {
        return [
            'summary' => [
                'mode' => 'Read-only',
                'generated_at' => now()->format('d M Y H:i:s'),
                'notice' => 'Informasi ini hanya untuk diagnosis. Secret, key, token, dan password tidak ditampilkan.',
            ],
            'groups' => [
                [
                    'title' => 'Application',
                    'description' => 'Konfigurasi runtime utama aplikasi.',
                    'items' => [
                        ['label' => 'App Name', 'value' => config('app.name')],
                        ['label' => 'Environment', 'value' => config('app.env')],
                        ['label' => 'Debug Mode', 'value' => config('app.debug') ? 'Enabled' : 'Disabled'],
                        ['label' => 'App URL', 'value' => config('app.url')],
                        ['label' => 'Timezone', 'value' => config('app.timezone')],
                        ['label' => 'Locale', 'value' => config('app.locale')],
                        ['label' => 'Maintenance', 'value' => app()->isDownForMaintenance() ? 'Down' : 'Live'],
                    ],
                ],
                [
                    'title' => 'Runtime',
                    'description' => 'Versi framework dan batasan PHP yang sedang aktif.',
                    'items' => [
                        ['label' => 'Laravel Version', 'value' => app()->version()],
                        ['label' => 'PHP Version', 'value' => PHP_VERSION],
                        ['label' => 'Server Software', 'value' => request()->server('SERVER_SOFTWARE') ?: PHP_SAPI],
                        ['label' => 'Memory Limit', 'value' => ini_get('memory_limit')],
                        ['label' => 'Max Execution Time', 'value' => ini_get('max_execution_time').' seconds'],
                        ['label' => 'Upload Max Filesize', 'value' => ini_get('upload_max_filesize')],
                        ['label' => 'Post Max Size', 'value' => ini_get('post_max_size')],
                    ],
                ],
                [
                    'title' => 'Drivers',
                    'description' => 'Driver Laravel yang sedang digunakan oleh aplikasi.',
                    'items' => [
                        ['label' => 'Database', 'value' => config('database.default')],
                        ['label' => 'Cache', 'value' => config('cache.default')],
                        ['label' => 'Queue', 'value' => config('queue.default')],
                        ['label' => 'Session', 'value' => config('session.driver')],
                        ['label' => 'Filesystem', 'value' => config('filesystems.default')],
                        ['label' => 'Mail', 'value' => config('mail.default')],
                        ['label' => 'Broadcast', 'value' => config('broadcasting.default')],
                    ],
                ],
                [
                    'title' => 'Paths',
                    'description' => 'Lokasi penting untuk debugging lokal dan deployment.',
                    'items' => [
                        ['label' => 'Base Path', 'value' => base_path()],
                        ['label' => 'Public Path', 'value' => public_path()],
                        ['label' => 'Storage Path', 'value' => storage_path()],
                        ['label' => 'Log Path', 'value' => storage_path('logs')],
                    ],
                ],
                [
                    'title' => 'PHP Extensions',
                    'description' => 'Extension penting yang umum dibutuhkan Laravel dan modul upload/media.',
                    'items' => collect(['pdo', 'openssl', 'mbstring', 'tokenizer', 'xml', 'ctype', 'json', 'fileinfo', 'curl', 'zip', 'gd', 'intl', 'exif'])
                        ->map(fn (string $extension) => [
                            'label' => $extension,
                            'value' => extension_loaded($extension) ? 'Loaded' : 'Missing',
                            'status' => extension_loaded($extension) ? 'ok' : 'warning',
                        ])
                        ->values()
                        ->all(),
                ],
            ],
        ];
    }

    private function healthCheck(string $name, string $description, callable $callback): array
    {
        try {
            $result = $callback();

            return [
                'name' => $name,
                'description' => $description,
                'status' => $result['status'] ?? 'ok',
                'value' => $result['value'] ?? 'OK',
                'meta' => $result['meta'] ?? null,
                'message' => $result['message'] ?? 'OK',
            ];
        } catch (Throwable $exception) {
            return [
                'name' => $name,
                'description' => $description,
                'status' => 'error',
                'value' => 'Error',
                'meta' => null,
                'message' => $exception->getMessage(),
            ];
        }
    }
}
