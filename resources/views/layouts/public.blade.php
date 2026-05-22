<!DOCTYPE html>
<html lang="id" class="scroll-smooth">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Beranda') · {{ config('app.name') }}</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Plus+Jakarta+Sans:wght@600;700;800&display=swap" rel="stylesheet">

    {{-- PWA --}}
    <link rel="manifest" href="/manifest.webmanifest">
    <link rel="icon" type="image/svg+xml" href="/icons/icon-192.svg">
    <link rel="apple-touch-icon" href="/icons/icon-192.svg">
    <meta name="theme-color" content="#1E4D8C">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="default">
    <meta name="apple-mobile-web-app-title" content="SIMPEL DINSOS">

    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <style>
        :root {
            --brand: #1E4D8C;
            --brand-dark: #163a6b;
            --accent: #2DB67C;
        }
        body { font-family: 'Inter', system-ui, sans-serif; color: #1f2937; background: #f8fafc; }
        h1, h2, h3, h4 { font-family: 'Plus Jakarta Sans', system-ui, sans-serif; }
        .btn-primary {
            background: var(--brand);
            color: white;
            padding: 0.75rem 1.5rem;
            border-radius: 0.75rem;
            font-weight: 600;
            transition: all 0.2s;
            display: inline-flex; align-items: center; gap: 0.5rem;
        }
        .btn-primary:hover { background: var(--brand-dark); transform: translateY(-1px); box-shadow: 0 8px 24px rgba(30, 77, 140, 0.25); }
        .btn-outline {
            border: 1.5px solid #cbd5e1;
            color: #334155;
            padding: 0.75rem 1.5rem;
            border-radius: 0.75rem;
            font-weight: 600;
            transition: all 0.2s;
            display: inline-flex; align-items: center; gap: 0.5rem;
        }
        .btn-outline:hover { border-color: var(--brand); color: var(--brand); }
        .card-elev { background: white; border-radius: 1rem; box-shadow: 0 1px 3px rgba(0,0,0,0.05), 0 4px 12px rgba(0,0,0,0.04); }
        .brand-gradient { background: linear-gradient(135deg, var(--brand) 0%, var(--brand-dark) 100%); }
    </style>
    @stack('head')
</head>
<body class="min-h-screen flex flex-col">

<header class="bg-white border-b border-slate-200 sticky top-0 z-50 backdrop-blur-sm bg-white/95">
    <nav class="max-w-7xl mx-auto px-4 md:px-6 py-4 flex items-center justify-between">
        <a href="{{ route('home') }}" class="flex items-center gap-3">
            <div class="w-10 h-10 rounded-xl brand-gradient flex items-center justify-center text-white font-bold">D</div>
            <div>
                <div class="font-bold text-slate-900 leading-tight">SIMPEL DINSOS</div>
                <div class="text-xs text-slate-500">Kab. Pringsewu</div>
            </div>
        </a>
        <div class="hidden md:flex items-center gap-1">
            <a href="{{ route('home') }}" class="px-4 py-2 text-sm font-medium text-slate-700 hover:text-[color:var(--brand)] rounded-lg hover:bg-slate-50">Beranda</a>
            <a href="{{ route('layanan.index') }}" class="px-4 py-2 text-sm font-medium text-slate-700 hover:text-[color:var(--brand)] rounded-lg hover:bg-slate-50">Layanan</a>
            <a href="{{ route('cek-status.index') }}" class="px-4 py-2 text-sm font-medium text-slate-700 hover:text-[color:var(--brand)] rounded-lg hover:bg-slate-50">Cek Status</a>
            <a href="{{ route('pengaduan.create') }}" class="px-4 py-2 text-sm font-medium text-slate-700 hover:text-[color:var(--brand)] rounded-lg hover:bg-slate-50">Lapor</a>
        </div>
        <div class="flex items-center gap-2">
            <a href="{{ route('tv.display') }}" target="_blank" class="hidden md:inline-flex text-xs px-3 py-2 text-slate-500 hover:text-slate-900">📺 TV Lobi</a>
            <a href="{{ route('kiosk.index') }}" target="_blank" class="hidden md:inline-flex text-xs px-3 py-2 text-slate-500 hover:text-slate-900">🏪 Kiosk</a>
            @auth
                <a href="{{ auth()->user()->role->value === 'operator_pekon' ? route('pekon.dashboard') : route('warga.dashboard') }}" class="btn-outline text-sm">Akun Saya</a>
            @else
                <a href="{{ route('warga.login') }}" class="btn-outline text-sm">Masuk</a>
            @endauth
            <a href="/admin" class="hidden md:inline-flex text-xs px-3 py-2 text-slate-500 hover:text-slate-900">Petugas →</a>
        </div>
    </nav>
</header>

<main class="flex-grow">
    @yield('content')
</main>

<footer class="bg-slate-900 text-slate-300 mt-20">
    <div class="max-w-7xl mx-auto px-4 md:px-6 py-12 grid md:grid-cols-4 gap-8">
        <div class="md:col-span-2">
            <div class="flex items-center gap-3 mb-4">
                <div class="w-10 h-10 rounded-xl brand-gradient flex items-center justify-center text-white font-bold">D</div>
                <div>
                    <div class="font-bold text-white">DINAS SOSIAL</div>
                    <div class="text-xs text-slate-400">Kabupaten Pringsewu</div>
                </div>
            </div>
            <p class="text-sm leading-relaxed text-slate-400">
                Jl. Dr. dr. Sugiri Syarief, Komplek Perkantoran Pemerintah Daerah Kabupaten Pringsewu — Kode Pos 35372.
            </p>
            <p class="mt-4 text-sm text-slate-400 italic">
                "Mudah, cepat, dan tanpa biaya."
            </p>
        </div>
        <div>
            <div class="font-semibold text-white mb-3">Kanal Pengaduan</div>
            <ul class="space-y-2 text-sm">
                <li>📞 WA/Telp: 0822-6986-7911</li>
                <li>✉ pringsewudinsos@gmail.com</li>
                <li>🌐 lapor.go.id</li>
                <li>🏢 Loket Informasi (jam kerja)</li>
            </ul>
        </div>
        <div>
            <div class="font-semibold text-white mb-3">Tautan</div>
            <ul class="space-y-2 text-sm">
                <li><a href="{{ route('layanan.index') }}" class="hover:text-white">16 Layanan Publik</a></li>
                <li><a href="{{ route('cek-status.index') }}" class="hover:text-white">Cek Status</a></li>
                <li><a href="{{ route('pengaduan.create') }}" class="hover:text-white">Pengaduan</a></li>
                <li><a href="{{ route('tv.display') }}" target="_blank" class="hover:text-white">Display Lobi</a></li>
            </ul>
        </div>
    </div>
    <div class="border-t border-slate-800">
        <div class="max-w-7xl mx-auto px-4 md:px-6 py-4 text-xs text-slate-500 text-center">
            © {{ date('Y') }} Pemerintah Kabupaten Pringsewu · Dinas Sosial · Maklumat 920/460/D.04/X/2023
        </div>
    </div>
</footer>

{{-- PWA: Service Worker + Install Prompt --}}
<div id="pwa-install" class="fixed bottom-4 inset-x-4 md:left-auto md:right-4 md:max-w-sm card-elev p-4 hidden z-50">
    <div class="flex items-start gap-3">
        <div class="w-10 h-10 rounded-xl brand-gradient flex items-center justify-center text-white font-bold flex-shrink-0">D</div>
        <div class="flex-1 min-w-0">
            <div class="font-semibold text-slate-900 text-sm">Pasang SIMPEL DINSOS</div>
            <div class="text-xs text-slate-500 mt-0.5">Akses lebih cepat seperti aplikasi mobile, hemat data.</div>
            <div class="mt-3 flex gap-2">
                <button id="pwa-install-btn" class="btn-primary text-xs px-3 py-1.5">Pasang →</button>
                <button id="pwa-dismiss-btn" class="text-xs text-slate-500 px-3 py-1.5">Nanti saja</button>
            </div>
        </div>
        <button id="pwa-close-btn" class="text-slate-400 hover:text-slate-700 flex-shrink-0 text-lg leading-none">×</button>
    </div>
</div>

<script>
// Register Service Worker
if ('serviceWorker' in navigator) {
    window.addEventListener('load', () => {
        navigator.serviceWorker.register('/sw.js').catch(err => console.warn('SW gagal:', err));
    });
}

// PWA Install Prompt
let deferredPrompt = null;
const installBox = document.getElementById('pwa-install');
const installBtn = document.getElementById('pwa-install-btn');
const dismissBtn = document.getElementById('pwa-dismiss-btn');
const closeBtn = document.getElementById('pwa-close-btn');

window.addEventListener('beforeinstallprompt', (e) => {
    e.preventDefault();
    deferredPrompt = e;
    // Tampilkan banner hanya kalau belum di-dismiss minggu ini
    if (! localStorage.getItem('pwa-dismissed-until') || Date.now() > Number(localStorage.getItem('pwa-dismissed-until'))) {
        if (installBox) installBox.classList.remove('hidden');
    }
});

installBtn?.addEventListener('click', async () => {
    if (! deferredPrompt) return;
    deferredPrompt.prompt();
    const { outcome } = await deferredPrompt.userChoice;
    console.log('PWA install:', outcome);
    deferredPrompt = null;
    installBox?.classList.add('hidden');
});

const dismiss = () => {
    // Tunda banner 7 hari
    localStorage.setItem('pwa-dismissed-until', String(Date.now() + 7 * 24 * 3600 * 1000));
    installBox?.classList.add('hidden');
};
dismissBtn?.addEventListener('click', dismiss);
closeBtn?.addEventListener('click', dismiss);

// Saat sudah ter-install
window.addEventListener('appinstalled', () => {
    installBox?.classList.add('hidden');
    deferredPrompt = null;
});
</script>

@stack('scripts')
</body>
</html>
