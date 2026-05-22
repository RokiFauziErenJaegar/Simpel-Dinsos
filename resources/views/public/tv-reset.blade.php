<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Reset Cache TV Lobi</title>
    <style>
        body { font-family: system-ui, sans-serif; background: #f1f5f9; padding: 40px; }
        .box { max-width: 600px; margin: 0 auto; background: white; padding: 30px; border-radius: 16px; }
        h1 { color: #1E4D8C; }
        .step { padding: 12px; background: #f8fafc; margin: 8px 0; border-radius: 8px; }
        .ok { color: #059669; }
        .err { color: #dc2626; }
        button { background: #1E4D8C; color: white; padding: 12px 24px; border: 0; border-radius: 8px; font-size: 16px; cursor: pointer; }
        button:hover { background: #163a6b; }
    </style>
</head>
<body>
    <div class="box">
        <h1>🔄 Reset Cache TV Lobi</h1>
        <p>Halaman ini menghapus seluruh cache browser & service worker untuk SIMPEL DINSOS. Setelah selesai, refresh /tv normal.</p>

        <div id="log"></div>
        <button id="reset" onclick="run()">Mulai Reset</button>
    </div>

    <script>
    const log = document.getElementById('log');
    function line(text, cls = '') {
        const el = document.createElement('div');
        el.className = 'step ' + cls;
        el.textContent = text;
        log.appendChild(el);
    }

    async function run() {
        document.getElementById('reset').disabled = true;
        line('▶ Memulai reset...');

        // 1. Unregister semua service workers
        if ('serviceWorker' in navigator) {
            const regs = await navigator.serviceWorker.getRegistrations();
            for (const reg of regs) {
                await reg.unregister();
                line('✓ Service worker dihapus: ' + reg.scope, 'ok');
            }
            if (regs.length === 0) line('• Tidak ada service worker terdaftar');
        }

        // 2. Hapus semua cache
        if ('caches' in window) {
            const keys = await caches.keys();
            for (const k of keys) {
                await caches.delete(k);
                line('✓ Cache dihapus: ' + k, 'ok');
            }
            if (keys.length === 0) line('• Tidak ada cache tersimpan');
        }

        // 3. Clear localStorage & sessionStorage (untuk PWA banner dll)
        try { localStorage.clear(); line('✓ localStorage dihapus', 'ok'); } catch(e) {}
        try { sessionStorage.clear(); line('✓ sessionStorage dihapus', 'ok'); } catch(e) {}

        line('✓ Selesai! Klik tombol di bawah untuk buka TV ulang.', 'ok');

        const btn = document.createElement('button');
        btn.textContent = 'Buka TV Lobi →';
        btn.onclick = () => location.href = '/tv';
        log.appendChild(btn);
    }
    </script>
</body>
</html>
