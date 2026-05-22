# Setup Gateway OTP WhatsApp / Email — SIMPEL DINSOS

Sistem support **4 driver gratis** untuk kirim OTP & notifikasi. Pilih salah satu yang paling cocok dengan kebutuhan Dinsos:

| Driver | Biaya | Kuota Gratis | Setup | Resmi? | Risiko |
|---|---|---|---|---|---|
| **Fonnte** | Gratis | 100 pesan/hari | ⭐ 5 menit (paling mudah) | Tidak (WA pribadi) | Sedang (bisa banned) |
| **Wablas** | Trial | Terbatas | 5 menit | Tidak | Sedang |
| **WhatsApp Cloud API** | Gratis | 1.000 conversation/bulan | ⏳ 1-3 hari | ✅ Resmi Meta | Rendah |
| **Email (SMTP Gmail)** | Gratis | 500 email/hari | 5 menit | ✅ | Rendah |

> **Rekomendasi untuk Dinsos Pringsewu:**
> - **Mulai dengan Fonnte** untuk pilot 1-2 bulan (cepat, gratis, 100 pesan/hari cukup)
> - **Migrasi ke WhatsApp Cloud API** untuk produksi penuh (resmi, lebih tahan banned)
> - **Email sebagai fallback** kalau warga tidak punya WhatsApp aktif

---

## 🌟 OPSI 1: Fonnte (Paling Cepat)

### Cara Daftar
1. Buka https://fonnte.com
2. Klik "Daftar" — isi email & password
3. Login ke dashboard https://md.fonnte.com
4. Klik menu **"Device"** → **"Tambah Device"**
5. **Scan QR code** di dashboard pakai HP yang punya WhatsApp aktif
   - Buka WhatsApp di HP → menu titik tiga → **Linked Devices** → **Link a device**
   - Arahkan kamera ke QR di layar komputer
6. Setelah connected, salin **token** yang muncul di dashboard

### Konfigurasi Aplikasi
Edit `.env` di server `dinsos.rokifauzi.biz.id`:

```env
NOTIFICATION_DRIVER=fonnte
FONNTE_TOKEN=xxxxxxxxxxxxxxxxxx   # paste token dari dashboard Fonnte
```

Restart server (atau `php artisan config:clear` jika di-cache).

### Test
1. Buka https://dinsos.rokifauzi.biz.id/masuk
2. Pilih tab "💬 WhatsApp"
3. Masukkan nomor HP Anda yang punya WhatsApp
4. Klik "Kirim OTP ke WhatsApp →"
5. **Cek WhatsApp Anda** — kode 6 digit harus masuk dalam 5-30 detik
6. Kalau tidak masuk, cek log: `storage/logs/laravel.log` — cari `[FONNTE]`

### Tips Hindari Banned di Fonnte
- Gunakan **nomor WA terpisah** khusus untuk gateway (bukan nomor pribadi pejabat)
- Hindari kirim ke banyak nomor sekaligus dalam waktu singkat
- Pesan **jangan terlalu generic** (Fonnte add salam personal di kode kita)
- Kalau gateway sering disconnect, scan ulang QR

### Batasan Paket Gratis Fonnte
- **100 pesan/hari** (cukup untuk ~50 login warga + ~50 notifikasi)
- **Tidak bisa kirim image/file** di free tier (hanya teks)
- Pesan dikirim dengan delay random 1-3 detik (anti spam)

Upgrade ke paket Starter (Rp 60rb/bulan): 5.000 pesan/hari + kirim media.

---

## 🌟 OPSI 2: WhatsApp Cloud API Resmi Meta (Most Robust)

Lebih ribet setup tapi **gratis 1.000 conversation/bulan** dan **tidak ada risiko banned**.

### Setup (1-3 hari proses verifikasi)
1. Buat akun **Meta for Developers**: https://developers.facebook.com
2. Buat **Business Manager** di https://business.facebook.com
3. Verifikasi bisnis (perlu dokumen pemda — SK Kadis, NPWP instansi)
4. Buat **WhatsApp Business Account**
5. Tambah nomor WhatsApp business (bukan WA pribadi)
6. Dapatkan **Access Token** + **Phone Number ID**
7. **Approve message template** untuk OTP (harus pakai template, bukan free-text)

### Template OTP yang Perlu Di-approve
Nama template: `otp_simpel_dinsos`
Kategori: AUTHENTICATION
Bahasa: id (Indonesia)
Body:
```
Kode OTP SIMPEL DINSOS Anda: {{1}}

Kode berlaku 5 menit. Jangan bagikan ke siapa pun.

— Dinas Sosial Pringsewu
```

### Konfigurasi
```env
NOTIFICATION_DRIVER=cloud
WHATSAPP_TOKEN=EAAxxx...          # Permanent access token
WHATSAPP_PHONE_ID=123456789       # ID nomor WA business
```

> Note: implementasi sekarang masih pakai pesan teks. Untuk produksi penuh dengan Meta, kode harus diadaptasi pakai template message format. Lihat dokumentasi Meta Cloud API.

---

## 🌟 OPSI 3: Email via SMTP Gmail (Backup)

Cocok untuk **fallback** kalau warga tidak punya WhatsApp atau gateway WA sedang mati.

### Setup Gmail App Password
1. Login ke Gmail dengan akun `pringsewudinsos@gmail.com` (atau akun pemda)
2. Buka https://myaccount.google.com/security
3. Aktifkan **2-Step Verification** (wajib)
4. Setelah aktif, masuk ke **App passwords**: https://myaccount.google.com/apppasswords
5. Pilih "Mail" → "Other (Custom name)" → ketik "SIMPEL DINSOS" → **Generate**
6. Salin password 16-karakter yang muncul (hanya muncul sekali!)

