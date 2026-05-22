# SIMPEL DINSOS Pringsewu

**Sistem Informasi Manajemen Pelayanan Dinas Sosial Kabupaten Pringsewu**

Aplikasi web Laravel untuk digitalisasi 16 layanan publik Dinas Sosial Kabupaten Pringsewu — sesuai SOP Maklumat 920/460/D.04/X/2023. Mendukung pengajuan online, antrian digital, workflow petugas, dashboard Kadis, dan display TV lobi.

> *"Mudah, cepat, dan tanpa biaya — Cepat, Adaptif, Responsif, Empati."*

---

## Stack Teknis

| Komponen | Versi |
|---|---|
| Laravel | 12.x |
| PHP | 8.3+ |
| Filament | 4.x (admin panel petugas + Kadis) |
| Livewire | 3.x |
| Tailwind CSS | 4.x via Vite |
| Database | **MySQL / MariaDB 10.4+** (XAMPP). SQLite hanya dipakai sebagai jembatan migrasi data lama via koneksi `sqlite_legacy`. |
| QR Code | simplesoftwareio/simple-qrcode |
| PDF | barryvdh/laravel-dompdf |

---

## Persiapan & Menjalankan

```bash
# 1. Masuk ke folder proyek
cd C:\xampp\htdocs\simpel-dinsos

# 2. Install dependencies
composer install
npm install

# 3. Pastikan MySQL/MariaDB XAMPP jalan, lalu buat database
mysql -uroot -e "CREATE DATABASE simpel_dinsos CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"

# 4. Set kredensial di .env (default XAMPP):
#    DB_CONNECTION=mysql
#    DB_HOST=127.0.0.1
#    DB_PORT=3306
#    DB_DATABASE=simpel_dinsos
#    DB_USERNAME=root
#    DB_PASSWORD=

# 5. Migrasi & seed database
php artisan migrate:fresh --seed

# 6. Build asset frontend
npm run build

# 7. Storage symlink
php artisan storage:link

# 8. Optimasi (penting! gain 3-5x performa)
php artisan optimize
php artisan filament:optimize
php artisan icons:cache

# 9. Jalankan server
php artisan serve
# Akses: http://127.0.0.1:8000
```

> **Penting**: Aktifkan **OPcache** di `C:\xampp\php\php.ini` (uncomment `zend_extension=opcache`, set `opcache.enable=1` & `opcache.enable_cli=1`). Tanpa OPcache, response 3-5x lebih lambat karena PHP parse ribuan file Laravel+Filament tiap request.

Untuk development asset live-reload, di terminal terpisah:
```bash
npm run dev
```

### Migrasi dari SQLite lama (one-time)

Kalau punya `database/database.sqlite` lama dan ingin pindah ke MySQL:

```bash
# 1. Backup dulu
cp database/database.sqlite database/database.sqlite.backup

# 2. Pastikan .env sudah pakai MySQL & migration MySQL sudah jalan
php artisan migrate --force

# 3. Transfer data
php artisan db:migrate-from-sqlite --fresh
```

Command ini membaca dari koneksi `sqlite_legacy` (file SQLite lama, read-only) dan salin per-tabel ke koneksi `mysql` default dengan urutan FK yang benar. Tabel `cache`, `sessions`, `jobs`, `migrations`, dan `password_reset_tokens` di-skip otomatis.

---

## Akun Demo

Semua akun memakai password **`password`**.

| Email | Peran | Akses |
|---|---|---|
| `admin@dinsospringsewu.test` | Administrator | Akses penuh `/admin` |
| `kadis@dinsospringsewu.test` | Kepala Dinas | Dashboard pemantauan & laporan Bupati |
| `sekretaris@dinsospringsewu.test` | Sekretaris | Disposisi & koordinasi |
| `kabid.rehsos@dinsospringsewu.test` | Kabid Rehabilitasi Sosial | Disposisi tim |
| `petugas@dinsospringsewu.test` | Petugas Loket | Verifikasi berkas, workflow harian |
| `operator.pekon@dinsospringsewu.test` | Operator Pekon | Input layanan atas nama warga |
| `budi@warga.test` | Warga (demo) | Akses publik sebagai pemohon |
| `siti@warga.test` | Warga (demo) | Akses publik sebagai pemohon |

---

## URL Penting

| Tujuan | URL |
|---|---|
| Beranda publik | http://127.0.0.1:8000/ |
| Katalog 16 layanan | http://127.0.0.1:8000/layanan |
| Detail layanan (contoh) | http://127.0.0.1:8000/layanan/surat-keterangan-dtsen |
| Form pengajuan | http://127.0.0.1:8000/layanan/surat-keterangan-dtsen/ajukan |
| Cek status | http://127.0.0.1:8000/cek-status |
| Pengaduan publik | http://127.0.0.1:8000/pengaduan |
| Verifikasi dokumen | http://127.0.0.1:8000/verify/{token} |
| **Display TV Lobi (fullscreen)** | http://127.0.0.1:8000/tv |
| API Antrian Live (JSON) | http://127.0.0.1:8000/tv/live |
| **Filament Admin Panel** | http://127.0.0.1:8000/admin |

---

## Fitur yang Sudah Tersedia (Fase 1 MVP)

