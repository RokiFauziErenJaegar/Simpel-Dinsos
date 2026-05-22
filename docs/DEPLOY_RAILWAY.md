# Deploy SIMPEL DINSOS ke Railway

Panduan deploy aplikasi ke [Railway](https://railway.com) dengan setup yang sudah disiapkan:
- Single container: Nginx + PHP-FPM 8.3 + Supervisord
- MySQL plugin Railway (1 service)
- Volume persisten untuk berkas upload
- Queue worker (1 service tambahan opsional)
- Healthcheck `/health` untuk auto-restart

Estimasi waktu setup: **15-25 menit** untuk first deploy.

---

## Prasyarat

1. Akun Railway aktif: https://railway.com (login via GitHub)
2. Repository sudah di-push ke GitHub
3. Railway CLI (opsional, untuk debug): `npm i -g @railway/cli`

---

## Step 1 — Buat Project di Railway

1. Login ke https://railway.com → **New Project**
2. Pilih **Deploy from GitHub repo** → connect GitHub → pilih repo `simpel-dinsos`
3. Railway akan mulai build pertama kali. **BIARKAN GAGAL DULU** — kita belum set env vars.

---

## Step 2 — Tambah MySQL Plugin

1. Di project Railway, klik **+ New** → **Database** → **MySQL**
2. Railway akan provision MySQL plugin baru.
3. Klik plugin MySQL → tab **Variables** → copy nilai-nilai ini:
   - `MYSQLHOST`
   - `MYSQLPORT`
   - `MYSQLDATABASE`
   - `MYSQLUSER`
   - `MYSQLPASSWORD`

> **Tips**: Railway juga expose `MYSQL_URL` (DSN lengkap). Kita tidak pakai, kita pakai variable terpisah.

---

## Step 3 — Set Environment Variables di Service Web

1. Di project Railway, klik service **web** (app utama, bukan plugin MySQL)
2. Tab **Variables** → klik **Raw Editor** (mode bulk paste)
3. Paste konfigurasi berikut, **ganti nilai placeholder** dengan kredensial dari Step 2:

```env
# ===== APP =====
APP_NAME="SIMPEL DINSOS"
APP_ENV=production
APP_DEBUG=false
APP_URL=https://${{RAILWAY_PUBLIC_DOMAIN}}
APP_TIMEZONE=Asia/Jakarta
APP_LOCALE=id
APP_FALLBACK_LOCALE=en

# GENERATE LOKAL DULU dengan: php artisan key:generate --show
# Lalu paste hasilnya di sini (format: base64:xxxxx...)
APP_KEY=

# ===== LOG =====
LOG_CHANNEL=stderr
LOG_LEVEL=warning

# ===== DATABASE (dari plugin MySQL Railway) =====
DB_CONNECTION=mysql
DB_HOST=${{MySQL.MYSQLHOST}}
DB_PORT=${{MySQL.MYSQLPORT}}
DB_DATABASE=${{MySQL.MYSQLDATABASE}}
DB_USERNAME=${{MySQL.MYSQLUSER}}
DB_PASSWORD=${{MySQL.MYSQLPASSWORD}}
DB_CHARSET=utf8mb4
DB_COLLATION=utf8mb4_unicode_ci

# ===== SESSION & CACHE (file-based, simpan di Volume) =====
SESSION_DRIVER=file
SESSION_LIFETIME=120
SESSION_ENCRYPT=true
CACHE_STORE=file

# ===== QUEUE (database driver, jobs jalankan di service queue terpisah) =====
QUEUE_CONNECTION=database

# ===== FILESYSTEM =====
FILESYSTEM_DISK=local
SECURE_DISK_DRIVER=local

# ===== BCRYPT =====
BCRYPT_ROUNDS=10

# ===== TRUSTED PROXIES (Railway behind their own edge) =====
TRUSTED_PROXIES=*

# ===== TENANT =====
TENANT_MODE=single
TENANT_ID=pringsewu
TENANT_NAME="Kabupaten Pringsewu"
TENANT_INSTANSI="Dinas Sosial Kabupaten Pringsewu"
TENANT_CALL_CENTER=0822-6986-7911

# ===== NOTIFIKASI (mode demo dulu, tulis ke storage outbox file) =====
# Setelah deploy berhasil, ganti driver=fonnte + isi token untuk production
NOTIFICATION_DRIVER=log
FONNTE_TOKEN=
WABLAS_TOKEN=

# ===== STUB SERVICES =====
DUKCAPIL_DRIVER=mock
DTSEN_DRIVER=mock
LAPOR_DRIVER=mock
BSRE_DRIVER=mock
BSRE_ENABLED=false

# ===== WEB PUSH (opsional; generate dengan: node -e "console.log(require('web-push').generateVAPIDKeys())") =====
VAPID_PUBLIC_KEY=
VAPID_PRIVATE_KEY=
VAPID_SUBJECT=mailto:pringsewudinsos@gmail.com

# ===== BROADCASTING (skip kalau tidak pakai Reverb) =====
BROADCAST_CONNECTION=null
```

### Generate `APP_KEY`

Di terminal lokal:
```bash
cd C:\xampp\htdocs\simpel-dinsos
php artisan key:generate --show
```

Output contoh: `base64:iJE1MxwBnNJa2S1NFvQp+Hz8rTBBtoQiQz8Yf2ypXwU=`

Copy seluruh string termasuk prefix `base64:` ke env var `APP_KEY` di Railway.

> ⚠️ **JANGAN** pakai key yang sama dengan lokal — generate baru untuk production. Kalau key bocor, semua data terenkripsi (NIK, KK) bisa dibaca.

---

## Step 4 — Tambah Volume untuk Storage

Berkas upload (KTP/KK warga, PDF surat) harus persisten. Filesystem container Railway ephemeral.

1. Di service **web**, tab **Settings** → scroll ke **Volumes** → klik **+ New Volume**
2. Set:
   - **Mount path**: `/app/storage`
   - **Size**: minimal **1 GB** (cukup untuk 10rb berkas KTP rata-rata 200KB)
3. Save.

Volume akan otomatis di-mount tiap container restart. Folder `storage/app/secure/`, `storage/app/public/`, dan `storage/logs/` semua tetap aman.

---

## Step 5 — Tambah Public Domain

1. Di service **web**, tab **Settings** → **Networking** → klik **Generate Domain**
2. Railway kasih URL seperti `https://simpel-dinsos-production.up.railway.app`
3. Copy URL → balik ke tab **Variables** → update `APP_URL` ke domain itu

> Atau pakai variable interpolation: `APP_URL=https://${{RAILWAY_PUBLIC_DOMAIN}}` (Railway substitusi otomatis).

---

## Step 6 — Deploy Ulang

Setelah env vars lengkap & volume terpasang:

1. Klik tab **Deployments** → klik **Redeploy** pada deployment terakhir
2. Tunggu build selesai (~5-8 menit pertama kali, karena composer install + npm build)
3. Cek **Logs** — pastikan keluar:
   ```
   [entrypoint] Bootstrap selesai. Starting supervisord...
   ```
4. Healthcheck `/health` harus return 200:
   ```bash
   curl https://your-app.up.railway.app/health
   # {"status":"ok","db":"mysql","app":"SIMPEL DINSOS",...}
   ```

---

## Step 7 — Tambah Service Queue Worker

Notifikasi WA (Fonnte) di-dispatch via `afterResponse` + `database` queue. Tanpa worker, queue menumpuk di tabel `jobs`.

1. Di project Railway, klik **+ New** → **GitHub Repo** → pilih repo yang sama
2. Beri nama service: **queue**
3. Tab **Settings**:
   - **Start Command**: `php artisan queue:work --tries=3 --max-time=3600`
   - **Healthcheck**: kosongkan (queue worker tidak listen HTTP)
4. Tab **Variables** → klik **Shared Variables** atau copy semua variable dari service `web`. **PENTING**: variable `DB_*` harus sama biar worker baca queue table dari MySQL yang sama.
5. Deploy.

Worker akan jalan terus, ambil job dari tabel `jobs`, eksekusi (kirim WA via Fonnte, dll), repeat.

---

## Step 8 — Buat Akun Admin (Pertama Kali)

Setelah deploy berhasil & DB di-seed otomatis oleh entrypoint, ada akun demo:

| Email | Password | Peran |
|-------|----------|-------|
| `admin@dinsospringsewu.test` | `password` | Admin Sistem |
| `kadis@dinsospringsewu.test` | `password` | Kepala Dinas |
| `petugas@dinsospringsewu.test` | `password` | Petugas Loket |

> ⚠️ **WAJIB GANTI PASSWORD** sebelum production aktif. Bisa via Filament `/admin` setelah login pertama.

Atau buat akun baru via Railway CLI:
```bash
railway run php artisan tinker
> App\Models\User::create([
    'name' => 'Nama Kadis Sebenarnya',
    'email' => 'kadis@dinsospringsewu.go.id',
    'phone' => '628xxxxxxxxx',
    'password' => bcrypt('PasswordKuat!'),
    'role' => 'kadis',
    'is_active' => true,
    'email_verified_at' => now(),
]);
```

---

## Setelah Deploy Berhasil

### Aktivasi Fonnte WhatsApp (production)

1. Daftar di https://fonnte.com, scan QR di dashboard
2. Salin token → set di Railway env vars service `web`:
   ```
   NOTIFICATION_DRIVER=fonnte
   FONNTE_TOKEN=xxxxxxxxxxxxxxx
   ```
3. Restart service `web` & service `queue`.
4. Test: ke `/masuk` → submit nomor → cek HP harus dapat OTP.

### Custom Domain (opsional)

1. Di Railway service `web` → **Settings** → **Networking** → **Custom Domain** → tambahkan `dinsos.rokifauzi.biz.id`
2. Railway kasih target CNAME, mis. `simpel-dinsos-production.up.railway.app`
3. Di Cloudflare DNS untuk `rokifauzi.biz.id`:
   - Add CNAME record: `dinsos` → `simpel-dinsos-production.up.railway.app`
   - Proxy: **DNS only** (gray cloud) untuk SSL cert Railway. Setelah aktif, baru bisa diorange (proxied).
4. Tunggu 1-5 menit, akses `https://dinsos.rokifauzi.biz.id`.

### Migrasi ke Cloudflare R2 / S3 (kalau Volume sudah penuh)

Update env vars:
```env
SECURE_DISK_DRIVER=s3
AWS_ACCESS_KEY_ID=xxx
AWS_SECRET_ACCESS_KEY=xxx
AWS_DEFAULT_REGION=auto
AWS_BUCKET=simpel-dinsos
AWS_ENDPOINT=https://xxx.r2.cloudflarestorage.com
AWS_USE_PATH_STYLE_ENDPOINT=true
```

Lalu migrasikan berkas lama via:
```bash
railway run php artisan storage:migrate-sensitive
```

---

## Troubleshooting

### Build gagal di tahap `npm run build`
- Cek `package-lock.json` ikut di-commit (Dockerfile pakai `npm ci`).
- Kalau dependencies error, hapus lock file lokal & re-run `npm install` lokal, lalu commit `package-lock.json`.

### `502 Bad Gateway` setelah deploy
- Cek Logs service web — biasanya `entrypoint.sh` error karena DB tidak terhubung.
- Pastikan MySQL plugin sudah running (cek di service MySQL → status hijau).
- Pastikan variable `DB_HOST` pakai format `${{MySQL.MYSQLHOST}}` (bukan IP hardcoded).

### `419 PAGE EXPIRED` saat submit form
- Session driver belum benar atau APP_URL belum match domain Railway.
- Cek `APP_URL` di env vars sesuai domain Railway aktual.
- Pastikan `SESSION_DRIVER=file` & Volume `/app/storage` ter-mount.

### Berkas upload hilang setelah redeploy
- Volume belum di-mount. Cek service web → **Settings** → **Volumes**.
- Mount path harus `/app/storage` persis.

### `APP_KEY` not set / Encryption error
- Variable `APP_KEY` belum di-set atau dihapus.
- Generate baru lokal dengan `php artisan key:generate --show`, paste ke Railway.
- ⚠️ Kalau key dihapus setelah ada data terenkripsi (NIK warga), data tersebut tidak bisa dibaca lagi.

### Filament admin tampil tanpa CSS/JS
- Build asset gagal. Cek Logs tahap `npm run build`.
- Atau symlink `/storage` belum benar — restart container untuk re-run entrypoint.

### Healthcheck `/health` selalu fail
- DB tidak terhubung. Pastikan plugin MySQL hidup.
- Cek `php artisan migrate:status` via `railway run` — kalau error koneksi, env vars belum benar.

### Logs cara baca
```bash
# Via Railway CLI
railway logs --service web
railway logs --service queue

# Atau di dashboard: service → tab Deployments → klik deployment → View Logs
```

---

## Biaya Estimasi Railway (per bulan)

| Komponen | Resource | Estimasi |
|----------|----------|----------|
| Service web (Nginx + PHP-FPM) | 512 MB RAM, 0.5 vCPU | ~$5 |
| Service queue worker | 256 MB RAM, 0.25 vCPU | ~$3 |
| MySQL plugin | 1 GB storage | ~$5 |
| Volume storage `/app/storage` | 1 GB | ~$0.25 |
| Bandwidth | ~10 GB/bulan | included |

**Total**: **~$13/bulan** untuk demo/MVP. Skala bisa naik kalau traffic tinggi (16 layanan × ribuan pengajuan/bulan).

Railway kasih $5 free credit pertama. Pakai cukup untuk demo awal.

---

## Rollback ke Deployment Sebelumnya

Kalau deploy baru bermasalah:

1. Di service `web` → tab **Deployments** → cari deployment sebelumnya yang status hijau
2. Klik titik tiga → **Redeploy**
3. Railway akan rollback ke build artifact deployment itu (cepat, ~30 detik).

Data MySQL & Volume tidak terpengaruh oleh rollback.

---

## Maintenance Mode

Untuk maintenance manual:
```bash
railway run php artisan down --refresh=15 --secret=admin-bypass-token
# Bypass: https://your-app.up.railway.app/admin-bypass-token
```

Setelah selesai:
```bash
railway run php artisan up
```

---

🤖 Dokumen ini dibuat untuk SIMPEL DINSOS Pringsewu. Versi terbaru selalu di repository `docs/DEPLOY_RAILWAY.md`.