### Konfigurasi
```env
# Kalau OTP dikirim via email saja
NOTIFICATION_DRIVER=email

# Atau kombinasi: WA via Fonnte + fallback email kalau WA gagal
NOTIFICATION_DRIVER=fonnte
NOTIFICATION_FALLBACK_EMAIL=true

# SMTP Gmail
MAIL_MAILER=smtp
MAIL_HOST=smtp.gmail.com
MAIL_PORT=587
MAIL_USERNAME=pringsewudinsos@gmail.com
MAIL_PASSWORD=xxxx xxxx xxxx xxxx   # 16-char app password (boleh dengan spasi)
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS=pringsewudinsos@gmail.com
MAIL_FROM_NAME="SIMPEL DINSOS Pringsewu"
```

### Batasan Gmail SMTP
- **500 email/hari** per akun Gmail biasa (cukup untuk Dinsos kabupaten)
- Untuk 2000+/hari, upgrade ke **Google Workspace** (~Rp 80rb/user/bulan)
- Atau pakai **Resend / Mailgun / Brevo** (paket gratis 100-300/hari)

---

## 🌟 OPSI 4: Email Provider Lain (Brevo / Resend)

Kalau ingin email kualitas lebih (deliverability tinggi, anti-spam):

### Brevo (dulu Sendinblue) — gratis 300 email/hari
```env
MAIL_MAILER=smtp
MAIL_HOST=smtp-relay.brevo.com
MAIL_PORT=587
MAIL_USERNAME=your-email@domain.com
MAIL_PASSWORD=brevo-smtp-key
MAIL_ENCRYPTION=tls
```

### Resend — gratis 100 email/hari
```env
MAIL_MAILER=resend
RESEND_KEY=re_xxx...
```
Install dulu: `composer require resend/resend-laravel`

---

## 🧪 Test Lengkap Setiap Driver

```bash
cd /path/ke/simpel-dinsos

# Test 1: Driver log (default, harus selalu jalan)
php -r "require 'vendor/autoload.php'; \$app = require 'bootstrap/app.php'; \$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap(); config(['services.notifications.driver' => 'log']); app(App\Services\NotificationGateway::class)->sendOtp('081234567890', '123456'); echo 'OK — cek storage/app/private/outbox/' . date('Y-m-d') . '.log';"

# Test 2: Driver fonnte (perlu FONNTE_TOKEN)
php -r "require 'vendor/autoload.php'; \$app = require 'bootstrap/app.php'; \$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap(); app(App\Services\NotificationGateway::class)->sendOtp('NOMOR_HP_ANDA', '999999'); echo 'OK — cek WhatsApp Anda';"

# Test 3: Driver email (perlu SMTP)
php -r "require 'vendor/autoload.php'; \$app = require 'bootstrap/app.php'; \$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap(); app(App\Services\NotificationGateway::class)->sendOtp('test@email.com', '888888'); echo 'OK — cek inbox email';"
```

---

## 📊 Troubleshooting

### "FONNTE_TOKEN belum di-set, fallback ke log"
- Token belum di-set di `.env` atau salah ketik
- Atau belum `php artisan config:clear` setelah edit `.env`

### Token sudah di-set tapi OTP tidak sampai
1. Cek dashboard Fonnte → menu **Log** — apakah pesan muncul?
2. Cek `storage/logs/laravel.log` — cari `[FONNTE]` untuk error detail
3. Pastikan **device di Fonnte masih connected** (tidak disconnect)
4. Pastikan **nomor tujuan punya WhatsApp aktif**
5. Coba kirim manual via dashboard Fonnte → kalau gagal juga, masalah di device

### "Pesan dikirim" tapi tidak masuk WhatsApp
- WA pengirim mungkin **diblokir Meta** karena dianggap spam
- Solusi: scan ulang QR dengan **nomor WA berbeda**, atau pakai WhatsApp Cloud API resmi

### Rate limit error
Sistem ada rate limit **5 percobaan OTP per 15 menit** per kontak. Tunggu atau hubungi admin untuk reset.

### Email tidak terkirim ("Connection could not be established")
- Gmail block koneksi dari IP yang tidak dikenal → coba enable "Less secure app access" (deprecated)
- Pakai **App Password** (16-char) bukan password biasa
- Pastikan 2FA Gmail sudah aktif

---

## 💰 Rekomendasi Final per Skenario

| Skenario | Driver |
|---|---|
| **Demo / pilot 1 minggu** | `log` (default, gratis) — kode terlihat di file outbox |
| **Pilot 1-2 bulan, <100 OTP/hari** | `fonnte` (gratis, paling cepat setup) |
| **Operasional penuh, 100-1000 OTP/hari** | `cloud` (Meta resmi, gratis 1000/bulan) |
| **Backup / failover** | Set `NOTIFICATION_FALLBACK_EMAIL=true` |
| **Skala besar (>1000/hari)** | Cloud API + upgrade Meta business tier |

## File Terkait

- `app/Services/NotificationGateway.php` — implementasi semua driver
- `config/services.php` (key `notifications`) — config driver & token
- `app/Http/Controllers/WargaAuthController.php` — flow OTP login warga
- `routes/web.php` — route `warga.otp.send` & `warga.otp.verify`