### Publik (untuk Warga)
- ✅ Landing page dengan statistik real-time & antrian live
- ✅ Katalog 16 layanan dengan filter pencarian
- ✅ Halaman detail layanan (persyaratan, prosedur, output)
- ✅ Form pengajuan layanan (multi-section, upload berkas, consent UU PDP)
- ✅ Halaman sukses dengan nomor antrian
- ✅ Cek status pengajuan dengan timeline real-time
- ✅ Form pengaduan masyarakat (web, opsi anonim)
- ✅ Halaman verifikasi keaslian dokumen ber-QR
- ✅ Footer dengan kanal pengaduan (Call Center, Email, Lapor.go.id)

### Internal (Filament Admin)
- ✅ Login pegawai dengan role-based access (Admin / Kadis / Sekretaris / Kabid / Kasi / Petugas / Operator Pekon)
- ✅ Dashboard Kadis: 6 KPI utama (Pemohon Bulan Ini, Ketepatan SLA, Pengaduan Aktif, Lewat SLA, Dilayani Hari Ini, Indeks Kepuasan)
- ✅ Resource Pengajuan Layanan:
  - Tabel dengan filter status, jenis layanan, pencarian
  - Tampilan SLA countdown real-time
  - Workflow action: Setujui / Kembalikan / Tolak / Terbitkan Surat
  - Detail pengajuan dengan timeline lengkap & semua berkas
- ✅ Badge notifikasi pengajuan aktif di sidebar
- ✅ Audit log otomatis di setiap transisi status

### Display TV Lobi
- ✅ Fullscreen mode dengan auto-refresh 5 detik (Alpine.js + fetch)
- ✅ Panel "Sedang Dilayani" (3 loket) + "Antrian Berikutnya"
- ✅ Statistik hari ini & bulan ini
- ✅ Daftar layanan ringkas
- ✅ QR code untuk daftar online
- ✅ Marquee footer dengan info kontak

---

## Struktur Direktori Penting

```
simpel-dinsos/
├── app/
│   ├── Enums/
│   │   ├── ApplicationStatus.php       # State machine status pengajuan
│   │   └── UserRole.php                # 8 peran pengguna
│   ├── Filament/Admin/
│   │   ├── Resources/Applications/     # CRUD + workflow pengajuan
│   │   └── Widgets/KadisOverview.php   # Stats dashboard Kadis
│   ├── Http/Controllers/
│   │   ├── PublicController.php        # Beranda, katalog, status, aduan, verify
│   │   ├── ApplicationController.php   # Form & submit pengajuan
│   │   └── TvDisplayController.php     # Display lobi & live JSON
│   └── Models/
│       ├── Application.php             # entitas pusat (state machine)
│       ├── ApplicationDocument.php
│       ├── ApplicationLog.php          # audit timeline
│       ├── OutputDocument.php          # surat hasil ber-QR
│       ├── QueueTicket.php             # antrian harian
│       ├── ServiceType.php             # master 16 layanan
│       ├── Complaint.php
│       ├── Kecamatan.php / Pekon.php
│       ├── PpksProfile.php             # data PPKS + DTSEN desil
│       └── User.php
├── database/
│   ├── migrations/                     # 11 migrasi inti
│   └── seeders/
│       ├── KecamatanSeeder.php         # 9 kecamatan Pringsewu + pekon
│       ├── ServiceTypeSeeder.php       # 16 layanan dari SOP
│       └── UserSeeder.php              # 8 akun demo + 1 PPKS profil
├── resources/views/
│   ├── layouts/public.blade.php        # Navbar + footer publik
│   └── public/                         # Semua view publik & TV
└── routes/web.php                      # Route publik + TV
```

---

## Workflow Pengajuan (State Machine)

```
[Pemohon submit di /layanan/{slug}/ajukan]
        ↓
[status: submitted]   — Tiket antrian diterbitkan (mis. A-001)
        ↓
[Petugas Loket: aksi "Setujui"]
        ↓
[status: in_process]  — current_handler ditugaskan
        ↓
[Petugas: aksi "Terbitkan Surat"]
        ↓
[OutputDocument dibuat: nomor surat, QR token, signed_by]
        ↓
[status: completed]   — Pemohon dinotifikasi (kode + tautan unduh)
```

Aksi alternatif tersedia di setiap step:
- **Kembalikan** ke pemohon dengan alasan (status → `returned`)
- **Tolak** dengan alasan (status → `rejected`, final)

Semua transisi tercatat di `application_logs` (audit trail).

---

## Cara Demo Cepat

1. **Buka beranda**: http://127.0.0.1:8000/
   → Lihat statistik antrian live (A-001 menunggu, A-002 sedang dilayani LOKET 1)
2. **Buka TV Lobi**: http://127.0.0.1:8000/tv
   → Tampilan fullscreen untuk display lobi kantor
3. **Cek status pengajuan**: http://127.0.0.1:8000/cek-status?code=SURAT-2026-0001
   → Timeline lengkap pengajuan Budi Santoso
4. **Ajukan layanan baru**: http://127.0.0.1:8000/layanan
   → Pilih layanan, lengkapi form & upload berkas, dapat nomor antrian
5. **Login petugas**: http://127.0.0.1:8000/admin
   → `petugas@dinsospringsewu.test` / `password`
   → Kelola pengajuan, klik aksi Setujui / Terbitkan
6. **Login Kadis**: `kadis@dinsospringsewu.test` / `password`
   → Lihat dashboard KPI 6 kartu untuk laporan ke Bupati

---

## Fase 2 (Selesai)

