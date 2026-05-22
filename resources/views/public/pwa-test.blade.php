@extends('layouts.public')
@section('title', 'PWA Device Test')
@section('content')

<section class="max-w-2xl mx-auto px-4 md:px-6 py-10">
    <h1 class="text-2xl font-bold text-slate-900">📱 PWA Device Test</h1>
    <p class="text-slate-600 text-sm mt-1">Cek dukungan PWA, service worker, dan push notification di perangkat Anda.</p>

    <div class="mt-6 space-y-3 text-sm" x-data="pwaTest()" x-init="check()">
        <div class="card-elev p-4 flex items-center justify-between">
            <div><strong>Service Worker</strong> — caching offline & background</div>
            <span x-text="sw.label" :class="sw.ok ? 'text-emerald-600 font-bold' : 'text-rose-600 font-bold'"></span>
        </div>
        <div class="card-elev p-4 flex items-center justify-between">
            <div><strong>Installable</strong> — bisa dipasang sebagai aplikasi</div>
            <span x-text="installable.label" :class="installable.ok ? 'text-emerald-600 font-bold' : 'text-slate-500'"></span>
        </div>
        <div class="card-elev p-4 flex items-center justify-between">
            <div><strong>Standalone Mode</strong> — sedang berjalan sebagai PWA</div>
            <span x-text="standalone.label" :class="standalone.ok ? 'text-emerald-600 font-bold' : 'text-slate-500'"></span>
        </div>
        <div class="card-elev p-4 flex items-center justify-between">
            <div><strong>Push Notification API</strong></div>
            <span x-text="push.label" :class="push.ok ? 'text-emerald-600 font-bold' : 'text-slate-500'"></span>
        </div>
        <div class="card-elev p-4 flex items-center justify-between">
            <div><strong>Notification Permission</strong></div>
            <span x-text="notifPerm" class="font-bold"></span>
        </div>
        <div class="card-elev p-4 flex items-center justify-between">
            <div><strong>Online</strong></div>
            <span x-text="online.label" :class="online.ok ? 'text-emerald-600 font-bold' : 'text-amber-600 font-bold'"></span>
        </div>
        <div class="card-elev p-4">
            <strong>User Agent</strong>
            <div class="text-xs text-slate-500 mt-1 break-all" x-text="ua"></div>
        </div>

        <div class="mt-6 grid md:grid-cols-2 gap-3">
            <button @click="testNotification()" class="btn-primary text-sm">Test Notifikasi Lokal</button>
            <button @click="subscribeServerPush()" class="btn-primary text-sm" :disabled="!isLogged">📡 Subscribe Server Push</button>
            <button @click="testServerPush()" class="btn-outline text-sm" :disabled="!isLogged">Kirim Push dari Server</button>
            <button @click="testCacheClear()" class="btn-outline text-sm">Hapus Cache Service Worker</button>
        </div>
        <p x-show="!isLogged" class="text-xs text-amber-600 mt-2">⚠ Login warga (/masuk) dulu agar subscription terikat ke akun.</p>

        <div x-show="log.length > 0" class="mt-4 card-elev p-4 bg-slate-50">
            <div class="font-semibold mb-2 text-sm">Log:</div>
            <template x-for="line in log" :key="line">
                <div class="text-xs font-mono text-slate-700" x-text="line"></div>
            </template>
        </div>
    </div>

    <div class="mt-8 card-elev p-4 bg-blue-50 text-blue-900 text-sm">
        💡 Untuk install penuh di Android: buka di Chrome → menu titik tiga → "Add to Home screen".
        Di iOS Safari: Share → "Add to Home Screen". Setelah terinstal, aplikasi terbuka dalam mode standalone tanpa address bar.
    </div>
</section>

