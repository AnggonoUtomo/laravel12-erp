@php
    $appName = config('app.name', 'Laravel');
    $homeUrl = url('/');
    $previousUrl = url()->previous();
    $isPreviousUseful = $previousUrl && $previousUrl !== url()->current();
@endphp

<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>{{ $appName }} - Halaman Tidak Ditemukan</title>
        <style>
            :root {
                color-scheme: light dark;
                font-family: Inter, ui-sans-serif, system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif;
                --bg: #f7f8fb;
                --card: rgba(255, 255, 255, 0.88);
                --card-strong: #ffffff;
                --border: rgba(15, 23, 42, 0.12);
                --text: #111827;
                --muted: #64748b;
                --primary: #0f766e;
                --primary-soft: rgba(15, 118, 110, 0.12);
                --warning: #d97706;
                --shadow: 0 28px 90px rgba(15, 23, 42, 0.16);
            }

            * {
                box-sizing: border-box;
            }

            body {
                min-height: 100vh;
                margin: 0;
                background:
                    radial-gradient(circle at 16% 18%, rgba(20, 184, 166, 0.18), transparent 28rem),
                    radial-gradient(circle at 82% 18%, rgba(14, 165, 233, 0.16), transparent 26rem),
                    linear-gradient(135deg, #f8fafc 0%, #eefdf8 46%, #f8fafc 100%);
                color: var(--text);
            }

            .page {
                min-height: 100vh;
                display: grid;
                place-items: center;
                padding: 28px;
            }

            .shell {
                width: min(100%, 1040px);
                display: grid;
                grid-template-columns: minmax(0, 1fr) 340px;
                gap: 18px;
                align-items: stretch;
            }

            .hero,
            .side {
                border: 1px solid var(--border);
                background: var(--card);
                box-shadow: var(--shadow);
                backdrop-filter: blur(22px);
            }

            .hero {
                min-height: 560px;
                border-radius: 28px;
                padding: clamp(28px, 5vw, 56px);
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
                    linear-gradient(135deg, rgba(15, 118, 110, 0.12), transparent 50%),
                    radial-gradient(circle at 84% 22%, rgba(245, 158, 11, 0.16), transparent 16rem);
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

            .brand,
            .badge,
            .route-box,
            .side-card {
                border: 1px solid var(--border);
                background: var(--card-strong);
            }

            .brand {
                display: inline-flex;
                align-items: center;
                gap: 10px;
                border-radius: 999px;
                padding: 8px 12px;
                color: var(--muted);
                font-size: 13px;
                font-weight: 800;
            }

            .mark {
                width: 28px;
                height: 28px;
                display: grid;
                place-items: center;
                border-radius: 9px;
                background: var(--primary-soft);
                color: var(--primary);
                font-weight: 900;
            }

            .badge {
                display: inline-flex;
                align-items: center;
                gap: 8px;
                border-radius: 999px;
                padding: 9px 13px;
                color: var(--muted);
                font-size: 12px;
                font-weight: 800;
            }

            .dot {
                width: 8px;
                height: 8px;
                border-radius: 999px;
                background: var(--warning);
                box-shadow: 0 0 0 6px rgba(217, 119, 6, 0.14);
            }

            .code {
                margin: 68px 0 0;
                color: var(--primary);
                font-size: 14px;
                font-weight: 900;
                letter-spacing: 0.16em;
                text-transform: uppercase;
            }

            h1 {
                max-width: 760px;
                margin: 14px 0 0;
                font-size: clamp(42px, 7vw, 78px);
                line-height: 0.96;
                letter-spacing: -0.04em;
            }

            .message {
                max-width: 640px;
                margin: 24px 0 0;
                color: var(--muted);
                font-size: clamp(16px, 2vw, 18px);
                line-height: 1.8;
            }

            .route-box {
                margin-top: 42px;
                border-radius: 18px;
                padding: 16px;
            }

            .route-box span {
                display: block;
                color: var(--muted);
                font-size: 12px;
                font-weight: 800;
                letter-spacing: 0.05em;
                text-transform: uppercase;
            }

            .route-box code {
                display: block;
                margin-top: 8px;
                overflow-wrap: anywhere;
                color: var(--text);
                font-size: 14px;
            }

            .actions {
                display: flex;
                flex-wrap: wrap;
                gap: 10px;
                margin-top: 28px;
            }

            .btn {
                display: inline-flex;
                align-items: center;
                justify-content: center;
                min-height: 42px;
                border-radius: 12px;
                padding: 0 16px;
                text-decoration: none;
                font-size: 14px;
                font-weight: 800;
            }

            .btn-primary {
                background: var(--primary);
                color: white;
            }

            .btn-secondary {
                border: 1px solid var(--border);
                background: var(--card-strong);
                color: var(--text);
            }

            .side {
                border-radius: 24px;
                padding: 18px;
                display: grid;
                gap: 14px;
            }

            .side-card {
                border-radius: 18px;
                padding: 18px;
            }

            .side-card span {
                display: block;
                color: var(--muted);
                font-size: 12px;
                font-weight: 900;
                text-transform: uppercase;
                letter-spacing: 0.05em;
            }

            .side-card strong {
                display: block;
                margin-top: 9px;
                font-size: 18px;
            }

            .side-card p {
                margin: 8px 0 0;
                color: var(--muted);
                font-size: 13px;
                line-height: 1.7;
            }

            .checklist {
                display: grid;
                gap: 10px;
                margin-top: 12px;
            }

            .checklist div {
                display: flex;
                gap: 10px;
                color: var(--muted);
                font-size: 13px;
                line-height: 1.5;
            }

            .checklist i {
                width: 22px;
                height: 22px;
                flex: 0 0 22px;
                display: grid;
                place-items: center;
                border-radius: 999px;
                background: var(--primary-soft);
                color: var(--primary);
                font-style: normal;
                font-weight: 900;
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

                .topline {
                    align-items: flex-start;
                    flex-direction: column;
                }
            }

            @media (prefers-color-scheme: dark) {
                :root {
                    --bg: #09090b;
                    --card: rgba(24, 24, 27, 0.84);
                    --card-strong: rgba(39, 39, 42, 0.94);
                    --border: rgba(250, 250, 250, 0.12);
                    --text: #fafafa;
                    --muted: #a1a1aa;
                    --primary: #2dd4bf;
                    --primary-soft: rgba(45, 212, 191, 0.12);
                    --shadow: 0 30px 90px rgba(0, 0, 0, 0.34);
                }

                body {
                    background:
                        radial-gradient(circle at 16% 18%, rgba(20, 184, 166, 0.2), transparent 28rem),
                        radial-gradient(circle at 82% 20%, rgba(245, 158, 11, 0.13), transparent 26rem),
                        #09090b;
                }
            }
        </style>
    </head>
    <body>
        <main class="page">
            <section class="shell" aria-label="Halaman tidak ditemukan">
                <article class="hero">
                    <div>
                        <div class="topline">
                            <div class="brand">
                                <span class="mark">{{ \Illuminate\Support\Str::upper(\Illuminate\Support\Str::substr($appName, 0, 1)) }}</span>
                                <span>{{ $appName }}</span>
                            </div>
                            <div class="badge"><span class="dot"></span> 404 Not Found</div>
                        </div>

                        <p class="code">Route tidak tersedia</p>
                        <h1>Halaman ini belum punya alamat yang valid.</h1>
                        <p class="message">
                            Link yang dibuka mungkin sudah berubah, module belum aktif, atau URL diketik tidak sesuai route yang tersedia.
                        </p>

                        <div class="route-box">
                            <span>URL yang diminta</span>
                            <code>{{ request()->getRequestUri() }}</code>
                        </div>

                        <div class="actions">
                            <a class="btn btn-primary" href="{{ $homeUrl }}">Kembali ke Launcher</a>
                            @if ($isPreviousUseful)
                                <a class="btn btn-secondary" href="{{ $previousUrl }}">Kembali ke Halaman Sebelumnya</a>
                            @endif
                        </div>
                    </div>
                </article>

                <aside class="side">
                    <div class="side-card">
                        <span>Yang bisa dicek</span>
                        <strong>Pastikan route dan module aktif</strong>
                        <div class="checklist">
                            <div><i>1</i><span>Periksa apakah module sudah punya `routes.php`.</span></div>
                            <div><i>2</i><span>Pastikan module aktif di `module.php`.</span></div>
                            <div><i>3</i><span>Gunakan launcher untuk masuk ke project yang tersedia.</span></div>
                        </div>
                    </div>

                    <div class="side-card">
                        <span>Project aktif</span>
                        <strong>Console</strong>
                        <p>Gunakan launcher atau `/dashboard` untuk kembali ke Console.</p>
                    </div>

                    <div class="side-card">
                        <span>Bantuan developer</span>
                        <strong>Route list</strong>
                        <p>Jalankan `php artisan route:list` untuk melihat route yang tersedia di aplikasi.</p>
                    </div>
                </aside>
            </section>
        </main>
    </body>
</html>