- [x] **PDF generator surat resmi + QR code embed** — `App\Services\DocumentGenerator` + template `documents/surat-generic.blade.php`. Filament action "Terbitkan" sekarang menghasilkan PDF A4 dengan kop, nomor 920/xxx/D.04/M/Y, QR verifikasi, dan blok tanda tangan Kadis. Output ~730 KB per surat.
- [x] **NotificationGateway (adapter WA/Email)** — `App\Services\NotificationGateway` dengan driver `log` (default, tulis ke `storage/app/private/outbox/{tanggal}.log`), `fonnte`, `wablas`. Trigger otomatis di: submit, complete, OTP, survei SKM. Tinggal pasang token kredensial untuk produksi.
- [x] **Login warga via OTP WhatsApp** — `/masuk` → input HP → `/masuk/verifikasi/{phone}` → kode 6 digit (berlaku 5 menit, max 5 percobaan). OTP dikirim via NotificationGateway. Auto-create user warga jika belum ada.
- [x] **Mode Operator Pekon** — `/pekon` (middleware `role:operator_pekon`). Daftarkan warga ke 16 layanan dengan PIN e-sign Kepala Pekon (demo: `123456`). Auto-isi data warga (kecamatan & pekon dari profil operator).
- [x] **Survei Kepuasan Masyarakat (SKM)** — `/skm/{code}` form 9 unsur Permenpan RB 14/2017 dengan emoji rating. Otomatis terhitung indeks (rata-rata × 20). Undangan dikirim via WA stub sesaat setelah pengajuan selesai.
- [x] **Ekspor laporan bulanan PDF Bupati** — Filament page `/admin/laporan-bulanan`. Kadis pilih periode → klik "Buat Laporan PDF" → A4 lengkap dengan KPI, performa per layanan, statistik pengaduan, ringkasan eksekutif otomatis, & blok tanda tangan. Output ~870 KB.
- [x] **Audio TTS panggilan antrian di TV lobi** — `tv.blade.php` deteksi `last_called` baru tiap polling, lalu: (1) bunyi *ding* via Web Audio API, (2) TTS Bahasa Indonesia via Web Speech API ("Nomor antrian A nol dua tujuh, silakan menuju loket satu"), (3) diulang 2x. Auto-enable setelah klik pertama (browser policy).
- [x] **Kiosk self-service lobi** — `/kiosk` halaman touchscreen mandiri untuk warga walk-in: ambil nomor antrian, pilih prioritas (lansia/disabilitas), cetak tiket termal otomatis, auto-redirect kembali ke menu setelah 30 detik.
- [x] **Modul LKS Registry** — Filament resource untuk lembaga/yayasan/panti terdaftar (akta notaris, NPWP, masa berlaku, jumlah klien). Akses dari sidebar admin.
- [x] **Stub Dukcapil + DTSEN** — `App\Services\DukcapilService` & `DtsenService` dengan driver `mock` (data deterministik dari NIK) dan `http` (siap pasang token). Tersedia juga endpoint AJAX `GET /api/nik/{nik}` untuk auto-fill form di masa depan.

## Fase 3 (Selesai)

- [x] **PDF surat dengan stempel + tanda tangan scan** — `SignatureAssetGenerator` auto-generate aset SVG (cap bundar resmi + tanda tangan cursive) untuk demo. Di produksi, Kadis upload PNG hasil scan ke `users.signature_path` & `stamp_path`. Template `surat-generic.blade.php` embed gambar dengan posisi absolut: stempel di belakang, TTD di atasnya.
- [x] **Modul UGB/PUB perizinan (L04)** — Filament resource lengkap di `/admin/ugb-pub-permits` dengan migration `ugb_pub_permits` (badan hukum, akta notaris, NPWP, NIB, target dana, area scope, lokasi). Badge sidebar auto-update untuk permit `diajukan`.
- [x] **WhatsApp Bot inbound + simulator** — `App\Services\WhatsAppBot` state-machine 4 menu (cek status / aduan / daftar layanan / kontak). Webhook `/webhook/wa` siap dipasang ke Fonnte/Wablas (verifikasi `X-Webhook-Token`). Simulator UI mirip WhatsApp di `/wa-demo` untuk demo tanpa gateway.
- [x] **Lapor.go.id (SP4N) adapter + scheduler** — `LaporGoIdService` dengan driver mock + http. `php artisan lapor:poll` jalan otomatis tiap 15 menit via `Schedule::command()`. Aduan baru langsung muncul di tabel `complaints` dengan `channel=lapor`.
- [x] **Realtime Laravel Reverb WebSocket** — Event `QueueTicketCalled` broadcast ke public channel `antrian`. TV Lobi subscribe via Echo + Pusher driver. Aksi Filament "Panggil" di Pengajuan langsung trigger TTS audio di TV tanpa polling (polling tetap dijaga sebagai fallback).
- [x] **Storage S3-compatible / MinIO** — Disk `minio` & `secure` ditambahkan di `config/filesystems.php`. Tinggal isi `MINIO_*` di `.env` dan ganti `SECURE_DISK_DRIVER=minio` untuk pindahkan berkas KTP/KK/foto PPKS ke MinIO on-premise.
- [x] **Auto-discovery Lapor.go.id via cron** — `routes/console.php` register schedule `everyFifteenMinutes` untuk `lapor:poll`. Cukup tambahkan `php artisan schedule:work` di `supervisord` atau cron `* * * * *` artisan schedule:run.
- [x] **Penetration test + UU PDP compliance checklist** — Dokumen acuan untuk Inspektorat / Diskominfo di [`docs/SECURITY_CHECKLIST.md`](docs/SECURITY_CHECKLIST.md). Mencakup 7 area teknis (pengerasan, auth, injection, XSS/CSRF, file storage, network, pengujian) + 8 area UU PDP (dasar hukum, hak subjek data, minimisasi, retensi, pelindungan teknis, DPO, pelanggaran data, pihak ketiga).

