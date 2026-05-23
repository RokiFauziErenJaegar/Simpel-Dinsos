# Progress Pengembangan SIMPEL DINSOS Pringsewu

**Sistem Informasi Manajemen Pelayanan Dinas Sosial Kabupaten Pringsewu**

Dokumen ini mencatat seluruh progress pembangunan aplikasi dari awal hingga deployment produksi ke Railway, lengkap dengan keputusan teknis dan fix yang dilakukan di tiap fase.

---

## Identitas Proyek

| Aspek | Detail |
|-------|--------|
| **Nama** | SIMPEL DINSOS Pringsewu |
| **Kepanjangan** | Sistem Informasi Manajemen Pelayanan Dinas Sosial Kabupaten Pringsewu |
| **Instansi** | Dinas Sosial Kabupaten Pringsewu, Provinsi Lampung |
| **Acuan SOP** | Maklumat No. 920/460/D.04/X/2023 (16 Oktober 2023) |
| **Kepala Dinas** | Debi Hardian, S.Pi., M.Si. (Pembina Utama Muda, NIP 19671022 199803 2 005) |
| **Repository** | https://github.com/RokiFauziErenJaegar/Simpel-Dinsos |
| **Production (Railway)** | https://web-production-3b557.up.railway.app |
| **Production (Cloudflare Tunnel ke XAMPP)** | https://dinsos.rokifauzi.biz.id |
| **Motto** | C-A-R-E (Cepat · Adaptif · Responsif · Empati) — *"Mudah, cepat, dan tanpa biaya"* |

## Stack Teknis Akhir

| Komponen | Versi | Keterangan |
|----------|-------|------------|
| Laravel | 12.60.1 | Framework PHP utama |
| PHP | 8.3.26 | Runtime, OPcache aktif |
| Filament | 4.0 | Admin panel untuk petugas/Kadis |
| Livewire | 3.8 | Real-time component (Filament + custom) |
| Tailwind CSS | 4.0 | Via Vite |
| Database | MySQL/MariaDB 10.4 | Production (Railway MySQL plugin + XAMPP lokal). SQLite hanya untuk migrasi awal. |
| Queue | Database driver | Worker terpisah di Railway service `queue` |
| Cache | File driver | Volume Railway untuk persistence |
| QR Code | simplesoftwareio/simple-qrcode | Untuk verifikasi surat |
| PDF | barryvdh/laravel-dompdf | Generate surat A4 |
| Gateway WA | Fonnte (gsEMsedR5PEs1PVcM52T) | 100/hari paket Hobby gratis |
| Web Push | minishlink/web-push (VAPID) | Push notification real |
| 2FA | pragmarx/google2fa | TOTP via Google Authenticator |

---

## Fase 1 — MVP Pelayanan Publik (Selesai)

Fondasi sistem digitalisasi 16 layanan Dinas Sosial Pringsewu.

### Modul Publik (untuk Warga)
- ✅ Landing page dengan statistik real-time & antrian live
- ✅ Katalog 16 layanan dengan filter pencarian (per bidang)
- ✅ Halaman detail layanan (persyaratan, prosedur, output)
- ✅ Form pengajuan layanan (multi-section, upload berkas, consent UU PDP)
- ✅ Halaman sukses dengan nomor antrian
- ✅ Cek status pengajuan dengan timeline real-time
- ✅ Form pengaduan masyarakat (web, opsi anonim)
- ✅ Halaman verifikasi keaslian dokumen ber-QR

### Modul Internal (Filament Admin)
- ✅ Login pegawai dengan role-based access (7 role: Admin, Kadis, Sekretaris, Kabid, Kasi, Petugas, Operator Pekon)
- ✅ Dashboard Kadis: 6 KPI utama (Pemohon Bulan Ini, Ketepatan SLA, Pengaduan Aktif, Lewat SLA, Dilayani Hari Ini, Indeks Kepuasan)
- ✅ Resource Pengajuan Layanan dengan tabel filter status/jenis layanan
- ✅ Workflow action: Setujui → Kembalikan → Tolak → Terbitkan Surat
- ✅ Badge notifikasi sidebar untuk pengajuan aktif
- ✅ Audit log otomatis di tiap transisi status

### Display TV Lobi
- ✅ Fullscreen mode dengan auto-refresh 5 detik
- ✅ Panel "Sedang Dilayani" (3 loket) + "Antrian Berikutnya"
- ✅ Statistik hari ini & bulan ini
- ✅ Daftar layanan ringkas + QR code untuk daftar online
- ✅ Marquee footer dengan info kontak

### Database Seeder
- 9 kecamatan Pringsewu (Adiluwih, Ambarawa, Banyumas, dst)
- 41 pekon (desa) di bawah kecamatan tsb
- 16 service types dari SOP (L01-L16)
- 8 akun demo (admin, kadis, sekretaris, kabid, kasi, petugas, operator_pekon, 2 warga)
- 1 PPKS profile sample

