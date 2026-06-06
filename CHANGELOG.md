# Changelog

Semua perubahan penting pada SIMPEL DINSOS Pringsewu didokumentasikan di sini.
Format mengikuti [Keep a Changelog](https://keepachangelog.com/id/1.1.0/)
dan [Semantic Versioning](https://semver.org/lang/id/).

## [1.3.0] - 2026-06-06

Rilis pengerasan keamanan & stabilitas menyeluruh hasil audit QA (8 P0, 13 P1, hardening P2).

### Security (P0 — kritis)
- Berkas sensitif (KTP/KK) jalur Operator Pekon kini disimpan di disk privat `secure`, bukan `public`.
- Surat terbitan (ber-NIK) dipindah ke disk `secure`; disajikan hanya via controller berotorisasi / token QR. File lama dimigrasikan.
- Endpoint lookup NIK (`/api/nik`) kini wajib login + dibatasi peran + throttle (cegah enumerasi data Dukcapil/DTSEN).
- Aksi panel admin (verifikasi/kembalikan/tolak/terbitkan surat) dibatasi per-peran; penerbitan surat hanya untuk admin/kadis/sekretaris.
- OTP: rate-limit pengiriman & verifikasi (web + API), perhitungan percobaan diperbaiki (anti brute-force).
- Normalisasi nomor telepon disatukan (`App\Support\PhoneNumber`); verifikasi OTP selalu menormalisasi ulang; `session()->regenerate()` saat login (cegah session fixation).
- Webhook WhatsApp kini fail-closed (wajib token); endpoint simulasi hanya non-produksi.
- PIN e-sign Operator Pekon dipindah dari hardcode ke konfigurasi `ESIGN_PEKON_PIN`.

### Security (P1 — tinggi)
- Cek-status (web & API) tidak lagi membocorkan surat, catatan internal, atau NIK ke non-pemilik (gate kepemilikan, nama disamarkan).
- Recovery code 2FA disimpan ter-hash (`Hash::check`); throttle pada verifikasi challenge; 2FA wajib untuk seluruh peran internal.
- Token Sanctum kini punya masa berlaku (`SANCTUM_EXPIRATION`, default 30 hari) + penegakan ability `warga`.
- Retensi data PDP menghapus berkas dari disk `secure` (sebelumnya salah disk).

### Fixed
- Race condition penomoran tiket antrian & nomor surat (retry-on-conflict) — submit/penerbitan bersamaan tidak lagi gagal/duplikat.
- Aksi panel dibungkus transaksi DB (status + log atomik), notifikasi dikirim setelah commit.
- `applyDocumentReview` tidak lagi memaksa berkas yang tak ditinjau menjadi tervalidasi.
- Statistik bulanan beranda menghitung tahun berjalan (sebelumnya lintas tahun).
- `/api/nik` mengembalikan 401/403 JSON, bukan error 500.

### Performance
- Query laporan bulanan dibuat sargable (`whereBetween`) agar memakai index.
- Eager-load relasi pada halaman detail pengajuan (hindari N+1 pada timeline & dokumen).
- Endpoint `/my/applications` kini berpaginasi.

### Added
- Middleware `SecurityHeaders` (X-Frame-Options, X-Content-Type-Options, Referrer-Policy, HSTS) + `trustProxies`.
- Handler `failed()` pada job OTP & notifikasi (observability).
- Healthcheck Railway `/up`.
- `.env.example` lengkap (notifikasi, Reverb, VAPID, integrasi, tenant, e-sign).
- Suite test isolasi DB (`simpel_dinsos_test`) + `OtpLoginTest`.

### Changed
- Persyaratan PHP dinaikkan ke `^8.3`.
- Entrypoint Docker menggagalkan boot bila migrasi gagal (bukan sekadar warning).
- Seluruh basis kode diformat dengan Laravel Pint.

### Catatan upgrade
- Set di produksi: `APP_ENV=production`, `APP_DEBUG=false`, `APP_URL=https://...`, `SESSION_SECURE_COOKIE=true`, `QUEUE_CONNECTION=database` + worker, `NOTIFICATION_WEBHOOK_TOKEN`, `ESIGN_PEKON_PIN`.
- Rotasi kredensial yang sebelumnya tersimpan di file `env` lokal (Fonnte/Reverb/VAPID) dan pertimbangkan rotasi `APP_KEY`.