## Konfigurasi Tambahan (Fase 3)

```env
# Reverb WebSocket (otomatis dikonfigurasi oleh installer)
BROADCAST_CONNECTION=reverb
REVERB_APP_ID=505077
REVERB_APP_KEY=...
REVERB_APP_SECRET=...
REVERB_HOST=localhost
REVERB_PORT=8080
REVERB_SCHEME=http

# Storage MinIO (opsional, on-premise S3-compatible)
MINIO_KEY=minioadmin
MINIO_SECRET=minioadmin
MINIO_BUCKET=simpel-dinsos
MINIO_ENDPOINT=http://127.0.0.1:9000
SECURE_DISK_DRIVER=local      # ganti ke 'minio' jika MinIO siap

# Lapor.go.id (kredensial Kemenpan)
LAPOR_DRIVER=mock              # ganti ke 'http' di produksi
LAPOR_BASE_URL=
LAPOR_TOKEN=
LAPOR_INSTANSI_ID=

# Webhook WhatsApp Bot
NOTIFICATION_WEBHOOK_TOKEN=     # token verifikasi inbound dari gateway
```

## URL Baru Fase 3

| Tujuan | URL |
|---|---|
| Simulator WhatsApp Bot | http://127.0.0.1:8000/wa-demo |
| Webhook WA inbound (POST) | http://127.0.0.1:8000/webhook/wa |
| UGB/PUB perizinan (admin) | http://127.0.0.1:8000/admin/ugb-pub-permits |
| Verifikasi PDF baru (TTD + cap) | http://127.0.0.1:8000/verify/{token} |

## Menjalankan Reverb WebSocket (untuk realtime TV lobi)

Buka **terminal terpisah**, jalankan:

```bash
cd C:\xampp\htdocs\simpel-dinsos
php artisan reverb:start
```

Lalu di terminal lain untuk scheduler Lapor.go.id:

```bash
php artisan schedule:work
```

TV lobi (`/tv`) akan otomatis menerima push tiket dipanggil tanpa polling. Polling 5 detik tetap jalan sebagai fallback jika Reverb mati.

## Fase 4 (Selesai)

- [x] **Audit log akses baca data PPKS** — Tabel `data_access_logs` + `ApplicationAccessObserver` otomatis catat akses pegawai ke detail pengajuan. Akses portabilitas oleh subjek juga ter-log. Retensi 2 tahun via `pdp:scrub`.
- [x] **Enkripsi at-rest NIK & KK** — `users.nik`, `applications.beneficiary_nik`, `ppks_profiles.family_card_no` di-cast `encrypted`. Untuk `users.nik` (unique-indexed) ditambah kolom `nik_hash` (SHA-256 HMAC dengan APP_KEY) untuk lookup. Helper `User::findByNik($plain)` + mutator `setNikAttribute`. Plaintext NIK tidak pernah disimpan di DB.
- [x] **Soft-delete + scheduled scrub** — Trait `SoftDeletes` di model utama + command `pdp:scrub` (dry-run + force) yang menghapus berkas KTP/KK > 3 tahun, OTP expired, access log > 2 tahun, soft-deleted > 30 hari. Schedule harian 02:00 WIB di `routes/console.php`.
- [x] **Hak portabilitas + hak hapus data warga** — `WargaDataRightsController` di `/akun/data-saya`. Tombol "Unduh Data Saya (JSON)" memberi semua data warga sebagai file JSON sesuai UU PDP Pasal 13. Tombol "Hapus Data Saya" memerlukan konfirmasi typed (`HAPUS DATA SAYA`), lakukan soft-delete cascade, log alasan, dan trigger scrub permanen 30 hari kemudian.
- [x] **2FA Google Authenticator (TOTP)** — Paket `pragmarx/google2fa` + `TwoFactorService`. Halaman `/akun/2fa` tampilkan QR code untuk scan via Google Authenticator/Authy. Setelah konfirmasi: terbit 8 recovery codes one-time-use. Middleware `EnsureTwoFactor` di Filament panel paksa Admin & Kadis aktifkan 2FA, dan paksa challenge `/2fa/verifikasi` di tiap sesi baru. Recovery code valid sebagai fallback.
- [x] **REST API untuk Mobile (Sanctum)** — `php artisan install:api` installed. Endpoint v1:
  - `GET /api/v1/services` daftar 16 layanan
  - `GET /api/v1/services/{slug}` detail layanan
  - `GET /api/v1/queue/status` antrian live
  - `GET /api/v1/applications/{code}` status pengajuan + timeline
  - `POST /api/v1/auth/send-otp` kirim OTP ke HP
  - `POST /api/v1/auth/verify-otp` verifikasi + dapatkan Bearer token
  - `GET /api/v1/auth/me` profil user
  - `GET /api/v1/my/applications` daftar pengajuan saya
  - `POST /api/v1/auth/logout` revoke token
- [x] **Multi-tenant config stub** — `config/tenant.php` + `docs/MULTI_TENANT.md`. Sistem siap diekspansi ke 15 kabupaten lain via mode `shared-db` atau `per-db`. Saat ini mode `single` (Pringsewu).

## Konfigurasi Tambahan (Fase 4)

