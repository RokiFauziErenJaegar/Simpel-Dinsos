<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Display Lobi · Dinas Sosial Pringsewu</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Plus+Jakarta+Sans:wght@700;800;900&display=swap" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js" defer></script>
    <script src="https://cdn.jsdelivr.net/npm/laravel-echo@1.16.1/dist/echo.iife.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/pusher-js@8.4.0/dist/web/pusher.min.js"></script>
    <style>
        :root {
            --brand: #1E4D8C;
            --brand-deep: #0a1f4a;
            --emerald: #2DB67C;
            --amber: #fbbf24;
            --navy: #050b1c;
        }
        * { margin: 0; padding: 0; box-sizing: border-box; }
        html, body { height: 100vh; width: 100vw; overflow: hidden; }
        body {
            font-family: 'Inter', system-ui, sans-serif;
            color: #fff;
            background: #050b1c;
            position: relative;
        }
        h1, h2, h3, h4 { font-family: 'Plus Jakarta Sans', system-ui, sans-serif; }
        [x-cloak] { display: none !important; }

        /* === Background mesh === */
        .bg-mesh {
            position: fixed; inset: 0; z-index: -2;
            background:
                radial-gradient(circle at 25% 25%, rgba(30, 77, 140, 0.55), transparent 45%),
                radial-gradient(circle at 75% 75%, rgba(45, 182, 124, 0.40), transparent 45%),
                linear-gradient(135deg, #050b1c 0%, #0a1f4a 50%, #050b1c 100%);
            animation: meshShift 20s ease-in-out infinite alternate;
        }
        @keyframes meshShift {
            from { background-position: 0% 0%, 100% 100%, 0% 0%; }
            to   { background-position: 30% 20%, 70% 80%, 0% 0%; }
        }

        /* === Main layout grid === */
        .tv-root {
            display: grid;
            grid-template-rows: auto 1fr auto auto;
            height: 100vh;
            gap: 16px;
            padding: 16px;
        }

        /* === Header === */
        .tv-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 16px 28px;
            background: rgba(255, 255, 255, 0.05);
            border: 1px solid rgba(255, 255, 255, 0.08);
            border-radius: 24px;
            backdrop-filter: blur(12px);
        }
        .tv-brand { display: flex; align-items: center; gap: 18px; }
        .tv-logo {
            width: 64px; height: 64px;
            background: linear-gradient(135deg, var(--brand) 0%, var(--emerald) 100%);
            border-radius: 18px;
            display: flex; align-items: center; justify-content: center;
            font-size: 2.2rem; font-weight: 900; color: white;
            box-shadow: 0 8px 24px rgba(45, 182, 124, 0.3);
        }
        .tv-title { font-size: 1.6rem; font-weight: 800; letter-spacing: -0.02em; }
        .tv-title em { color: #6ee7b7; font-style: normal; }
        .tv-subtitle { font-size: 0.95rem; color: rgba(255, 255, 255, 0.65); margin-top: 2px; }
        .tv-subtitle b { color: var(--amber); font-weight: 700; }

        .tv-clock { text-align: right; }
        .clock-day { font-size: 1rem; font-weight: 800; color: var(--amber); letter-spacing: 0.2em; text-transform: uppercase; }
        .clock-time {
            font-family: 'Plus Jakarta Sans', sans-serif;
            font-size: 3.2rem; font-weight: 900; line-height: 1;
            letter-spacing: -0.04em;
            font-variant-numeric: tabular-nums;
            margin-top: 2px;
        }
        .clock-date { font-size: 1rem; color: rgba(255, 255, 255, 0.7); font-weight: 500; margin-top: 4px; }

        /* === Main content grid === */
        .tv-main {
            display: grid;
            grid-template-columns: 2fr 1fr;
            gap: 16px;
            min-height: 0;
        }

        /* === LEFT: Antrian hero === */
        .panel {
            background: rgba(255, 255, 255, 0.05);
            border: 1px solid rgba(255, 255, 255, 0.08);
            border-radius: 24px;
            backdrop-filter: blur(12px);
            padding: 22px 28px;
        }
        .panel-title {
            display: flex; align-items: center; gap: 12px;
            font-size: 1rem; font-weight: 800;
            letter-spacing: 0.25em; text-transform: uppercase;
            color: rgba(255, 255, 255, 0.85);
        }
        .panel-title::before {
            content: '';
            width: 10px; height: 10px;
            background: var(--emerald);
            border-radius: 50%;
            box-shadow: 0 0 16px var(--emerald);
            animation: pulse-dot 2s ease-in-out infinite;
        }
        @keyframes pulse-dot {
            0%, 100% { transform: scale(1); box-shadow: 0 0 16px var(--emerald); }
            50%      { transform: scale(1.3); box-shadow: 0 0 28px var(--emerald); }
        }
        .live-badge {
            display: inline-flex; align-items: center; gap: 8px;
            padding: 4px 14px; border-radius: 999px;
            background: rgba(239, 68, 68, 0.15); border: 1px solid rgba(239, 68, 68, 0.4);
            font-size: 0.8rem; font-weight: 800; letter-spacing: 0.15em;
            color: #fca5a5;
        }
        .live-badge::before {
            content: ''; width: 8px; height: 8px; background: #ef4444; border-radius: 50%;
            animation: blink 1s steps(2) infinite;
        }
        @keyframes blink { 50% { opacity: 0.3; } }

        /* Ticket cards */
        .ticket-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 18px;
            margin-top: 18px;
            flex: 1;
        }
        .ticket-card {
            background: linear-gradient(165deg, #ffffff 0%, #e2e8f0 100%);
            color: var(--brand-deep);
            border-radius: 28px;
            padding: 18px 12px;
            display: flex; flex-direction: column; justify-content: center; align-items: center;
            box-shadow:
                0 24px 60px rgba(45, 182, 124, 0.25),
                0 12px 32px rgba(0, 0, 0, 0.4);
            animation: breathe 4s ease-in-out infinite;
            position: relative;
            overflow: hidden;
            min-height: 0;
        }
        .ticket-card.empty { opacity: 0.35; animation: none; }
        .ticket-card::before {
            content: '';
            position: absolute; inset: -50%;
            background: conic-gradient(from 0deg,
                transparent 0deg, rgba(45, 182, 124, 0.12) 60deg, transparent 120deg,
                rgba(30, 77, 140, 0.12) 240deg, transparent 360deg);
            animation: rotate 10s linear infinite;
            z-index: 0;
        }
        .ticket-card > * { position: relative; z-index: 1; }
        .ticket-label {
            font-size: 0.8rem; font-weight: 700;
            letter-spacing: 0.25em; text-transform: uppercase;
            color: #64748b;
            margin-bottom: 4px;
        }
        .ticket-number {
            font-family: 'Plus Jakarta Sans', sans-serif;
            font-size: clamp(3.5rem, 7vw, 6.5rem);
            font-weight: 900;
            line-height: 0.95;
            letter-spacing: -0.05em;
            background: linear-gradient(135deg, var(--brand) 0%, var(--emerald) 100%);
            -webkit-background-clip: text;
            background-clip: text;
            color: transparent;
        }
        .ticket-counter {
            margin-top: 10px;
            padding: 6px 18px;
            background: linear-gradient(135deg, var(--brand), var(--emerald));
            color: white;
            font-weight: 800;
            letter-spacing: 0.15em;
            border-radius: 999px;
            font-size: 0.95rem;
        }
        .ticket-card.empty .ticket-number {
            background: linear-gradient(135deg, #94a3b8, #cbd5e1);
            -webkit-background-clip: text; background-clip: text;
        }
        .ticket-card.empty .ticket-counter { background: #94a3b8; }
        @keyframes breathe {
            0%, 100% { transform: scale(1); }
            50%      { transform: scale(1.02); }
        }
        @keyframes rotate {
            from { transform: rotate(0deg); }
            to   { transform: rotate(360deg); }
        }

        .empty-state {
            display: flex; flex-direction: column; align-items: center; justify-content: center;
            color: rgba(255, 255, 255, 0.5);
            text-align: center;
            flex: 1;
        }
        .empty-state-icon { font-size: 5rem; margin-bottom: 12px; opacity: 0.6; }
        .empty-state-title { font-size: 1.6rem; font-weight: 700; }
        .empty-state-sub { font-size: 0.95rem; margin-top: 6px; color: rgba(255, 255, 255, 0.4); }

        /* Next queue */
        .next-queue {
            display: flex; align-items: center; gap: 12px; flex-wrap: wrap;
            margin-top: 16px;
        }
        .num-pill {
            min-width: 110px;
            padding: 14px 22px;
            background: linear-gradient(180deg, rgba(255,255,255,0.10), rgba(255,255,255,0.03));
            border: 1px solid rgba(255,255,255,0.18);
            border-radius: 18px;
            font-family: 'Plus Jakarta Sans', sans-serif;
            font-size: 1.8rem; font-weight: 800;
            color: white;
            text-align: center;
        }
        .num-pill.next {
            background: linear-gradient(180deg, rgba(251,191,36,0.35), rgba(251,191,36,0.10));
            border-color: rgba(251,191,36,0.6);
            color: #fde68a;
            box-shadow: 0 0 24px rgba(251, 191, 36, 0.2);
        }
        .next-more {
            padding: 12px 18px;
            color: rgba(255,255,255,0.5);
            font-size: 1.1rem; font-weight: 600;
        }
        .next-empty {
            padding: 14px 0;
            color: rgba(255,255,255,0.4);
            font-size: 1.2rem;
        }

        /* === RIGHT: Side info === */
        .side {
            display: grid;
            grid-template-rows: auto auto 1fr;
            gap: 16px;
            min-height: 0;
        }

        .stats-row {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 10px;
            margin-top: 14px;
        }
        .stat-box {
            background: rgba(255, 255, 255, 0.04);
            border: 1px solid rgba(255, 255, 255, 0.08);
            border-radius: 16px;
            padding: 14px 10px;
            text-align: center;
        }
        .stat-value {
            font-family: 'Plus Jakarta Sans', sans-serif;
            font-size: 2.5rem; font-weight: 900; line-height: 1;
            background: linear-gradient(135deg, #fff, #cbd5e1);
            -webkit-background-clip: text; background-clip: text; color: transparent;
            transition: transform 0.3s ease;
        }
        .stat-value.bump { animation: bumpEffect 0.6s ease; }
        @keyframes bumpEffect {
            0%   { transform: scale(1); }
            50%  { transform: scale(1.3); color: var(--amber); }
            100% { transform: scale(1); }
        }
        .stat-label {
            font-size: 0.7rem;
            color: rgba(255, 255, 255, 0.55);
            text-transform: uppercase;
            letter-spacing: 0.1em;
            font-weight: 700;
            margin-top: 4px;
        }

        .qr-panel {
            background: white;
            color: var(--brand-deep);
            border-radius: 24px;
            padding: 16px 20px;
            display: flex; align-items: center; gap: 18px;
        }
        .qr-image {
            width: 110px; height: 110px;
            background: white;
            border: 3px solid #e2e8f0;
            border-radius: 14px;
            padding: 4px;
            flex-shrink: 0;
        }
        .qr-image svg { width: 100%; height: 100%; }
        .qr-label-top { font-size: 0.7rem; font-weight: 800; color: #64748b; letter-spacing: 0.2em; text-transform: uppercase; }
        .qr-label-big { font-size: 1.4rem; font-weight: 900; color: var(--brand); margin-top: 4px; letter-spacing: -0.02em; }
        .qr-domain { font-size: 0.85rem; color: #64748b; margin-top: 6px; }
        .qr-gratis {
            display: inline-block;
            margin-top: 8px;
            padding: 4px 10px;
            background: #d1fae5; color: #047857;
            border-radius: 999px;
            font-size: 0.75rem; font-weight: 800; letter-spacing: 0.05em;
        }

        .info-grid {
            display: grid;
            grid-template-columns: 1fr;
            gap: 12px;
            min-height: 0;
        }
        .motto-panel {
            background: linear-gradient(135deg, var(--brand) 0%, var(--emerald) 100%);
            border-radius: 22px;
            padding: 18px 22px;
            text-align: center;
            position: relative;
            overflow: hidden;
        }
        .motto-care {
            font-family: 'Plus Jakarta Sans', sans-serif;
            font-size: 3rem; font-weight: 900;
            letter-spacing: 0.05em;
            text-shadow: 0 4px 20px rgba(0, 0, 0, 0.3);
            line-height: 1;
        }
        .motto-words { font-size: 0.85rem; font-weight: 700; letter-spacing: 0.15em; color: rgba(255,255,255,0.95); margin-top: 8px; }
        .motto-quote { font-size: 1rem; font-style: italic; color: rgba(255,255,255,0.95); margin-top: 10px; }

        .info-panel {
            background: rgba(255, 255, 255, 0.05);
            border: 1px solid rgba(255, 255, 255, 0.08);
            border-radius: 22px;
            padding: 16px 22px;
        }
        .info-row { display: flex; align-items: center; gap: 12px; padding: 8px 0; }
        .info-row + .info-row { border-top: 1px solid rgba(255, 255, 255, 0.06); }
        .info-icon { font-size: 1.4rem; width: 32px; text-align: center; }
        .info-label { font-size: 0.7rem; color: rgba(255, 255, 255, 0.55); text-transform: uppercase; letter-spacing: 0.1em; font-weight: 700; }
        .info-value { font-size: 1.05rem; font-weight: 800; color: var(--amber); line-height: 1.1; margin-top: 2px; }

        /* === Marquee footer === */
        .tv-marquee {
            background: rgba(0, 0, 0, 0.35);
            border: 1px solid rgba(255, 255, 255, 0.06);
            border-radius: 16px;
            padding: 12px 0;
            overflow: hidden;
            position: relative;
            -webkit-mask-image: linear-gradient(90deg, transparent, black 4%, black 96%, transparent);
            mask-image: linear-gradient(90deg, transparent, black 4%, black 96%, transparent);
        }
        .marquee-track {
            display: inline-block;
            white-space: nowrap;
            animation: marquee 60s linear infinite;
            padding-left: 100%;
            font-size: 1.05rem;
            color: rgba(255, 255, 255, 0.85);
            font-weight: 500;
        }
        .marquee-track b { color: var(--amber); font-weight: 800; }
        .marquee-track .ok { color: #6ee7b7; font-weight: 800; }
        @keyframes marquee {
            from { transform: translateX(0); }
            to   { transform: translateX(-100%); }
        }
        .marquee-sep { margin: 0 30px; }

        /* === Pop-up overlay panggilan === */
        .call-overlay {
            position: fixed; inset: 0; z-index: 100;
            display: none;
            align-items: center; justify-content: center;
            backdrop-filter: blur(8px);
            background: radial-gradient(circle at center, rgba(45, 182, 124, 0.92) 0%, rgba(30, 77, 140, 0.95) 70%, rgba(5, 11, 28, 0.98) 100%);
        }
        .call-overlay.show { display: flex; animation: overlayIn 0.4s ease-out; }
        @keyframes overlayIn { from { opacity: 0; } to { opacity: 1; } }
        .call-content { text-align: center; animation: callPop 0.6s cubic-bezier(0.34, 1.56, 0.64, 1); }
        @keyframes callPop {
            0%   { transform: scale(0.5); opacity: 0; }
            60%  { transform: scale(1.05); opacity: 1; }
            100% { transform: scale(1); }
        }
        .call-overline { color: var(--amber); font-weight: 800; letter-spacing: 0.25em; font-size: 1.4rem; text-transform: uppercase; }
        .call-number {
            font-family: 'Plus Jakarta Sans', sans-serif;
            font-size: clamp(8rem, 22vw, 18rem);
            font-weight: 900;
            line-height: 0.85;
            letter-spacing: -0.05em;
            color: white;
            text-shadow: 0 20px 80px rgba(0, 0, 0, 0.5), 0 0 60px rgba(255, 255, 255, 0.3);
            margin: 16px 0;
        }
        .call-arrow { font-size: 4rem; animation: arrowBounce 1s ease-in-out infinite; }
        @keyframes arrowBounce {
            0%, 100% { transform: translateY(0); }
            50%      { transform: translateY(20px); }
        }
        .call-direction { font-size: 2rem; font-weight: 900; text-transform: uppercase; letter-spacing: 0.1em; margin-top: 6px; }
        .call-counter {
            display: inline-block;
            margin-top: 16px;
            padding: 16px 40px;
            background: white;
            color: var(--brand-deep);
            font-size: 3.5rem;
            font-weight: 900;
            border-radius: 24px;
            box-shadow: 0 24px 60px rgba(0, 0, 0, 0.4);
        }

        /* === Audio banner === */
        .audio-banner {
            position: fixed; bottom: 24px; right: 24px; z-index: 90;
            padding: 14px 22px;
            background: linear-gradient(135deg, var(--amber), #f59e0b);
            color: #1a1a1a;
            border-radius: 14px;
            font-weight: 800; font-size: 0.95rem;
            box-shadow: 0 12px 40px rgba(251, 191, 36, 0.4);
            cursor: pointer;
            display: flex; align-items: center; gap: 10px;
            animation: bannerPulse 2s ease-in-out infinite;
        }
        @keyframes bannerPulse {
            0%, 100% { transform: translateY(0); }
            50%      { transform: translateY(-3px); box-shadow: 0 18px 50px rgba(251, 191, 36, 0.6); }
        }
    </style>
</head>
<body x-data="tvDisplay()" x-init="init()">

<div class="bg-mesh"></div>

<div class="tv-root">

    {{-- ========== HEADER ========== --}}
    <header class="tv-header">
        <div class="tv-brand">
            <div class="tv-logo">D</div>
            <div>
                <div class="tv-title">DINAS SOSIAL <em>PRINGSEWU</em></div>
                <div class="tv-subtitle">Pelayanan <b>CARE</b> · Cepat · Adaptif · Responsif · Empati</div>
            </div>
        </div>
        <div class="tv-clock">
            <div class="clock-day" x-text="serverDay">—</div>
            <div class="clock-time" x-text="serverTime">--:--:--</div>
            <div class="clock-date" x-text="serverDateRest">—</div>
        </div>
    </header>

    {{-- ========== MAIN ========== --}}
    <main class="tv-main">

        {{-- LEFT --}}
        <section class="panel" style="display: flex; flex-direction: column;">
            <div style="display: flex; align-items: center; justify-content: space-between;">
                <div class="panel-title">SEDANG DILAYANI</div>
                <div class="live-badge">LIVE</div>
            </div>

            {{-- Tickets --}}
            <template x-if="nowServing.length > 0">
                <div class="ticket-grid">
                    <template x-for="i in 3" :key="i">
                        <template x-if="nowServing[i-1]">
                            <div class="ticket-card">
                                <div class="ticket-label">Loket</div>
                                <div class="ticket-number" x-text="nowServing[i-1].ticket_number"></div>
                                <div class="ticket-counter" x-text="nowServing[i-1].counter || 'LOKET 1'"></div>
                            </div>
                        </template>
                    </template>
                    {{-- Fill empty slots --}}
                    <template x-for="i in Math.max(0, 3 - nowServing.length)" :key="'e'+i">
                        <div class="ticket-card empty">
                            <div class="ticket-label">Loket</div>
                            <div class="ticket-number">—</div>
                            <div class="ticket-counter">KOSONG</div>
                        </div>
                    </template>
                </div>
            </template>

            <template x-if="nowServing.length === 0">
                <div class="empty-state">
                    <div class="empty-state-icon">☕</div>
                    <div class="empty-state-title">Belum ada antrian dilayani</div>
                    <div class="empty-state-sub">Silakan ambil nomor di kiosk lobi</div>
                </div>
            </template>

            {{-- Next queue --}}
            <div style="margin-top: 18px;">
                <div style="display: flex; align-items: center; justify-content: space-between;">
                    <div class="panel-title" style="font-size: 0.9rem;">ANTRIAN BERIKUTNYA</div>
                    <div style="font-size: 0.95rem; color: rgba(255,255,255,0.6); font-weight: 600;">
                        Menunggu: <span style="color: white; font-weight: 900;" x-text="upcoming.length"></span>
                    </div>
                </div>
                <div class="next-queue">
                    <template x-if="upcoming.length > 0">
                        <span class="num-pill next" x-text="upcoming[0]"></span>
                    </template>
                    <template x-for="num in upcoming.slice(1, 6)" :key="num">
                        <span class="num-pill" x-text="num"></span>
                    </template>
                    <span class="next-more" x-show="upcoming.length > 6">
                        +<span x-text="upcoming.length - 6"></span> lagi
                    </span>
                    <span class="next-empty" x-show="upcoming.length === 0">— Belum ada antrian menunggu —</span>
                </div>
            </div>
        </section>

        {{-- RIGHT --}}
        <aside class="side">

            {{-- Stats --}}
            <div class="panel">
                <div class="panel-title">STATISTIK HARI INI</div>
                <div class="stats-row">
                    <div class="stat-box">
                        <div style="font-size:1.5rem;margin-bottom:4px;">✅</div>
                        <div class="stat-value" :class="bumps.served && 'bump'" x-text="stats.served_today">0</div>
                        <div class="stat-label">Dilayani</div>
                    </div>
                    <div class="stat-box">
                        <div style="font-size:1.5rem;margin-bottom:4px;">⏳</div>
                        <div class="stat-value" :class="bumps.waiting && 'bump'" x-text="stats.waiting_today">0</div>
                        <div class="stat-label">Menunggu</div>
                    </div>
                    <div class="stat-box">
                        <div style="font-size:1.5rem;margin-bottom:4px;">📈</div>
                        <div class="stat-value" x-text="stats.completed_month">0</div>
                        <div class="stat-label">Bln Ini</div>
                    </div>
                </div>
            </div>

            {{-- QR --}}
            <div class="qr-panel">
                <div class="qr-image">
                    {!! \SimpleSoftwareIO\QrCode\Facades\QrCode::format('svg')->size(100)->margin(0)->color(30,77,140)->generate(url('/')) !!}
                </div>
                <div style="flex: 1; min-width: 0;">
                    <div class="qr-label-top">Daftar Online</div>
                    <div class="qr-label-big">Scan untuk Mulai</div>
                    <div class="qr-domain">{{ parse_url(config('app.url'), PHP_URL_HOST) ?: 'simpel-dinsos.id' }}</div>
                    <span class="qr-gratis">💚 GRATIS · 16 LAYANAN</span>
                </div>
            </div>

            {{-- Motto + Info --}}
            <div class="info-grid">
                <div class="motto-panel">
                    <div class="motto-care">C·A·R·E</div>
                    <div class="motto-words">CEPAT · ADAPTIF · RESPONSIF · EMPATI</div>
                    <div class="motto-quote">"Mudah, cepat, dan tanpa biaya."</div>
                </div>

                <div class="info-panel">
                    <div class="info-row">
                        <div class="info-icon">📞</div>
                        <div style="flex:1;">
                            <div class="info-label">Pengaduan / Info</div>
                            <div class="info-value">0822-6986-7911</div>
                        </div>
                    </div>
                    <div class="info-row">
                        <div class="info-icon">✉</div>
                        <div style="flex:1;">
                            <div class="info-label">Email</div>
                            <div class="info-value" style="font-size: 0.95rem; color: #e2e8f0;">pringsewudinsos@gmail.com</div>
                        </div>
                    </div>
                </div>
            </div>
        </aside>
    </main>

    {{-- ========== MARQUEE ========== --}}
    <div class="tv-marquee">
        <div class="marquee-track">
            <span><span class="ok">💚 PELAYANAN GRATIS</span> · <b>16 jenis layanan publik</b> · Maklumat 920/460/D.04/X/2023</span>
            <span class="marquee-sep">·</span>
            <span>📞 Pengaduan: <b>0822-6986-7911</b></span>
            <span class="marquee-sep">·</span>
            <span>✉ <b>pringsewudinsos@gmail.com</b></span>
            <span class="marquee-sep">·</span>
            <span>🌐 Aduan anonim: <b>lapor.go.id</b></span>
            <span class="marquee-sep">·</span>
            <span>⭐ <b>Cepat · Adaptif · Responsif · Empati</b></span>
            <span class="marquee-sep">·</span>
            <span>📱 Daftar online tanpa antre — scan QR di layar</span>
            <span class="marquee-sep">·</span>
            <span>🏛 Jl. Dr. dr. Sugiri Syarief, Komplek Perkantoran Pemda Pringsewu</span>
            <span class="marquee-sep">·</span>
        </div>
    </div>

</div>

{{-- ========== AUDIO BANNER ========== --}}
<div class="audio-banner" x-show="!audioEnabled" x-cloak @click="enableAudio()">
    🔊 Klik untuk aktifkan suara panggilan
</div>

{{-- ========== POP-UP OVERLAY ========== --}}
<div class="call-overlay" :class="callOverlay.show && 'show'" x-show="callOverlay.show" x-cloak>
    <div class="call-content">
        <div class="call-overline">Nomor Antrian</div>
        <div class="call-number" x-text="callOverlay.number">A-001</div>
        <div class="call-arrow">⬇</div>
        <div class="call-direction">Silakan menuju</div>
        <div class="call-counter" x-text="callOverlay.counter">LOKET 1</div>
    </div>
</div>

<script>
function tvDisplay() {
    return {
        nowServing: [],
        upcoming: [],
        stats: { served_today: 0, waiting_today: 0, completed_month: 0 },
        bumps: { served: false, waiting: false },
        serverTime: '--:--:--',
        serverDay: '—',
        serverDateRest: '—',
        lastAnnouncedTicket: null,
        audioEnabled: false,
        callOverlay: { show: false, number: '', counter: '' },

        init() {
            this.refresh();
            setInterval(() => this.refresh(), 5000);
            setInterval(() => this.tickClock(), 1000);

            const enable = () => this.enableAudio();
            document.addEventListener('click', enable);
            document.addEventListener('keydown', enable);

            // Reverb subscribe (jika tersedia)
            this.trySubscribeReverb();
        },

        trySubscribeReverb() {
            try {
                if (typeof Echo !== 'undefined' && !window.__echoTV) {
                    // Init Echo via CDN
                    window.Pusher = Pusher;
                    window.Echo = new Echo({
                        broadcaster: 'reverb',
                        key: '{{ env('REVERB_APP_KEY') }}',
                        wsHost: '{{ env('REVERB_HOST', 'localhost') }}',
                        wsPort: {{ env('REVERB_PORT', 8080) }},
                        forceTLS: false,
                        enabledTransports: ['ws', 'wss'],
                    });
                    window.__echoTV = true;
                }
                if (window.Echo) {
                    window.Echo.channel('antrian').listen('.ticket.called', (e) => {
                        this.refresh();
                        this.showCallOverlay(e.ticketNumber, e.counter);
                        this.announce({ ticket_number: e.ticketNumber, counter: e.counter });
                        this.lastAnnouncedTicket = e.ticketNumber;
                    });
                    console.log('[TV] Reverb terhubung.');
                }
            } catch (e) { /* Reverb optional, fallback ke polling */ }
        },

        enableAudio() {
            if (this.audioEnabled) return;
            this.audioEnabled = true;
            try { speechSynthesis.speak(new SpeechSynthesisUtterance(' ')); } catch(e) {}
        },

        tickClock() {
            const now = new Date();
            this.serverTime = now.toLocaleTimeString('id-ID', { hour: '2-digit', minute: '2-digit', second: '2-digit' });
        },

        async refresh() {
            try {
                // Cache-bust query param + no-store header agar Cloudflare/SW/browser
                // tidak meng-cache response antrian (harus selalu fresh)
                // Pakai URL relatif agar otomatis ikut protocol halaman.
                // Tanpa ini, kalau APP_URL di .env server pakai http://, browser akan block
                // mixed-content saat halaman diakses via https:// (fetch silent fail).
                const res = await fetch('/tv/live?t=' + Date.now(), {
                    cache: 'no-store',
                    headers: { 'Cache-Control': 'no-cache' },
                });
                if (! res.ok) {
                    console.warn('[TV] /tv/live HTTP', res.status);
                    return;
                }
                const data = await res.json();
                if (window.__lastFetchedAt && window.__lastFetchedAt === data.fetched_at) {
                    console.warn('[TV] Response sama berturut-turut → kemungkinan cache CDN/proxy aktif. Buka /tv/debug untuk verifikasi DB.');
                }
                window.__lastFetchedAt = data.fetched_at;

                if (data.stats.served_today !== this.stats.served_today) this.bump('served');
                if (data.stats.waiting_today !== this.stats.waiting_today) this.bump('waiting');

                this.nowServing = data.now_serving || [];
                this.upcoming = data.upcoming || [];
                this.stats = data.stats;
                this.serverTime = data.server_time;

                // server_date format: "Rabu, 20 Mei 2026"
                const parts = (data.server_date || '').split(',');
                this.serverDay = parts[0] || '—';
                this.serverDateRest = (parts[1] || '').trim();

                if (data.last_called && data.last_called.ticket_number !== this.lastAnnouncedTicket) {
                    if (this.lastAnnouncedTicket !== null) {
                        this.showCallOverlay(data.last_called.ticket_number, data.last_called.counter);
                        this.announce(data.last_called);
                    }
                    this.lastAnnouncedTicket = data.last_called.ticket_number;
                }
            } catch (e) { console.error(e); }
        },

        bump(key) {
            this.bumps[key] = true;
            setTimeout(() => { this.bumps[key] = false; }, 600);
        },

        showCallOverlay(number, counter) {
            this.callOverlay = { show: true, number, counter: counter || 'LOKET 1' };
            setTimeout(() => { this.callOverlay.show = false; }, 6000);
        },

        announce(ticket) {
            if (! this.audioEnabled) return;
            try {
                const AC = window.AudioContext || window.webkitAudioContext;
                const ctx = new AC();
                [{f:587.33,d:0,t:0.25},{f:783.99,d:0.15,t:0.25},{f:1174.66,d:0.30,t:0.4}].forEach(n => {
                    const osc = ctx.createOscillator(), gain = ctx.createGain();
                    osc.type = 'sine'; osc.frequency.value = n.f;
                    osc.connect(gain); gain.connect(ctx.destination);
                    gain.gain.setValueAtTime(0, ctx.currentTime + n.d);
                    gain.gain.linearRampToValueAtTime(0.25, ctx.currentTime + n.d + 0.02);
                    gain.gain.exponentialRampToValueAtTime(0.001, ctx.currentTime + n.d + n.t);
                    osc.start(ctx.currentTime + n.d);
                    osc.stop(ctx.currentTime + n.d + n.t);
                });
            } catch(e) {}

            setTimeout(() => {
                if ('speechSynthesis' in window) {
                    const counter = ticket.counter || 'LOKET SATU';
                    const text = `Nomor antrian ${this.spellOut(ticket.ticket_number)}, silakan menuju ${counter}`;
                    const utter = new SpeechSynthesisUtterance(text);
                    utter.lang = 'id-ID';
                    utter.rate = 0.92;
                    utter.pitch = 1.05;
                    speechSynthesis.speak(utter);
                    setTimeout(() => speechSynthesis.speak(new SpeechSynthesisUtterance(text)), 3800);
                }
            }, 1100);
        },

        spellOut(s) {
            const map = {'0':'nol','1':'satu','2':'dua','3':'tiga','4':'empat','5':'lima','6':'enam','7':'tujuh','8':'delapan','9':'sembilan','-':''};
            return s.split('').map(c => map[c] !== undefined ? map[c] : c).join(' ');
        },
    };
}
</script>

</body>
</html>