---

## Fase 2 — Layanan Lengkap + Notifikasi (Selesai)

Lengkapi alur end-to-end dengan output dokumen resmi dan notifikasi outbound.

- ✅ **PDF Generator** — `App\Services\DocumentGenerator` menerbitkan surat A4 dengan kop, nomor 920/xxx/D.04/M/Y, QR verifikasi, dan blok tanda tangan Kadis (~730 KB per surat)
- ✅ **NotificationGateway adapter** — `App\Services\NotificationGateway` dengan driver `log` (default, outbox file), `fonnte`, `wablas`. Trigger di submit, complete, OTP, survei SKM
- ✅ **Login warga via OTP WhatsApp** — `/masuk` → input HP → `/masuk/verifikasi/{phone}` → kode 6 digit (berlaku 5 menit, max 5 percobaan)
- ✅ **Mode Operator Pekon** — `/pekon` dengan PIN e-sign Kepala Pekon, auto-isi data warga dari profil operator
- ✅ **Survei Kepuasan Masyarakat (SKM)** — `/skm/{code}` form 9 unsur Permenpan RB 14/2017, indeks otomatis terhitung
- ✅ **Ekspor Laporan Bulanan PDF** — Filament page `/admin/laporan-bulanan` untuk Kadis, lengkap KPI + statistik per layanan (~870 KB)
- ✅ **Audio TTS panggilan antrian di TV** — Web Audio API + Web Speech API, Bahasa Indonesia
- ✅ **Kiosk self-service lobi** — `/kiosk` untuk warga walk-in ambil antrian
- ✅ **Modul LKS Registry** — `/admin/lks` untuk lembaga/yayasan/panti terdaftar
- ✅ **Stub Dukcapil + DTSEN** — `App\Services\DukcapilService` + `DtsenService` dengan driver mock/http, endpoint `/api/nik/{nik}`

---

## Fase 3 — Realtime, Bot, Compliance (Selesai)

Fitur lanjutan untuk meningkatkan responsiveness dan kualitas pelayanan.

- ✅ **PDF surat dengan stempel + TTD scan** — `SignatureAssetGenerator` auto-generate SVG cap bundar + tanda tangan untuk demo. Production: upload PNG hasil scan
- ✅ **Modul UGB/PUB perizinan (L04)** — Filament resource `/admin/ugb-pub-permits` lengkap dengan badge sidebar
- ✅ **WhatsApp Bot inbound + simulator** — `App\Services\WhatsAppBot` state-machine 4 menu (cek status / aduan / daftar layanan / kontak). Webhook `/webhook/wa` siap Fonnte/Wablas. Simulator UI di `/wa-demo`
- ✅ **Lapor.go.id (SP4N) adapter** — `LaporGoIdService` driver mock/http. `php artisan lapor:poll` tiap 15 menit via Schedule
- ✅ **Realtime Laravel Reverb WebSocket** — Event `QueueTicketCalled` broadcast ke channel `antrian`. TV Lobi subscribe via Echo + Pusher driver
- ✅ **Storage S3-compatible / MinIO** — Disk `minio` & `secure` di `config/filesystems.php`. `SECURE_DISK_DRIVER=minio` untuk pindah ke on-premise
- ✅ **Penetration test + UU PDP checklist** — Dokumen `docs/SECURITY_CHECKLIST.md` 7 area teknis + 8 area UU PDP

### Konfigurasi `.env` baru (Fase 3)
```env
BROADCAST_CONNECTION=reverb
REVERB_APP_ID=505077
REVERB_APP_KEY=...

MINIO_KEY=minioadmin
SECURE_DISK_DRIVER=local

LAPOR_DRIVER=mock
NOTIFICATION_WEBHOOK_TOKEN=
```

---

## Fase 4 — Keamanan + REST API + Multi-Tenant Stub (Selesai)

Kepatuhan UU PDP 27/2022 + readiness untuk skala multi-instansi.