```env
# Multi-tenant (default single untuk Pringsewu)
TENANT_MODE=single                    # single | shared-db | per-db
TENANT_ID=pringsewu
TENANT_NAME="Kabupaten Pringsewu"
TENANT_KODE_WILAYAH=187103
TENANT_INSTANSI="Dinas Sosial Kabupaten Pringsewu"
TENANT_CALL_CENTER=0822-6986-7911

# Email laporan PDP retention scrub
PDP_REPORT_EMAIL=dpo@dinsospringsewu.go.id
```

## URL Baru Fase 4

| Tujuan | URL |
|---|---|
| Hak atas data saya (UU PDP) | `/akun/data-saya` (auth) |
| Ekspor JSON data warga | `/akun/data-saya/ekspor` (auth) |
| Setup 2FA TOTP | `/akun/2fa` (auth) |
| Challenge 2FA | `/2fa/verifikasi` |
| **REST API v1 base** | `/api/v1/...` (mobile-ready) |

## Cara Aktifkan 2FA untuk Kadis (Demo)

1. Login Kadis di `/admin` → redirect otomatis ke `/akun/2fa` (karena middleware wajib 2FA aktif)
2. Pindai QR di halaman dengan Google Authenticator
3. Masukkan kode 6 digit dari authenticator → klik Aktifkan
4. Simpan 8 recovery code yang muncul
5. Logout & login ulang → akan diminta kode 6 digit di `/2fa/verifikasi`

## REST API Demo (curl)

```bash
# 1. Kirim OTP ke nomor
curl -X POST http://127.0.0.1:8000/api/v1/auth/send-otp \
  -H "Content-Type: application/json" \
  -d '{"phone":"081234567890"}'

# Cek kode OTP di storage/app/private/outbox/2026-MM-DD.log

# 2. Verifikasi + dapatkan token
curl -X POST http://127.0.0.1:8000/api/v1/auth/verify-otp \
  -H "Content-Type: application/json" \
  -d '{"phone":"081234567890","code":"123456","device_name":"iPhone 15"}'

# 3. Akses endpoint protected
curl http://127.0.0.1:8000/api/v1/auth/me \
  -H "Authorization: Bearer {token}"
```

## Fase 5 (Selesai)

- [x] **PWA — Mobile App via Web** — `manifest.webmanifest` + `sw.js` + ikon SVG (192/512/maskable). Banner install dengan dismiss 7 hari, offline shell + halaman `/offline`, app shortcuts (Ajukan/Status/Lapor), strategi cache: network-first untuk HTML, cache-first untuk asset. Halaman `/pwa-test` untuk verifikasi device. Push notification API siap (`/pwa/subscribe`, `/pwa/vapid-key`).
- [x] **Multi-tenant Penuh** — Tabel `tenants` + kolom `tenant_id` di 14 tabel utama. Trait `BelongsToTenant` dengan global scope filtering otomatis (aktif kalau `TENANT_MODE != single`). Middleware `IdentifyTenant` resolve via subdomain (mis. `pringsewu.simpel-dinsos.id`) atau header `X-Tenant` (API). Seeder tambah 3 tenant: Pringsewu (aktif), Pesawaran & Tanggamus (non-aktif untuk demo siap-pakai).
- [x] **Storage Sensitif default ke `secure` disk** — `ApplicationController` upload langsung ke disk `secure` (di luar `public/`). Endpoint `/secure-file/{docId}` serve berkas dengan auth + audit log via `SecureFileController`. Command `php artisan storage:migrate-sensitive --dry-run` untuk migrasi berkas lama dari `public` → `secure`. Tinggal `SECURE_DISK_DRIVER=minio` untuk pindah ke MinIO/S3.
- [x] **BSrE BSSN TTE Adapter** — `App\Services\BsreService` dengan driver `mock` (tambah metadata marker, untuk demo) dan `http` (panggil API BSSN resmi). Otomatis trigger setelah `DocumentGenerator::issue()` kalau `BSRE_ENABLED=true`. Update `file_path` & `file_hash` ke versi tertandatangani.
- [x] **Push Notification Stub** — Tabel `push_subscriptions` + endpoint subscribe + halaman `/pwa-test` dengan tombol "Test Notifikasi Lokal" yang langsung kirim notification via service worker. Tinggal pasang VAPID key untuk Web Push real.
- [x] **ISO/IEC 27001 Gap Analysis** — Dokumen [`docs/ISO_27001_GAP_ANALYSIS.md`](docs/ISO_27001_GAP_ANALYSIS.md) memetakan 93 kontrol Annex A. Skor: 60% terimplementasi, 28% sebagian, 12% perlu kerja. Path 15 bulan ke sertifikasi via CB akreditasi KAN.
- [x] **Flutter / React Native Starter** — Dokumen [`docs/MOBILE_APP_GUIDE.md`](docs/MOBILE_APP_GUIDE.md) dengan 3 opsi (PWA / Flutter / RN), struktur folder Flutter, sample API client Dio + interceptor, estimasi MVP 15 hari.

## Konfigurasi Tambahan (Fase 5)

```env
# Multi-tenant
TENANT_MODE=single                  # single | shared-db | per-db

# Storage berkas sensitif
SECURE_DISK_DRIVER=local            # ganti ke 'minio' untuk on-premise S3
MINIO_KEY=
MINIO_SECRET=
MINIO_BUCKET=simpel-dinsos
MINIO_ENDPOINT=http://127.0.0.1:9000

# BSrE BSSN (TTE tersertifikasi)
BSRE_DRIVER=mock                    # mock | http
BSRE_ENABLED=false                  # set true untuk auto-sign saat Terbitkan Surat
BSRE_BASE_URL=
BSRE_USER=
BSRE_PASS=
BSRE_PASSPHRASE=

# Web Push (opsional)
VAPID_PUBLIC_KEY=
VAPID_PRIVATE_KEY=
```

