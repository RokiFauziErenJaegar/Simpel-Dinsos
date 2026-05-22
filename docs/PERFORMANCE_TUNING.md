# Tuning Performa SIMPEL DINSOS

> Daftar setting & optimasi yang **WAJIB** dilakukan di server produksi (`dinsos.rokifauzi.biz.id`) agar loading login + submit pengajuan responsif.

## 1. Setting `.env` Produksi

```env
# Matikan debug — tanpa ini, setiap exception serialisasi stack trace lengkap (lambat + tidak aman)
APP_DEBUG=false
APP_ENV=production
LOG_LEVEL=warning           # hindari verbose 'debug'

# Bcrypt: 10 rounds (default Laravel) ~80ms vs 12 rounds ~300ms saat login
BCRYPT_ROUNDS=10

# Session di file (~1ms vs database ~10-50ms per request)
SESSION_DRIVER=file
SESSION_LIFETIME=120

# Cache di file (untuk tenant resolver, navigation badge)
CACHE_STORE=file

# Broadcasting:
# - Pilih 'log' kalau Reverb belum running (default, tidak ada I/O network)
# - 'reverb' kalau service Reverb sudah jalan via supervisor
BROADCAST_CONNECTION=log

# Notifikasi outbound — biarkan 'log' kalau belum ada WA gateway
NOTIFICATION_DRIVER=log

# Queue — kalau pakai 'database' WAJIB jalankan worker `php artisan queue:work`
QUEUE_CONNECTION=sync       # paling sederhana — eksekusi langsung in-process
```

## 2. Cache Konfigurasi & Route

```bash
# Cache config (hindari parse .env setiap request)
php artisan config:cache

# Cache route (lebih cepat route lookup)
php artisan route:cache

# Cache view
php artisan view:cache

# Composer optimize autoloader
composer install --optimize-autoloader --no-dev
```

> **Catatan**: setelah `config:cache`, helper `env()` hanya bekerja di file `config/*.php`, bukan di kode aplikasi. Pastikan kode aplikasi pakai `config(...)`.

## 3. OPcache PHP

Aktifkan di `php.ini` server:
```ini
opcache.enable=1
opcache.memory_consumption=256
opcache.max_accelerated_files=20000
opcache.validate_timestamps=0
opcache.revalidate_freq=0
```

## 4. SQLite → MySQL (Saran kuat)

SQLite OK untuk demo, tapi untuk produksi multi-user dengan menulis paralel (submit + petugas verifikasi bersamaan), **MySQL/PostgreSQL** jauh lebih baik. SQLite punya per-database lock saat write.

```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_DATABASE=simpel_dinsos
DB_USERNAME=...
DB_PASSWORD=...
```

Migrate ulang: `php artisan migrate:fresh --seed`.

## 5. PHP-FPM (bukan PHP CLI server)

Server XAMPP atau `php artisan serve` adalah **single-process** — request lain harus tunggu. Untuk produksi pakai PHP-FPM + Nginx:
- Multiple workers (8-16 child process)
- Concurrent request tanpa antre

## 6. Reverb (jika dipakai)

Reverb harus dijalankan sebagai service:
```bash
# Di supervisor (.conf)
[program:reverb]
command=php /var/www/simpel-dinsos/artisan reverb:start --host=0.0.0.0 --port=8080
autostart=true
autorestart=true
user=www-data
```

Lalu Nginx proxy WebSocket ke `localhost:8080`. **Kalau Reverb belum disiapkan**, set `BROADCAST_CONNECTION=log` di `.env` agar event tidak coba HTTP ke port 8080 yang mati (yang menyebabkan timeout 30 detik).

## 7. Storage Sensitif

Saat ini `SECURE_DISK_DRIVER=local`. Kalau berkas KTP/KK sudah banyak, pindah ke MinIO supaya hosting tidak penuh dan I/O lebih cepat (lihat `docs/MULTI_TENANT.md` & `infra/docker-compose.minio.yml`).

## 8. Monitoring

Install Laravel Telescope di staging (BUKAN production) untuk profiling query/event:
```bash
composer require laravel/telescope --dev
php artisan telescope:install
```

## Checklist Deploy Performa

- [ ] `APP_DEBUG=false`
- [ ] `LOG_LEVEL=warning`
- [ ] `BCRYPT_ROUNDS=10`
- [ ] `SESSION_DRIVER=file`
- [ ] `CACHE_STORE=file`
- [ ] `BROADCAST_CONNECTION=log` (atau `reverb` + service jalan)
- [ ] `QUEUE_CONNECTION=sync` (atau `database` + worker jalan)
- [ ] `php artisan config:cache && route:cache && view:cache`
- [ ] OPcache aktif di `php.ini`
- [ ] PHP-FPM (bukan `php artisan serve`)
- [ ] Restart web server setelah apply

## Fix Code yang Sudah Dilakukan

| Issue | Impact | Fix |
|---|---|---|
| `bcrypt(rounds=12)` untuk user warga auto-create | +250-400ms per submit | `bcrypt(rounds=4)` — password tidak dipakai, warga login via OTP |
| `NotificationGateway::send*` sync di-request cycle | +10-200ms (log/I/O) atau detik (WA gateway) | `dispatch(fn)->afterResponse()` |
| `IdentifyTenant` query DB setiap request | +5-30ms per request | `Cache::remember(key, 300, fn)` |
| `ApplicationAccessObserver` panggil `request()` setiap retrieved | overhead per record | Cache decision per-request, skip lebih awal |
| `QueueTicketCalled` broadcast sync, Reverb mati = timeout 30s | +30s saat aksi Panggil | `dispatch(fn)->afterResponse()` |
| Session+Cache driver database | +20-100ms I/O per request | File driver |

## Test Cepat Lokal

```bash
# Ukur waktu submit pengajuan (sebelum/sesudah fix)
time curl -X POST http://127.0.0.1:8000/layanan/surat-keterangan-dtsen/ajukan \
  -F "_token=..." -F "..."
```

Targetnya:
- Login petugas: < 1 detik (utama bcrypt verify)
- Submit pengajuan tanpa file: < 500ms
- Submit dengan 3 file 2MB: < 2 detik
