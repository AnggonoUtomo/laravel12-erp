@php
    $appName = config('app.name', 'Laravel');
    $message = 'Aplikasi sedang dalam mode maintenance. Silakan coba lagi beberapa saat lagi.';
    $pageStyle = 'aurora';

    try {
        if (class_exists(\App\Modules\Console\SystemSettings\Infrastructure\Models\SystemSetting::class)) {
            $settings = \App\Modules\Console\SystemSettings\Infrastructure\Models\SystemSetting::query()
                ->where('group', 'maintenance_mode')
                ->whereIn('key', ['message', 'page_style'])
                ->pluck('value', 'key');

            if (filled($settings->get('message'))) {
                $message = $settings->get('message');
            }

            if (in_array($settings->get('page_style'), ['aurora', 'operations', 'minimal'], true)) {
                $pageStyle = $settings->get('page_style');
            }
        }
    } catch (Throwable) {
        //
    }

    $formatRetryLabel = function (mixed $seconds): string {
        if (! is_numeric($seconds)) {
            return 'beberapa saat';
        }

        $seconds = (int) $seconds;

        foreach ([
            86400 => 'hari',
            3600 => 'jam',
            60 => 'menit',
            1 => 'detik',
        ] as $unitSeconds => $label) {
            if ($seconds >= $unitSeconds && $seconds % $unitSeconds === 0) {
                return ($seconds / $unitSeconds).' '.$label;
            }
        }

        return $seconds.' detik';
    };

    $retryLabel = isset($retryAfter) && filled($retryAfter) ? $formatRetryLabel($retryAfter) : 'beberapa saat';
@endphp