- ✅ **Audit log akses baca data PPKS** — Tabel `data_access_logs` + `ApplicationAccessObserver` otomatis catat akses pegawai
- ✅ **Enkripsi at-rest NIK & KK** — `users.nik`, `applications.beneficiary_nik`, `ppks_profiles.family_card_no` cast `encrypted`. Untuk lookup, kolom `nik_hash` (SHA-256 HMAC dengan APP_KEY)
- ✅ **Soft-delete + scheduled scrub** — Trait `SoftDeletes` di model utama + command `pdp:scrub` (berkas KTP/KK > 3 tahun, OTP expired, access log > 2 tahun). Schedule harian 02:00 WIB
- ✅ **Hak portabilitas + hak hapus data warga** — `/akun/data-saya` (UU PDP Pasal 13). Unduh JSON + hapus dengan konfirmasi typed
- ✅ **2FA Google Authenticator (TOTP)** — `/akun/2fa` dengan QR code, 8 recovery code. Wajib untuk Admin & Kadis
- ✅ **REST API v1 (Sanctum)** — 9 endpoint untuk mobile:
  - `GET /api/v1/services` daftar 16 layanan
  - `GET /api/v1/services/{slug}` detail layanan
  - `GET /api/v1/queue/status` antrian live
  - `GET /api/v1/applications/{code}` status pengajuan + timeline
  - `POST /api/v1/auth/send-otp` kirim OTP ke HP
  - `POST /api/v1/auth/verify-otp` verifikasi + dapatkan Bearer token
  - `GET /api/v1/auth/me` profil user
  - `GET /api/v1/my/applications` daftar pengajuan saya
  - `POST /api/v1/auth/logout` revoke token
- ✅ **Multi-tenant config stub** — `config/tenant.php` + `docs/MULTI_TENANT.md`. Mode `single` default

---

## Fase 5 — PWA + Multi-Tenant Penuh + TTE (Selesai)

Aplikasi siap dipasang sebagai PWA di HP, multi-tenant penuh, dan TTE BSrE.

- ✅ **PWA — Mobile App via Web** — `manifest.webmanifest` + `sw.js` + ikon 192/512/maskable. Banner install dengan dismiss 7 hari, offline shell, app shortcuts, strategi cache network-first untuk HTML + cache-first untuk asset. Halaman `/pwa-test`
- ✅ **Multi-tenant Penuh** — Tabel `tenants` + `tenant_id` di 14 tabel utama. Trait `BelongsToTenant` global scope. Middleware `IdentifyTenant` resolve via subdomain atau header `X-Tenant`. 3 tenant seed: Pringsewu (aktif), Pesawaran & Tanggamus (non-aktif)
- ✅ **Storage Sensitif default ke `secure` disk** — `ApplicationController` upload ke disk `secure` (di luar `public/`). Endpoint `/secure-file/{docId}` dengan auth + audit log via `SecureFileController`
- ✅ **BSrE BSSN TTE Adapter** — `App\Services\BsreService` driver mock/http. Auto-trigger setelah `DocumentGenerator::issue()` kalau `BSRE_ENABLED=true`
- ✅ **Push Notification Stub** — Tabel `push_subscriptions` + endpoint subscribe + `/pwa-test`
- ✅ **ISO/IEC 27001 Gap Analysis** — `docs/ISO_27001_GAP_ANALYSIS.md` memetakan 93 kontrol Annex A
- ✅ **Flutter / React Native Starter Guide** — `docs/MOBILE_APP_GUIDE.md` dengan 3 opsi + struktur folder Flutter

---

## Fase 6 — Web Push Real + UI Tenant + Flutter Starter (Selesai)

Penyelesaian fitur push notification real dan starter project mobile.

- ✅ **Web Push Real (VAPID)** — Paket `minishlink/web-push` terpasang, VAPID key pair generated via Node.js, `WebPushService` mengirim push real ke FCM/Mozilla/Apple. `/pwa-test` dengan tombol "Subscribe Server Push" + "Kirim Push dari Server"
- ✅ **Filament Tenant Resource** — `/admin/tenants` UI manajemen tenant (admin only). Aksi Aktifkan/Nonaktifkan satu klik
- ✅ **ISO 27001 Supporting Docs** — 4 dokumen di `docs/policies/`:
  - `ACCEPTABLE_USE_POLICY.md` (A.5.10, A.6.1-A.6.6)
  - `INCIDENT_RESPONSE_RUNBOOK.md` (NIST SP 800-61, A.5.24-A.5.30)
  - `BUSINESS_CONTINUITY_PLAN.md` (RTO/RPO 6 layanan, A.5.29-A.5.30)
  - `DATA_PROCESSING_AGREEMENT_TEMPLATE.md` (UU PDP Pasal 51-52, A.5.19-A.5.22)
- ✅ **MinIO Docker Compose** — `infra/docker-compose.minio.yml` + `infra/setup-minio.sh`. Jalankan `bash infra/setup-minio.sh` untuk siap pakai
- ✅ **Flutter Starter Project** — Folder `mobile/flutter/` dengan 18 file: pubspec, main+app, core (API client, router, theme), features (auth, home, services, applications, profile), models. State Riverpod, routing GoRouter

### Konfigurasi `.env` baru (Fase 6)
```env
VAPID_PUBLIC_KEY="BNr3a0nFnSZi..."
VAPID_PRIVATE_KEY="LGw_k4f9xnVK..."
VAPID_SUBJECT="mailto:pringsewudinsos@gmail.com"
VITE_VAPID_PUBLIC_KEY="${VAPID_PUBLIC_KEY}"
```

---