<script>
function pwaTest() {
    return {
        sw: { ok: false, label: 'memeriksa...' },
        installable: { ok: false, label: 'belum terdeteksi' },
        standalone: { ok: false, label: 'mode browser' },
        push: { ok: false, label: 'memeriksa...' },
        online: { ok: navigator.onLine, label: navigator.onLine ? 'YA' : 'OFFLINE' },
        notifPerm: 'memeriksa...',
        ua: navigator.userAgent,
        isLogged: @json(auth()->check()),
        log: [],

        async check() {
            // Service Worker
            if ('serviceWorker' in navigator) {
                const reg = await navigator.serviceWorker.getRegistration();
                this.sw = { ok: !!reg, label: reg ? 'AKTIF ✓' : 'tidak terdaftar' };
            } else {
                this.sw = { ok: false, label: 'tidak didukung' };
            }

            // Standalone mode
            const isStandalone = window.matchMedia('(display-mode: standalone)').matches ||
                                 window.navigator.standalone === true;
            this.standalone = { ok: isStandalone, label: isStandalone ? 'YA ✓' : 'BELUM (jalankan dari home screen)' };

            // Push API
            const supportsPush = 'PushManager' in window && 'Notification' in window;
            this.push = { ok: supportsPush, label: supportsPush ? 'didukung ✓' : 'tidak didukung' };

            // Notification permission
            this.notifPerm = ('Notification' in window) ? Notification.permission : 'tidak didukung';

            // Beforeinstallprompt sudah ditangkap di layout
            this.installable = { ok: !!window.deferredPrompt, label: window.deferredPrompt ? 'YA ✓' : '—' };

            this.log.push('[' + new Date().toLocaleTimeString('id-ID') + '] Pemeriksaan selesai.');
        },

        async testNotification() {
            if (! ('Notification' in window)) {
                this.log.push('Notification API tidak didukung di browser ini.');
                return;
            }
            const perm = await Notification.requestPermission();
            this.notifPerm = perm;
            if (perm !== 'granted') {
                this.log.push('Izin notifikasi ditolak.');
                return;
            }
            // Kirim notifikasi via service worker (terlihat di tray bahkan ketika tab tertutup)
            const reg = await navigator.serviceWorker.getRegistration();
            if (reg) {
                reg.showNotification('SIMPEL DINSOS', {
                    body: 'Test notifikasi dari device test page. ' + new Date().toLocaleTimeString('id-ID'),
                    icon: '/icons/icon-192.svg',
                    badge: '/icons/icon-192.svg',
                    vibrate: [100, 50, 100],
                });
                this.log.push('Notifikasi terkirim via service worker.');
            } else {
                new Notification('SIMPEL DINSOS', { body: 'Test notifikasi (fallback).' });
                this.log.push('Notifikasi dikirim via fallback (no SW).');
            }
        },

        async testCacheClear() {
            if (! ('caches' in window)) return;
            const keys = await caches.keys();
            await Promise.all(keys.map(k => caches.delete(k)));
            this.log.push('Cache dihapus: ' + keys.join(', '));
        },

        async subscribeServerPush() {
            if (!('serviceWorker' in navigator) || !('PushManager' in window)) {
                this.log.push('Push API tidak didukung browser ini.');
                return;
            }
            const perm = await Notification.requestPermission();
            this.notifPerm = perm;
            if (perm !== 'granted') {
                this.log.push('Izin notifikasi ditolak.');
                return;
            }

            // Ambil VAPID public key
            const vapidRes = await fetch('/pwa/vapid-key');
            const vapid = await vapidRes.json();
            if (!vapid.key) {
                this.log.push('VAPID key belum di-set di server.');
                return;
            }

            const reg = await navigator.serviceWorker.ready;
            const subscription = await reg.pushManager.subscribe({
                userVisibleOnly: true,
                applicationServerKey: this.urlBase64ToUint8Array(vapid.key),
            });

            // Kirim subscription ke server
            const res = await fetch('/pwa/subscribe', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                },
                body: JSON.stringify(subscription.toJSON()),
            });
            const data = await res.json();
            this.log.push('Subscription tersimpan id=' + data.id + ' — endpoint: ' + subscription.endpoint.slice(0, 60) + '...');
        },

        async testServerPush() {
            const res = await fetch('/pwa/test-push', {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    'Accept': 'application/json',
                },
            });
            const data = await res.json();
            if (data.error) {
                this.log.push('Error: ' + data.error);
                return;
            }
            this.log.push('Server push: ' + data.message);
        },

        urlBase64ToUint8Array(base64String) {
            const padding = '='.repeat((4 - base64String.length % 4) % 4);
            const base64 = (base64String + padding).replace(/-/g, '+').replace(/_/g, '/');
            const rawData = window.atob(base64);
            const output = new Uint8Array(rawData.length);
            for (let i = 0; i < rawData.length; ++i) output[i] = rawData.charCodeAt(i);
            return output;
        },
    };
}
</script>

@endsection