## URL Baru Fase 5

| Tujuan | URL |
|---|---|
| Web App Manifest (PWA) | `/manifest.webmanifest` |
| Service Worker | `/sw.js` |
| Halaman offline fallback | `/offline` |
| Test PWA & Push di device | `/pwa-test` |
| Download berkas sensitif (auth) | `/secure-file/{docId}` |
| Subscribe push notification | POST `/pwa/subscribe` |

## Cara Install PWA di HP

**Android Chrome:** Akses URL → banner "Pasang SIMPEL DINSOS" muncul → "Pasang →". Atau menu titik tiga → "Install app".

**iOS Safari 16.4+:** Akses URL → tap Share → "Add to Home Screen".

## Fase 6 (Selesai)

- [x] **Web Push Real (VAPID)** — Paket `minishlink/web-push` terpasang, VAPID key pair di-generate (via Node.js karena XAMPP PHP belum ada EC curve), `WebPushService` mengirim push beneran via protokol Web Push ke FCM/Mozilla/Apple. Halaman `/pwa-test` punya tombol "Subscribe Server Push" + "Kirim Push dari Server" untuk demo end-to-end. Expired subscriptions auto-prune saat send.
- [x] **Filament Tenant Resource** — `/admin/tenants` UI manajemen tenant (admin only). Form bersection (Identitas, Kontak, Branding, Settings JSON), aksi Aktifkan/Nonaktifkan satu klik. Onboarding kabupaten baru cukup via UI. 3 tenant tersimpan: Pringsewu (aktif), Pesawaran, Tanggamus.
- [x] **ISO 27001 Supporting Docs (4 dokumen)** — Menutup gap utama dari Fase 5:
  - [`docs/policies/ACCEPTABLE_USE_POLICY.md`](docs/policies/ACCEPTABLE_USE_POLICY.md) — AUP pegawai (A.5.10, A.6.1-A.6.6)
  - [`docs/policies/INCIDENT_RESPONSE_RUNBOOK.md`](docs/policies/INCIDENT_RESPONSE_RUNBOOK.md) — Runbook NIST SP 800-61 dengan skenario P0-P3 + UU PDP Pasal 46 (A.5.24-A.5.30)
  - [`docs/policies/BUSINESS_CONTINUITY_PLAN.md`](docs/policies/BUSINESS_CONTINUITY_PLAN.md) — BCP/DRP dengan RTO/RPO 6 layanan (A.5.29-A.5.30)
  - [`docs/policies/DATA_PROCESSING_AGREEMENT_TEMPLATE.md`](docs/policies/DATA_PROCESSING_AGREEMENT_TEMPLATE.md) — Template DPA per vendor sesuai UU PDP Pasal 51-52 (A.5.19-A.5.22)
- [x] **MinIO Docker Compose** — [`infra/docker-compose.minio.yml`](infra/docker-compose.minio.yml) + [`infra/setup-minio.sh`](infra/setup-minio.sh). Jalankan dengan satu command `bash infra/setup-minio.sh`. Console UI di `:9001`, S3 API di `:9000`. Auto-create bucket `simpel-dinsos`. Ready untuk `SECURE_DISK_DRIVER=minio`.
- [x] **Flutter Starter Project Lengkap** — Folder [`mobile/flutter/`](mobile/flutter/) berisi 18 file: pubspec.yaml dengan 14 dependencies (Dio, Riverpod, GoRouter, secure_storage, dst), main.dart + app.dart entry, core (API client, router dengan auth guard, theme brand), features (auth login+OTP, home shell, services list+detail, my applications, application detail dengan timeline, profile), models (ServiceType, Application). README mobile dengan instruksi setup, build APK & iOS, TODO. State: Riverpod 2 AsyncNotifier. Routing: GoRouter dengan auth redirect. Storage: flutter_secure_storage untuk Bearer token.

## Konfigurasi Tambahan (Fase 6)

```env
# Web Push (VAPID) — generated via:
# node -e "console.log(require('web-push').generateVAPIDKeys())"
VAPID_PUBLIC_KEY="BNr3a0nFnSZi..."
VAPID_PRIVATE_KEY="LGw_k4f9xnVK..."
VAPID_SUBJECT="mailto:pringsewudinsos@gmail.com"
VITE_VAPID_PUBLIC_KEY="${VAPID_PUBLIC_KEY}"
```

## URL Baru Fase 6

| Tujuan | URL |
|---|---|
| Manajemen Tenant (admin only) | `/admin/tenants` |
| Test Web Push dari server | POST `/pwa/test-push` (auth) |
| VAPID Public Key | `/pwa/vapid-key` |

## Cara Demo Fase 6

1. **Web Push beneran**: login warga di `/masuk` → buka `/pwa-test` → klik "Subscribe Server Push" → allow notifikasi → klik "Kirim Push dari Server" → notif muncul di tray (bahkan saat tab tertutup, bahkan saat HP di-lock).
2. **Tenant management**: login admin → klik sidebar "Tenant / Kabupaten" → klik "Aktifkan" untuk Pesawaran/Tanggamus. Untuk multi-tenant beneran, set `TENANT_MODE=shared-db` di `.env`.
3. **ISO 27001 docs**: baca 4 dokumen di `docs/policies/`. Kustomisasi placeholder (TBD) sesuai pegawai aktual sebelum berlaku formal.
4. **MinIO**: `cd infra && bash setup-minio.sh` (perlu Docker). Akses console `http://localhost:9001` → buat access key → update `.env` dengan `SECURE_DISK_DRIVER=minio` → restart server → upload baru langsung ke MinIO bucket.
5. **Flutter**: `cd mobile/flutter && flutter pub get && flutter run` (perlu Flutter SDK + emulator). Login dengan nomor demo `081200000001` (Budi) → OTP di outbox file → masuk ke home dengan 16 layanan.