## Fase 7 — Migrasi Database & Optimasi Performa (Selesai, Mei 2026)

Persiapan untuk produksi: pindah dari SQLite ke MySQL, dramatic speedup, polish UX.

### Migrasi SQLite → MySQL/MariaDB

**Problem**: SQLite di pakai untuk dev, tidak cocok untuk produksi multi-user concurrent.

**Solusi**:
- Default database pindah ke MariaDB 10.4 (XAMPP)
- Connection `sqlite_legacy` ditambah di [config/database.php](config/database.php) sebagai jembatan baca data lama
- Command baru: [`db:migrate-from-sqlite`](app/Console/Commands/DbMigrateFromSqliteCommand.php) — salin semua tabel per-tabel dengan urutan FK benar, sync auto-increment, skip tabel ephemeral (cache/session/jobs/migrations)
- Fix migrasi: kolom `users.two_factor_recovery_codes` dari `json` → `text` (MariaDB strict-mode menolak isi base64 yang bukan JSON valid)
- 211 baris berhasil dipindah dari SQLite lokal (9 kecamatan, 41 pekon, 3 tenant, 12 user, 16 layanan, 1 PPKS, 15 pengajuan, 44 dokumen, 13 log, 14 tiket, 1 surat, 18 OTP, 24 access log)

### OPcache + Filament Cache (Performance)

**Problem**: GET `/admin/login` 0.91-1.36s, dashboard `/admin` 1.30-1.90s, beranda `/` 1.47s — terlalu lambat untuk presentation ke pimpinan.

**Diagnosis**:
- OPcache **tidak aktif** di XAMPP PHP — PHP parse + compile ribuan file Laravel/Filament tiap request (penyebab utama, ~70% perlambatan)
- Widget `KadisOverview` jalankan 6 query agregat SQLite tiap render dashboard (192ms)
- Navigation badge query DB tiap render sidebar (3 query COUNT)
- Filament boot tanpa cache komponen — scan resources/pages/widgets via filesystem tiap request

**Solusi**:
- Aktifkan OPcache di `C:\xampp\php\php.ini` (memory 256MB, 20k file, JIT, `revalidate_freq=0` untuk dev)
- `php artisan filament:optimize` cache komponen Filament + Blade icons
- `php artisan event:cache` cache Laravel events
- Widget `KadisOverview` bungkus 6 query dalam `Cache::remember('kadis.overview.v1', 60, ...)` + matikan `pollingInterval`
- Navigation badge Applications/Tenants/UgbPubPermits cache 30-60 detik dengan `Cache::remember`
- bcrypt rounds **TIDAK** diturunkan dari 10 (keamanan password admin > performa)

### Async Notification (Performance)

**Problem**: HTTP call Fonnte sinkron pada login OTP — worst case 24 detik (timeout 8s × 3 retry).

**Solusi awal**:
- Semua call ke Fonnte dibungkus `dispatch(fn)->afterResponse()` (deferred ke setelah HTTP response)
- Timeout Fonnte/Wablas/Cloud diturunkan dari 8s + retry 2× → 5s + retry 1× → worst case dari 24s → 10s
- (Catatan: pendekatan `afterResponse` ini akan diganti lagi di Fase 9 karena tidak reliable di Railway PHP-FPM)

### Login Warga Disederhanakan

- Opsi login via email dihilangkan dari `/masuk` (UI & controller)
- Hanya WhatsApp OTP — konsisten dengan kanal notifikasi utama
- Method `maskEmail()` dihapus dari `WargaAuthController`

### Fix LaporanBulanan Error

**Problem**: Error `Class "Filament\Notifications\Actions\Action" not found` saat klik "Buat Laporan PDF"

**Penyebab**: Class namespace berubah di Filament 4 (unified dengan `Filament\Actions\Action`)

**Solusi**: Ganti `\Filament\Notifications\Actions\Action::make('open')` → `Action::make('open')` (sudah di-import). Plus fix bug interpolasi single-quote di judul notifikasi.

### Fix Secure File 403

**Problem**: Akses `/storage/applications/15/xxx.pdf` return 403 di admin infolist.

**Penyebab**: File memang disimpan di disk `secure` (private), tapi URL pakai `asset('storage/...')` yang mengarah ke disk public.

**Solusi**: Ubah tombol "Buka file" di `ApplicationInfolist` → `route('secure.file', ['docId' => $record->id])` yang dilayani oleh `SecureFileController` dengan otorisasi + audit log via `DataAccessLog`.

### Benchmark hasil Fase 7

| URL | Sebelum | Sesudah | Speedup |
|-----|---------|---------|---------|
| `GET /admin/login` | 0.91–1.36s | **0.14–0.24s** | 5-6× |
| `/admin` (dashboard) | 330ms | **97ms** (0 query!) | 3.4× |
| `/admin/applications` | 432ms | **243ms** | 1.8× |
| `/admin/tenants` | 608ms | **207ms** | 2.9× |
| `/` (beranda) | 1.47s | **0.25s** | 6× |
| `/layanan` | 0.60s | **0.32s** | 1.9× |