<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>{{ $appName }} - Maintenance</title>
        <style>
            :root {
                color-scheme: light dark;
                font-family:
                    Inter, ui-sans-serif, system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif;
                --bg: #f6f7f9;
                --panel: rgba(255, 255, 255, 0.86);
                --panel-strong: #ffffff;
                --border: rgba(24, 24, 27, 0.12);
                --text: #18181b;
                --muted: #64748b;
                --accent: #2563eb;
                --accent-soft: rgba(37, 99, 235, 0.12);
                --accent-two: #14b8a6;
                --shadow: 0 28px 90px rgba(15, 23, 42, 0.16);
            }

            * {
                box-sizing: border-box;
            }

            body {
                min-height: 100vh;
                margin: 0;
                overflow-x: hidden;
                background: var(--bg);
                color: var(--text);
            }

            body.aurora {
                --bg: #f7f8fb;
                --accent: #4f46e5;
                --accent-two: #0ea5e9;
                background:
                    radial-gradient(circle at 15% 15%, rgba(79, 70, 229, 0.22), transparent 32rem),
                    radial-gradient(circle at 82% 24%, rgba(20, 184, 166, 0.2), transparent 30rem),
                    linear-gradient(135deg, #f8fafc 0%, #eef2ff 45%, #f8fafc 100%);
            }

            body.operations {
                --bg: #0f172a;
                --panel: rgba(15, 23, 42, 0.82);
                --panel-strong: rgba(30, 41, 59, 0.92);
                --border: rgba(148, 163, 184, 0.24);
                --text: #f8fafc;
                --muted: #cbd5e1;
                --accent: #22c55e;
                --accent-soft: rgba(34, 197, 94, 0.12);
                --accent-two: #38bdf8;
                --shadow: 0 30px 90px rgba(0, 0, 0, 0.38);
                background:
                    linear-gradient(rgba(148, 163, 184, 0.06) 1px, transparent 1px),
                    linear-gradient(90deg, rgba(148, 163, 184, 0.06) 1px, transparent 1px),
                    radial-gradient(circle at 74% 14%, rgba(34, 197, 94, 0.18), transparent 28rem),
                    #0f172a;
                background-size:
                    36px 36px,
                    36px 36px,
                    auto,
                    auto;
            }

            body.minimal {
                --bg: #fafafa;
                --accent: #111827;
                --accent-soft: rgba(17, 24, 39, 0.08);
                --accent-two: #737373;
                background: linear-gradient(180deg, #ffffff 0%, #f4f4f5 100%);
            }

            .page {
                min-height: 100vh;
                display: grid;
                place-items: center;
                padding: 32px;
                position: relative;
            }

            .page::before,
            .page::after {
                content: "";
                position: fixed;
                pointer-events: none;
                border-radius: 999px;
                filter: blur(12px);
                opacity: 0.8;
            }

            body.minimal .page::before,
            body.minimal .page::after {
                display: none;
            }

            .page::before {
                width: 18rem;
                height: 18rem;
                left: -6rem;
                bottom: -4rem;
                background: color-mix(in srgb, var(--accent) 20%, transparent);
            }

            .page::after {
                width: 14rem;
                height: 14rem;
                right: -4rem;
                top: -3rem;
                background: color-mix(in srgb, var(--accent-two) 18%, transparent);
            }

            .shell {
                width: min(100%, 980px);
                display: grid;
                grid-template-columns: minmax(0, 1.1fr) 320px;
                gap: 18px;
                align-items: stretch;
                position: relative;
                z-index: 1;
            }

            .hero,
            .side {
                border: 1px solid var(--border);
                background: var(--panel);
                box-shadow: var(--shadow);
                backdrop-filter: blur(22px);
            }

            .hero {
                min-height: 520px;
                border-radius: 28px;
                padding: clamp(28px, 5vw, 54px);
                display: flex;
                flex-direction: column;
                justify-content: space-between;
                overflow: hidden;
                position: relative;
            }

            .hero::before {
                content: "";
                position: absolute;
                inset: 0;
                background:
                    linear-gradient(135deg, color-mix(in srgb, var(--accent) 10%, transparent), transparent 48%),
                    radial-gradient(circle at 85% 20%, color-mix(in srgb, var(--accent-two) 18%, transparent), transparent 18rem);
                opacity: 0.9;
            }

            body.minimal .hero::before {
                opacity: 0.35;
            }

            .hero > * {
                position: relative;
                z-index: 1;
            }

            .topline {
                display: flex;
                align-items: center;
                justify-content: space-between;
                gap: 16px;
            }

            .brand {
                display: inline-flex;
                align-items: center;
                gap: 10px;
                min-width: 0;
                color: var(--muted);
                font-size: 13px;
                font-weight: 700;
                letter-spacing: 0.08em;
                text-transform: uppercase;
            }

            .mark {
                width: 34px;
                height: 34px;
                border-radius: 11px;
                display: grid;
                place-items: center;
                background: var(--accent-soft);
                color: var(--accent);
                font-weight: 900;
                letter-spacing: 0;
            }

            .badge {
                display: inline-flex;
                align-items: center;
                gap: 8px;
                border: 1px solid var(--border);
                border-radius: 999px;
                padding: 8px 12px;
                background: var(--panel-strong);
                color: var(--muted);
                font-size: 12px;
                font-weight: 700;
                white-space: nowrap;
            }

            .pulse {
                width: 8px;
                height: 8px;
                border-radius: 999px;
                background: var(--accent);
                box-shadow: 0 0 0 6px color-mix(in srgb, var(--accent) 14%, transparent);
            }

            h1 {
                max-width: 700px;
                margin: 72px 0 0;
                font-size: clamp(40px, 7vw, 76px);
                line-height: 0.96;
                letter-spacing: -0.04em;
            }

            .message {
                max-width: 620px;
                margin: 24px 0 0;
                color: var(--muted);
                font-size: clamp(16px, 2vw, 18px);
                line-height: 1.8;
            }

            .meta {
                display: grid;
                grid-template-columns: repeat(3, minmax(0, 1fr));
                gap: 12px;
                margin-top: 42px;
            }

            .meta-card,
            .side-card {
                border: 1px solid var(--border);
                border-radius: 18px;
                background: var(--panel-strong);
                padding: 16px;
            }

            .meta-card span,
            .side-card span {
                display: block;
                color: var(--muted);
                font-size: 12px;
                font-weight: 700;
                letter-spacing: 0.03em;
                text-transform: uppercase;
            }

            .meta-card strong,
            .side-card strong {
                display: block;
                margin-top: 8px;
                font-size: 20px;
            }

            .side {
                border-radius: 24px;
                padding: 20px;
                display: flex;
                flex-direction: column;
                justify-content: space-between;
                gap: 16px;
            }

            .panel-title {
                margin: 0;
                font-size: 16px;
                font-weight: 800;
            }

            .panel-copy {
                margin: 8px 0 0;
                color: var(--muted);
                font-size: 13px;
                line-height: 1.7;
            }

            .steps {
                display: grid;
                gap: 10px;
                margin-top: 18px;
            }

            .step {
                display: flex;
                gap: 10px;
                color: var(--muted);
                font-size: 13px;
                line-height: 1.5;
            }

            .step i {
                width: 22px;
                height: 22px;
                flex: 0 0 22px;
                border-radius: 999px;
                display: grid;
                place-items: center;
                background: var(--accent-soft);
                color: var(--accent);
                font-style: normal;
                font-size: 12px;
                font-weight: 800;
            }

            .progress {
                height: 8px;
                overflow: hidden;
                border-radius: 999px;
                background: color-mix(in srgb, var(--muted) 18%, transparent);
            }

            .progress span {
                display: block;
                width: 68%;
                height: 100%;
                border-radius: inherit;
                background: linear-gradient(90deg, var(--accent), var(--accent-two));
            }

            @media (max-width: 860px) {
                .page {
                    padding: 18px;
                }

                .shell {
                    grid-template-columns: 1fr;
                }

                .hero {
                    min-height: auto;
                    border-radius: 22px;
                }

                .side {
                    border-radius: 20px;
                }

                .topline {
                    align-items: flex-start;
                    flex-direction: column;
                }

                h1 {
                    margin-top: 54px;
                }

                .meta {
                    grid-template-columns: 1fr;
                }
            }

            @media (prefers-color-scheme: dark) {
                body.aurora,
                body.minimal {
                    --bg: #09090b;
                    --panel: rgba(24, 24, 27, 0.82);
                    --panel-strong: rgba(39, 39, 42, 0.9);
                    --border: rgba(250, 250, 250, 0.12);
                    --text: #fafafa;
                    --muted: #a1a1aa;
                    --shadow: 0 30px 90px rgba(0, 0, 0, 0.34);
                    background:
                        radial-gradient(circle at 16% 18%, rgba(79, 70, 229, 0.22), transparent 28rem),
                        radial-gradient(circle at 82% 20%, rgba(20, 184, 166, 0.16), transparent 26rem),
                        #09090b;
                }
            }
        </style>
    </head>
    <body class="{{ $pageStyle }}">
        <main class="page">
            <section class="shell" aria-label="Maintenance mode">
                <article class="hero">
                    <div>
                        <div class="topline">
                            <div class="brand">
                                <span class="mark">{{ \Illuminate\Support\Str::upper(\Illuminate\Support\Str::substr($appName, 0, 1)) }}</span>
                                <span>{{ $appName }}</span>
                            </div>
                            <div class="badge"><span class="pulse"></span> Maintenance aktif</div>
                        </div>

                        <h1>Sistem sedang ditingkatkan.</h1>
                        <p class="message">{{ $message }}</p>
                    </div>

                    <div class="meta">
                        <div class="meta-card">
                            <span>Status</span>
                            <strong>Terjadwal</strong>
                        </div>
                        <div class="meta-card">
                            <span>Coba Lagi</span>
                            <strong>{{ $retryLabel }}</strong>
                        </div>
                        <div class="meta-card">
                            <span>Akses</span>
                            <strong>Terbatas</strong>
                        </div>
                    </div>
                </article>

                <aside class="side">
                    <div>
                        <div class="side-card">
                            <span>Informasi</span>
                            <p class="panel-title">Perawatan sistem sedang berjalan</p>
                            <p class="panel-copy">
                                Tim sedang melakukan pembaruan agar aplikasi tetap stabil, aman, dan nyaman digunakan.
                            </p>
                        </div>

                        <div class="steps">
                            <div class="step"><i>1</i><span>Layanan sementara dibatasi untuk pengguna umum.</span></div>
                            <div class="step"><i>2</i><span>Admin dapat memakai secret bypass jika sudah disiapkan.</span></div>
                            <div class="step"><i>3</i><span>Halaman akan kembali normal setelah maintenance selesai.</span></div>
                        </div>
                    </div>

                    <div class="side-card">
                        <span>Progress</span>
                        <strong>Dalam proses</strong>
                        <p class="panel-copy">Silakan kembali dalam {{ $retryLabel }}.</p>
                        <div class="progress" aria-hidden="true"><span></span></div>
                    </div>
                </aside>
            </section>
        </main>
    </body>
</html>
