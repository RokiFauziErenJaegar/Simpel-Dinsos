# Changelog

Semua perubahan penting pada SIMPEL DINSOS Pringsewu didokumentasikan di sini.
Format mengikuti [Keep a Changelog](https://keepachangelog.com/id/1.1.0/)
dan [Semantic Versioning](https://semver.org/lang/id/).

## [1.4.0] - 2026-07-20

Rilis fitur lanjutan hasil masukan operasional Dinas Sosial: layanan dua lokasi
(Dinsos & MPP), modul Konsultasi Warga (KIE), multi-akun petugas, statistik SKM
publik, dan pengerasan alur pengajuan warga.

### Added
- **Konsultasi Warga (KIE)** — modul baru pendaftaran konsultasi mandiri oleh warga
  (`/kie`): form publik, kode konsultasi, halaman sukses, notifikasi WhatsApp via job
  antrean, resource Filament lengkap (list/create/edit/view) + widget ringkasan di dasbor.
- **Multi-akun petugas** (ala Instagram/WhatsApp) — tambah akun kedua tanpa keluar dari
  akun aktif lalu berpindah tanpa password ulang (`/akun-petugas`): pemilih akun di topbar
  panel, pintasan akun yang pernah dipakai di halaman login, dan aksi "lupakan akun".
- **Lokasi pelayanan (Dinsos / MPP)** — enum `ServiceLocation`, kolom `location` pada
  `users` & `applications`. Lokasi pengajuan di-stempel mengikuti lokasi petugas yang
  pertama menangani; pengajuan online yang belum disentuh tampil sebagai
  "Online / Belum diproses". Kolom & filter lokasi di tabel pengajuan admin.
- **Statistik Kepuasan Masyarakat publik** (`/statistik-kepuasan`) — total responden,
  indeks, dan sebaran per unsur; rentang bulan berjalan / tahun / kustom.
- **Laporan SKM** — `SkmReportGenerator` + halaman panel "Laporan SKM" dan template PDF
  siap cetak.
- **Manajemen Pengguna di panel admin** — `UserResource` (list/create/edit/view) untuk
  kelola akun petugas beserta peran dan lokasinya.
- Rekap **per lokasi pelayanan** pada laporan bulanan PDF untuk Bupati.
- Dukungan **gateway WhatsApp self-hosted** yang kompatibel Fonnte lewat
  `FONNTE_ENDPOINT` (default tetap endpoint resmi Fonnte).

### Changed
- Indeks Kepuasan pada dasbor Kadis kini dihitung dari data survei nyata
  (sebelumnya angka hardcoded `87,3`); tampil `—` bila belum ada responden bulan berjalan.
- Verifikasi 2FA dilacak **per akun**, bukan satu flag per sesi — dengan multi-akun, akun
  kedua tidak lagi menumpang verifikasi 2FA milik akun pertama. Route `account.*`
  dikecualikan dari gerbang 2FA agar petugas yang tertahan di challenge tetap bisa
  berpindah akun.
- Opsi penerima manfaat **"Orang lain (kuasa)"** kini hanya tersedia untuk layanan
  Surat Tanda Terdaftar LKS/Yayasan/Panti (L03); untuk 15 layanan lain opsi dihapus.
  Bila dikuasakan, unggah **surat kuasa wajib**.

### Fixed
- **Duplikasi pengajuan** saat tombol kirim diklik berkali-kali pada internet lambat:
  form kini membawa nonce sekali-pakai + lock, sehingga klik/retry berulang mengembalikan
  pengajuan yang sama alih-alih membuat pengajuan baru. Berlaku juga pada alur perbaikan
  berkas (resubmit).

### Catatan upgrade
- Jalankan migrasi baru: `add_location_to_users_table`, `add_location_to_applications_table`,
  `create_kie_consultations_table`.
- Set `location` (`dinsos` / `mpp`) pada akun petugas yang bertugas di MPP agar rekap
  lokasi pada laporan bulanan akurat.
- Opsional: set `FONNTE_ENDPOINT` bila memakai WA gateway sendiri.
- Fitur anti-duplikat memakai cache — pastikan `CACHE_STORE` persisten (database/redis),
  bukan `array`.

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