---

## Fase 8 — Deployment Production ke Railway (Selesai, Mei 2026)

Deploy aplikasi ke Railway untuk akses publik via domain `*.up.railway.app`.

### Persiapan Files Deployment

Buat 11 file baru untuk infrastructure-as-code:

| File | Fungsi |
|------|--------|
| [Dockerfile](Dockerfile) | Image build: PHP 8.3-FPM Alpine + Nginx, composer install (no-dev), npm build, optimasi |
| [.dockerignore](.dockerignore) | Exclude `vendor/`, `node_modules/`, `.env`, `database/*.sqlite`, `storage/app/*` |
| [docker/nginx.conf](docker/nginx.conf) | Nginx: PHP-FPM via unix socket, gzip, cache static, block `/storage/app/secure`, real_ip dari Railway edge |
| [docker/php.ini](docker/php.ini) | PHP production: memory 256M, upload 20M, error log stderr, timezone Jakarta |
| [docker/opcache.ini](docker/opcache.ini) | OPcache aggressive: 256MB, 20k file, JIT, `validate_timestamps=0` |
| [docker/php-fpm.conf](docker/php-fpm.conf) | PHP-FPM unix socket, pm dynamic, 2-10 worker |
| [docker/entrypoint.sh](docker/entrypoint.sh) | Bootstrap: $PORT substitusi, volume permissions, wait MySQL, migrate, seed (kalau kosong), cache config/route/view/event/filament |
| [railway.json](railway.json) | Railway config: Dockerfile builder, restart-on-failure |
| [routes/web.php:16-34](routes/web.php#L16) | Endpoint `/health` baru untuk healthcheck (cek koneksi DB) |
| [docs/DEPLOY_RAILWAY.md](docs/DEPLOY_RAILWAY.md) | Panduan deploy step-by-step lengkap + troubleshooting + biaya |

### Proses Deploy Railway (via CLI)

1. **Install Railway CLI** — `npm install -g @railway/cli`
2. **Login** — `railway login` (user authenticate via browser)
3. **Create project** — `railway init --name simpel-dinsos`
4. **Add MySQL plugin** — `railway add --database mysql`
5. **Create web service** — `railway add --service web` (empty service)
6. **Link folder** — `railway service web`
7. **Set environment variables** — `railway variables --service web --set ...` dengan reference interpolation `${{MySQL.MYSQLHOST}}` untuk auto-link ke plugin MySQL
8. **Create Volume** — `railway volume add --service <id> --mount-path /app/storage` (1 GB persistent)
9. **Generate domain** — `railway domain --service web --port 8080` → `https://web-production-3b557.up.railway.app`
10. **Deploy** — `railway up --service web --ci`

### Bug & Fix saat First Deploy

**Build sukses tapi container crash tanpa log apapun.**

**Diagnosis**: supervisord di Alpine + Railway tidak forward log ke stdout dengan benar, container exit segera setelah `docker run`.

**Fix**: Drop supervisord, ganti pendekatan single-process:
- nginx jadi PID 1 (foreground via `nginx -g "daemon off;"`)
- php-fpm jadi daemon via `php-fpm --daemonize`
- Entrypoint script handle startup langsung, exec ke nginx di akhir
- Healthcheck timeout dinaikkan dari 30s → 300s untuk handle migration time

**Hasil**:
```
GET /health: 200 | 0.87s
/ (beranda):    200 | 0.63s
/layanan:        200 | 0.41s
/admin/login:   200 | 0.87s
```

### Setup Queue Worker Service

**Problem**: True async dispatch (queue) butuh worker process yang jalan continuous. Web container hanya untuk HTTP.

**Solusi**:
- Tambah `CONTAINER_ROLE` env var di entrypoint.sh — branching ke `nginx+php-fpm` (web) atau `php artisan queue:work` (queue) atau `php artisan schedule:work` (scheduler)
- `railway add --service queue` — empty service
- Copy env vars dari web + `CONTAINER_ROLE=queue`
- Tanpa healthcheck (worker tidak listen HTTP) — railway.json di-update remove healthcheck global
- Deploy: `railway up --service queue --ci`

**Hasil**: Queue worker Online, polling tabel `jobs` setiap 3 detik, pickup & process job, mark failed_jobs kalau retry exhausted.

---

## Fase 9 — Post-Deployment Fixes (Selesai, Mei 2026)

Bug-bug yang ditemukan setelah live di Railway dan harus diperbaiki.

### Fix 1: Notifikasi WA Tidak Terkirim di Railway

**Problem**: User submit OTP di `/masuk` — tabel `otp_codes` ada record baru, tapi quota Fonnte tidak berubah. WA tidak diterima user.

**Diagnosis**:
- `dispatch(fn)->afterResponse()` tidak fire di Railway PHP-FPM + nginx (fastcgi_finish_request tidak reliable)
- Token Fonnte sudah valid (test direct ke API sukses)
- Config Laravel sudah `driver=fonnte`, `token=set`
- Code flow tidak mencapai `sendViaFonnte()` karena callback tidak pernah eksekusi

**Solusi**: Migrasi dari `afterResponse()` ke **true queue dispatch**.

- Buat [SendOtpJob](app/Jobs/SendOtpJob.php) — Job class implements `ShouldQueue`, panggil `NotificationGateway::sendOtp` di handle()
- Buat [SendApplicationNotificationJob](app/Jobs/SendApplicationNotificationJob.php) — handle 3 tipe: submitted, completed, survey
- Update 4 file controller/action:
  - [WargaAuthController::sendOtp](app/Http/Controllers/WargaAuthController.php) — `SendOtpJob::dispatch($phone, $code)`
  - [AuthApiController::sendOtp](app/Http/Controllers/Api/V1/AuthApiController.php) — sama
  - [ApplicationController::store](app/Http/Controllers/ApplicationController.php) — `SendApplicationNotificationJob::dispatch($id, 'submitted')`
  - [OperatorPekonController](app/Http/Controllers/OperatorPekonController.php) — sama
  - [ApplicationsTable.php](app/Filament/Admin/Resources/Applications/Tables/ApplicationsTable.php) (Filament action "Terbitkan") — dispatch 2 job: completed + survey

**Hasil**:
- Web response cepat (~200ms, tidak nunggu Fonnte)
- Job masuk tabel `jobs` → queue worker pickup 3 detik kemudian → kirim Fonnte
- Retry otomatis 3× kalau Fonnte sempat down
- Verified: log queue worker tunjukkan `App\Jobs\SendOtpJob ... 826ms DONE`
- Quota Fonnte turun setelah job processed → WA diterima user di HP

### Fix 2: OTP Lokal Nyangkut (XAMPP Cloudflare Tunnel)

**Problem**: Setelah migrasi ke `SendOtpJob`, OTP via `dinsos.rokifauzi.biz.id` (= XAMPP lokal tunneled) tidak terkirim. Tabel `jobs` lokal terus bertambah.

**Penyebab**: Di Railway ada service `queue` dengan worker. Di Windows lokal **tidak ada worker yang jalan**.

**Solusi**:
- Buat [start-queue-worker.bat](start-queue-worker.bat) — double-click untuk start worker manual. Auto-restart kalau crash (loop 5 detik)
- Buat [docs/QUEUE_WORKER_WINDOWS.md](docs/QUEUE_WORKER_WINDOWS.md) — panduan setup persistent via Task Scheduler:
  - PowerShell as Admin (1 command `Register-ScheduledTask`)
  - Atau GUI Task Scheduler (Create Basic Task → At startup)
  - Restart on failure, run whether user logged on or not
- Proses 3 OTP nyangkut manual dengan `php artisan queue:work --stop-when-empty`

### Fix 3: 403 Forbidden saat Login Petugas

**Problem**: User dapat 403 saat akses `/admin/login` di Railway maupun `dinsos.rokifauzi.biz.id`.

**Diagnosis**:
- User sebelumnya login sebagai warga di `/masuk` (via OTP WA) → session 'web' guard berisi warga
- Lalu user buka `/admin/login` → Filament `Authenticate` middleware cek user logged in → `canAccessPanel()` → `role->canAccessFilament()` → warga return **false** → Filament throw **403**

**Solusi**: Middleware baru [AutoLogoutNonAdmin](app/Http/Middleware/AutoLogoutNonAdmin.php) didaftarkan di `AdminPanelProvider`:
- Cek `Auth::user()` sebelum Filament panel render
- Kalau user ada DAN `role->canAccessFilament() === false` (= warga) → `Auth::logout()` + `session()->regenerate()` + redirect ke `/admin/login` dengan notice
- Hasilnya: user lihat login form admin (bukan 403), bisa login pakai akun petugas

**Verifikasi**:
- Guest → `/admin/login` → 200 ✓
- Warga login → `/admin/login` → auto-logout + tampil form login admin ✓
- Petugas login → `/admin` → dashboard Filament ✓

---

## Status Saat Ini (Mei 2026)

### Deployment Live

| Environment | URL | Status | Stack |
|-------------|-----|--------|-------|
| **Railway Production** | https://web-production-3b557.up.railway.app | ● Online | Docker · MariaDB plugin · Volume 1GB · Queue worker |
| **Cloudflare Tunnel ke XAMPP** | https://dinsos.rokifauzi.biz.id | ● Online (saat XAMPP nyala) | Apache 2.4 · MariaDB lokal · Queue worker via Task Scheduler |

### Akun Demo Aktif (Both Environments)

| Email | Password | Peran |
|-------|----------|-------|
| `admin@dinsospringsewu.test` | `password` | Administrator |
| `kadis@dinsospringsewu.test` | `password` | Kepala Dinas (2FA required) |
| `sekretaris@dinsospringsewu.test` | `password` | Sekretaris |
| `kabid.rehsos@dinsospringsewu.test` | `password` | Kabid Rehabilitasi Sosial |
| `petugas@dinsospringsewu.test` | `password` | Petugas Loket |
| `operator.pekon@dinsospringsewu.test` | `password` | Operator Pekon |

> ⚠️ **WAJIB GANTI** password sebelum production resmi.

### Statistik Code

| Aspek | Jumlah |
|-------|--------|
| Migration files | 28 |
| Filament Resources | 4 (Applications, Lks, Tenants, UgbPubPermits) |
| Filament Pages | 1 (LaporanBulanan) |
| Filament Widgets | 1 (KadisOverview) |
| Models | 18 |
| Controllers | 13 |
| Services | 11 |
| Middleware custom | 4 (IdentifyTenant, EnsureWargaRole, EnsureTwoFactor, AutoLogoutNonAdmin) |
| Job classes | 2 (SendOtpJob, SendApplicationNotificationJob) |
| Console commands | 8 |
| Total file PHP | ~150 |
| Total lines composer autoload | 7,479 classes |

### Fonnte WhatsApp Gateway

- Token: aktif (quota 970+/hari Hobby gratis)
- Device terhubung ke nomor `+62 821-7582-7721`
- Integration: queue worker → `NotificationGateway::sendViaFonnte()` → HTTP POST ke `api.fonnte.com/send`
- Tested: OTP, notif submit pengajuan, notif surat selesai, undangan SKM

---

## Biaya Hosting Railway (Estimasi Bulanan)

| Komponen | Resource | Estimasi |
|----------|----------|----------|
| Service web (Nginx + PHP-FPM) | 512 MB RAM, 0.5 vCPU | ~$5 |
| Service queue worker | 256 MB RAM, 0.25 vCPU | ~$3 |
| MySQL plugin | 1 GB storage | ~$5 |
| Volume storage `/app/storage` | 1 GB | ~$0.25 |
| Bandwidth | ~10 GB/bulan | included |
| **Total** | | **~$13/bulan** |

Railway kasih $5 free credit pertama.

---

## Roadmap Fase 10+ (Belum, Perlu Sumber Daya Eksternal)

- [ ] **Aktivasi BSrE BSSN real** — perlu kredensial resmi dari BSSN (TTE tersertifikasi)
- [ ] **WhatsApp Cloud API resmi** — pindah dari Fonnte ke Meta (gratis 1000 conv/bulan, butuh business verification)
- [ ] **Sertifikasi ISO/IEC 27001** — path 15 bulan via Certification Body terakreditasi KAN
- [ ] **Pelatihan keamanan info untuk seluruh pegawai** — annual, sesuai ISO A.6.3
- [ ] **Penetration test eksternal** — Diskominfo / BSSN
- [ ] **Custom domain `dinsos.rokifauzi.biz.id` di Railway** — pindah dari Cloudflare Tunnel XAMPP ke Railway dengan CNAME proper
- [ ] **Form pengajuan layanan di Flutter** (multipart upload berkas)
- [ ] **FCM push di Flutter** paralel dengan Web Push
- [ ] **Submit Flutter app ke Play Store + App Store**
- [ ] **Scheduler service di Railway** — untuk `pdp:scrub` + `lapor:poll` (saat ini hanya berjalan di XAMPP lokal)

---

## Pelajaran Teknis Penting (Lessons Learned)

### 1. `afterResponse` Tidak Reliable di PHP-FPM Production

`dispatch(fn)->afterResponse()` bekerja sempurna di `php artisan serve` (dev server) tapi **kadang tidak fire** di PHP-FPM behind nginx (fastcgi_finish_request behavior). Untuk production:
- ✅ Pakai true queue: `Job::dispatch()` + queue worker
- ❌ Hindari `afterResponse()` untuk operasi penting (kirim WA, email, dll)

### 2. OPcache Wajib di Production

Tanpa OPcache, PHP parse + compile ~7000 file tiap request. Setelah aktif:
- Response time turun 60-80%
- Memory tetap stabil (cache di shared memory)
- Production setting: `validate_timestamps=0` + restart container/Apache tiap deploy

### 3. SQLite Cocok Dev, Tidak Cocok Production Concurrent

SQLite punya **whole-database lock** saat write. Untuk 1-2 user concurrent OK, untuk Dinsos dengan banyak operator + petugas concurrent submit/update — pasti deadlock.

MySQL row-level lock jauh lebih scalable. Plus support tipe data JSON yang proper.

### 4. Filament `canAccessPanel` Return False = 403

Filament tidak gracefully redirect saat user tidak boleh akses panel. Default = throw 403. Untuk UX yang lebih baik, butuh middleware custom yang auto-logout + redirect.

### 5. Railway Volume vs Shared Filesystem

Railway Volume **per-service**, tidak shared. Kalau 2 service butuh akses file yang sama (mis. web upload, queue baca), pilih:
- Single service dengan multi-process (rumit untuk Laravel)
- Atau pakai S3/MinIO bersama

Untuk kasus SIMPEL DINSOS, web service punya volume untuk berkas KTP/KK. Queue worker tidak butuh akses file (HTTP outbound only) — jadi tidak masalah.

### 6. Filament 4 Namespace Changes

Beberapa class dari Filament 3 berubah di v4:
- `Filament\Notifications\Actions\Action` → `Filament\Actions\Action` (unified)
- `Filament\Forms\Components\Action` → `Filament\Actions\Action` juga
- Reference: vendor src/, jangan asumsi nama lama

---

## Kontribusi & Maintenance

### Repository
- GitHub: https://github.com/RokiFauziErenJaegar/Simpel-Dinsos
- Branch utama: `main`
- Style commit message: Bahasa Indonesia ringkas, deskriptif

### Cara Re-deploy ke Railway
```bash
cd C:\xampp\htdocs\simpel-dinsos
git add . && git commit -m "deskripsi perubahan"
git push                                # push ke GitHub
railway up --service web                # deploy web
railway up --service queue              # deploy queue (kalau code job berubah)
```

### Cara Re-deploy ke XAMPP Lokal (Cloudflare Tunnel)
```bash
cd C:\xampp\htdocs\simpel-dinsos
git pull                                # kalau kode dari GitHub
php artisan migrate                     # apply migrasi baru
php artisan optimize:clear              # clear cache lama
php artisan filament:optimize           # cache komponen Filament
php artisan queue:restart               # restart queue worker (signal)
```

Pastikan `start-queue-worker.bat` jalan, atau Task Scheduler aktif.

### Dokumentasi Tambahan

- [README.md](README.md) — overview & cara setup awal
- [docs/DEPLOY_RAILWAY.md](docs/DEPLOY_RAILWAY.md) — deploy ke Railway step-by-step
- [docs/QUEUE_WORKER_WINDOWS.md](docs/QUEUE_WORKER_WINDOWS.md) — setup worker di Windows XAMPP
- [docs/WA_GATEWAY_SETUP.md](docs/WA_GATEWAY_SETUP.md) — setup Fonnte/Wablas/Cloud API
- [docs/SECURITY_CHECKLIST.md](docs/SECURITY_CHECKLIST.md) — checklist UU PDP + ISO 27001
- [docs/policies/](docs/policies/) — 4 dokumen ISO 27001 (AUP, IR Runbook, BCP, DPA)

---

## Penutup

Pengembangan SIMPEL DINSOS Pringsewu telah melalui **9 fase major** dari MVP sampai production deployment, dengan total ~150 file PHP dan 28 migration. Aplikasi sudah berjalan di 2 environment (Railway + Cloudflare Tunnel XAMPP) dengan fitur lengkap:

✅ 16 layanan publik dengan workflow petugas lengkap
✅ Login OTP WhatsApp via Fonnte (real, bukan demo)
✅ TV Lobi realtime dengan TTS Bahasa Indonesia
✅ Kiosk self-service walk-in
✅ Operator Pekon (proxy submit untuk warga tidak bisa online)
✅ Dashboard Kadis dengan 6 KPI + laporan bulanan PDF untuk Bupati
✅ 2FA untuk admin/kadis (TOTP Google Authenticator)
✅ Enkripsi NIK/KK at-rest + audit log akses (UU PDP compliance)
✅ PWA installable di HP (manifest + service worker + web push)
✅ REST API v1 dengan Sanctum Bearer token (mobile-ready)
✅ Multi-tenant siap ekspansi ke 15 kabupaten lain
✅ WhatsApp Bot inbound + simulator
✅ Lapor.go.id (SP4N) integration siap
✅ Flutter starter project untuk mobile native
✅ Performance dioptimasi (5-6× lebih cepat dengan OPcache + cache layer)
✅ Deployed ke Railway dengan queue worker service terpisah
✅ Dokumentasi lengkap untuk operasional & compliance

> *"Mudah, cepat, dan tanpa biaya — Cepat, Adaptif, Responsif, Empati."*

🤖 Aplikasi awal di-scaffold dengan bantuan Claude Code, mengikuti SOP resmi Dinas Sosial Kabupaten Pringsewu Maklumat No. 920/460/D.04/X/2023.