## Fase 7 — Migrasi Produksi & Performa (Selesai, Mei 2026)

- [x] **Migrasi SQLite → MySQL/MariaDB** — Default database pindah ke MariaDB 10.4 (XAMPP). Connection `sqlite_legacy` ditambah di [config/database.php](config/database.php) sebagai jembatan baca data lama. Command artisan baru: [`db:migrate-from-sqlite`](app/Console/Commands/DbMigrateFromSqliteCommand.php) salin semua data per-tabel dengan urutan FK benar, sync auto-increment counter, dan skip tabel ephemeral (cache/session/jobs/migrations). Fix migrasi: kolom `users.two_factor_recovery_codes` dari `json` → `text` karena nilainya encrypted base64 (MariaDB strict-mode menolak isi non-JSON).
- [x] **OPcache + Filament cache** — OPcache PHP diaktifkan di `php.ini` (256MB memory, 20k file, `revalidate_freq=0` untuk dev). `filament:optimize` + `event:cache` + `icons:cache` dijalankan saat build. Hasil: `/admin/login` 1.36s → 0.15s (**9× lebih cepat**), `/admin` dashboard 330ms → 97ms (0 query saat cache hit), `/` 1.47s → 0.25s.
- [x] **Cache widget & navigation badge** — `KadisOverview` widget bungkus 6 query agregat dalam `Cache::remember(60)`. Navigation badge Applications/Tenants/UgbPubPermits cache 30-60 detik. Auto-polling dimatikan untuk hemat query background.
- [x] **Notifikasi outbound async** — Semua call ke Fonnte (`sendOtp`, `sendApplicationSubmitted`, `sendApplicationCompleted`) di-defer via `dispatch(fn)->afterResponse()` agar user tidak menunggu HTTP outbound 3-8 detik. Timeout Fonnte/Wablas/Cloud diturunkan dari 8s + retry 2x → 5s + retry 1x. Total worst-case dari 24s → 10s untuk gateway terburuk.
- [x] **Login warga: hanya WhatsApp** — Opsi login via email dihilangkan dari `/masuk` (UI & controller). Sederhana, konsisten dengan kanal notifikasi utama. Method `maskEmail()` dihapus dari `WargaAuthController`.
- [x] **Fix Secure File 403** — Link "Buka file" di Filament admin (`ApplicationInfolist`) diperbaiki dari `asset('storage/...')` (disk public yang bukan tempat file) ke `route('secure.file', docId)` yang masuk ke `SecureFileController` dengan otorisasi + audit log via `DataAccessLog`.
- [x] **Fix LaporanBulanan error** — `Filament\Notifications\Actions\Action` (Filament 3) sudah diganti menjadi `Filament\Actions\Action` yang unified di Filament 4. Plus fix bug interpolasi string single-quote di judul notifikasi.

### Konfigurasi Tambahan (Fase 7)

```env
# Database (XAMPP default)
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=simpel_dinsos
DB_USERNAME=root
DB_PASSWORD=
DB_CHARSET=utf8mb4
DB_COLLATION=utf8mb4_unicode_ci

# Performance (rekomendasi)
LOG_LEVEL=warning              # debug bikin overhead I/O di tiap request
APP_DEBUG=false                # set false di produksi (true hanya untuk dev)
```

Di `C:\xampp\php\php.ini`:
```ini
zend_extension=opcache
opcache.enable=1
opcache.enable_cli=1
opcache.memory_consumption=256
opcache.max_accelerated_files=20000
opcache.validate_timestamps=1
opcache.revalidate_freq=0      ; dev: tiap perubahan kode langsung tercermin
                               ; production: ganti ke validate_timestamps=0 + restart Apache tiap deploy
```

### Benchmark sebelum & sesudah Fase 7

| URL | Sebelum (SQLite + no OPcache) | Sesudah (MySQL + OPcache + cache) |
|-----|------|------|
| `GET /admin/login` | 0.91–1.36s | **0.14–0.24s** |
| `/admin` (dashboard) | 330ms | **97ms** (0 query) |
| `/admin/applications` | 432ms | **243ms** |
| `/admin/tenants` | 608ms | **207ms** |
| `/` (beranda) | 1.47s | **0.25s** |
| `/layanan` | 0.60s | **0.32s** |

### Deploy production (`dinsos.rokifauzi.biz.id`)

1. **Buat database MySQL** di cPanel/hosting: `simpel_dinsos` charset `utf8mb4_unicode_ci`.
2. **Set kredensial `.env`** server (gunakan password kuat, bukan root/kosong).
3. **Migrasi**:
   ```bash
   php artisan migrate --force
   # Jika ada SQLite production lama, upload database/database.sqlite lalu:
   php artisan db:migrate-from-sqlite
   ```
4. **Optimasi**:
   ```bash
   php artisan optimize
   php artisan filament:optimize
   php artisan icons:cache
   ```
5. **Restart Apache/PHP-FPM** agar OPcache aktif.

## Roadmap Fase 8+ (Belum, perlu sumber daya eksternal)

- [ ] Aktivasi BSrE BSSN real (perlu kredensial dari BSSN)
- [ ] Aktivasi gateway WA Fonnte/Wablas (perlu kredensial vendor)
- [ ] Sertifikasi ISO/IEC 27001 (path 15 bulan via CB akreditasi KAN)
- [ ] Pelatihan keamanan info untuk seluruh pegawai (annual)
- [ ] Penetration test eksternal (Diskominfo / BSSN)
- [ ] Form pengajuan layanan di Flutter (multipart upload)
- [ ] FCM push di Flutter (paralel dengan Web Push)
- [ ] Submit Flutter app ke Play Store + App Store

## Konfigurasi Tambahan (Fase 2)

Tambahkan di `.env` untuk aktifkan gateway produksi:

```env
# Notifikasi outbound — default 'log' (tulis ke outbox file)
NOTIFICATION_DRIVER=log
# Untuk produksi: ganti ke fonnte atau wablas, lalu isi token
FONNTE_TOKEN=
WABLAS_TOKEN=

# Dukcapil — default 'mock'
DUKCAPIL_DRIVER=mock
DUKCAPIL_BASE_URL=
DUKCAPIL_TOKEN=

# DTSEN — default 'mock'
DTSEN_DRIVER=mock
DTSEN_BASE_URL=
DTSEN_TOKEN=
```

## URL Baru Fase 2

| Tujuan | URL |
|---|---|
| Login warga (OTP) | http://127.0.0.1:8000/masuk |
| Dashboard warga | http://127.0.0.1:8000/akun (auth) |
| Operator Pekon dashboard | http://127.0.0.1:8000/pekon (login `operator.pekon@dinsospringsewu.test`) |
| Daftarkan warga | http://127.0.0.1:8000/pekon/ajukan |
| Survei SKM | http://127.0.0.1:8000/skm/{kode-pengajuan} |
| Kiosk lobi | http://127.0.0.1:8000/kiosk |
| Lookup NIK (Dukcapil + DTSEN) | http://127.0.0.1:8000/api/nik/{16-digit-nik} |
| Laporan Bulanan Kadis | http://127.0.0.1:8000/admin/laporan-bulanan |
| LKS Registry | http://127.0.0.1:8000/admin/lks |

## Catatan Demo Fase 2

**Outbox WhatsApp simulasi**: semua notifikasi WA tertulis ke `storage/app/private/outbox/{YYYY-MM-DD}.log`. Cek di sana untuk melihat isi pesan OTP, notifikasi pengajuan, dan undangan SKM yang "dikirim".

**PIN E-sign Pekon (demo)**: `123456`. Di produksi, simpan hash PIN per pekon di tabel terpisah.

**TTS TV Lobi**: butuh interaksi user pertama (klik/keydown) untuk aktif (browser autoplay policy). Setelah aktif, setiap perubahan `last_called` akan disuarakan otomatis dalam Bahasa Indonesia.

**Mock NIK Dukcapil**: NIK harus diawali `187103` (kode wilayah Pringsewu) agar `found=true`. Selain itu akan `found=false`.

**Mock DTSEN**: ~70% NIK acak dianggap terdaftar (mod 10 ≥ 3). Desil 1–10 deterministik dari 4 digit terakhir NIK.

---

## Catatan Pengembang

- **Database**: MySQL/MariaDB (XAMPP) sebagai default. Backup SQLite lama tersimpan di `database/database.sqlite.backup-*`. Connection `sqlite_legacy` di [config/database.php](config/database.php) hanya dipakai oleh command `db:migrate-from-sqlite` — aman dihapus setelah verifikasi data MySQL OK.
- **Login warga**: hanya via **OTP WhatsApp** (`/masuk`). Opsi login email sudah dihilangkan sejak Mei 2026 untuk konsisten dengan kanal notifikasi utama (Fonnte WA).
- **Performa**: OPcache **wajib** untuk development & produksi. Setelah deploy/restart, jalankan `php artisan optimize && php artisan filament:optimize` untuk cache komponen Filament & route — bisa turunkan response time dari 1.3s ke 150ms.
- **Cache aplikasi**: widget Kadis & navigation badge Filament di-cache 30-60 detik (`Cache::remember`). Untuk invalidate manual, jalankan `php artisan cache:clear`.
- **Locale**: `id` (Bahasa Indonesia) dengan timezone `Asia/Jakarta`.
- **Seeder idempotency**: jalankan `php artisan migrate:fresh --seed` jika seed berbenturan unique constraint.
- **IDE diagnostics false positive**: jika IDE menampilkan "Undefined type" untuk class Filament/Laravel, biasanya karena vendor belum terindeks. Jalankan `composer dump-autoload` atau restart language server. Runtime PHP tetap berfungsi normal.
- **Smoke test data**: jalankan `php tests/smoke.php` untuk membuat 3 pengajuan sampel dengan status berbeda (waiting / serving / completed).

---

## Maklumat Pelayanan

> Berdasarkan Maklumat No. **920/460/D.04/X/2023** tanggal 16 Oktober 2023.
> Kepala Dinas: **Debi Hardian, S.Pi., M.Si.** (Pembina Utama Muda, NIP 19671022 199803 2 005)
>
> Pengaduan: **0822-6986-7911** · `pringsewudinsos@gmail.com` · `lapor.go.id`

---

🤖 Aplikasi awal di-scaffold dengan bantuan Claude Code, mengikuti SOP resmi Dinas Sosial Kabupaten Pringsewu.
