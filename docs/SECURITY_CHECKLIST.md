# Security & UU PDP Compliance Checklist — SIMPEL DINSOS

> Dokumen acuan **Inspektorat / Diskominfo Pringsewu** untuk audit keamanan dan kepatuhan UU 27/2022 (Pelindungan Data Pribadi) sebelum & selama operasional sistem.

---

## A. Pre-Production Penetration Test Checklist

### A1. Pengerasan Server & Aplikasi
- [ ] `APP_DEBUG=false` & `APP_ENV=production` di server produksi
- [ ] `APP_KEY` di-generate ulang (`php artisan key:generate`) untuk produksi
- [ ] HTTPS terpasang dengan sertifikat resmi (Let's Encrypt minimal)
- [ ] HSTS header diaktifkan (`Strict-Transport-Security: max-age=31536000`)
- [ ] `php artisan config:cache` & `route:cache` dijalankan
- [ ] PHP `expose_php = Off`, `display_errors = Off`
- [ ] File permissions: `storage/` & `bootstrap/cache/` writable, sisanya read-only untuk web user
- [ ] Backup harian database & `storage/app/` ke lokasi terpisah, retensi 30 hari
- [ ] Log rotation aktif (`config/logging.php` daily channel)

### A2. Autentikasi & Otorisasi
- [ ] Password admin minimal 12 karakter, kombinasi huruf besar/kecil/angka/simbol
- [ ] 2FA wajib untuk role Admin & Kadis (Filament `MustVerifyEmail` + OTP atau Google Authenticator)
- [ ] Rate limit OTP: max 5 percobaan per nomor per jam
- [ ] OTP expire dalam 5 menit
- [ ] Session timeout pegawai max 120 menit (`SESSION_LIFETIME=120`)
- [ ] Logout otomatis saat tab ditutup untuk role internal (`SESSION_EXPIRE_ON_CLOSE=true`)
- [ ] Pemisahan tegas role: Petugas tidak bisa lihat data lintas-bidang, Warga hanya lihat pengajuan sendiri
- [ ] PIN e-sign Operator Pekon di-hash (saat ini demo string `123456` — WAJIB diganti)
- [ ] Cek `Authorize` policy untuk setiap aksi Filament

### A3. Injection & Input Validation
- [ ] Semua input form melewati `$request->validate(...)`
- [ ] File upload dibatasi mime type (`jpg,jpeg,png,pdf`) dan ukuran (2 MB)
- [ ] File upload disimpan dengan nama random (bukan original) di `storage/app/secure/`
- [ ] Tidak ada `DB::raw()` dengan input user tanpa parameter binding
- [ ] Output Blade pakai `{{ }}` (auto-escaped), bukan `{!! !!}` kecuali untuk konten yang sudah dibersihkan
- [ ] CSRF token aktif di semua form POST (sudah default Laravel)
- [ ] Webhook WA pakai header `X-Webhook-Token` untuk verifikasi origin

### A4. XSS & CSRF
- [ ] Content Security Policy (CSP) di middleware: `default-src 'self'; img-src 'self' data: https:;`
- [ ] X-Frame-Options: DENY (cegah clickjacking)
- [ ] X-Content-Type-Options: nosniff
- [ ] CSRF token diverifikasi di setiap POST/PUT/DELETE (webhook WA dikecualikan tapi pakai token sendiri)
- [ ] Cookie session di-set `Secure` + `HttpOnly` + `SameSite=Lax`

### A5. File Storage & Data Sensitif
- [ ] Direktori `storage/app/secure/` tidak dapat diakses publik (di luar `public/`)
- [ ] Berkas KTP/KK/foto PPKS pindah ke disk `secure` (set `SECURE_DISK_DRIVER=minio` di prod)
- [ ] Foto wajah PPKS dengan watermark "DINSOS PRINGSEWU"
- [ ] Output PDF tidak menampilkan NIK lengkap kecuali diperlukan (mask 8 digit tengah)
- [ ] Database backup terenkripsi (gpg / openssl)
- [ ] Token verifikasi dokumen pakai 40 char random (`bin2hex(random_bytes(20))`) — tidak prediktif

### A6. Network & Infrastruktur
- [ ] Firewall: hanya port 443 (HTTPS) & 22 (SSH dengan key-only) terbuka ke publik
- [ ] Port database (3306/5432) hanya local
- [ ] Port Reverb WebSocket (8080) hanya melalui reverse proxy Nginx + WSS
- [ ] SSH disable password login, hanya pakai key
- [ ] Fail2ban aktif untuk SSH & Filament login (rate limit 5 percobaan / 15 menit)
- [ ] Update keamanan otomatis (`unattended-upgrades`)

### A7. Pengujian
- [ ] `composer audit` zero high-severity
- [ ] `npm audit` zero high-severity
- [ ] Pen-test eksternal sebelum go-live (libatkan Diskominfo / BSSN)
- [ ] OWASP Top 10 dicek satu per satu (Injection, Broken Auth, XSS, CSRF, Sensitive Data, dll)
- [ ] DAST scan dengan OWASP ZAP minimal sekali sebelum produksi
- [ ] Load test (k6 / JMeter): 100 user simultan, response < 2 detik

---

## B. UU 27/2022 Pelindungan Data Pribadi — Compliance Checklist

### B1. Dasar Hukum Pemrosesan (Pasal 20)
- [ ] **Persetujuan eksplisit** dari subjek data sebelum berkas diunggah (checkbox consent di form pengajuan — sudah ✅)
- [ ] Persetujuan dapat ditarik kembali kapan saja (sediakan tombol "Hapus akun & data saya")
- [ ] Pemrosesan dapat dilakukan tanpa persetujuan untuk: pemenuhan kewajiban hukum (mis. wajib lapor ke Bupati), pelaksanaan tugas pemerintahan untuk kepentingan umum (rujukan PPKS) — dokumentasikan dasar tersebut per layanan
- [ ] Catat pasal UU PDP yang menjadi dasar pemrosesan pada masing-masing 16 layanan

### B2. Hak Subjek Data (Pasal 4-15)
- [ ] **Hak diberi tahu**: notifikasi WA terkirim saat data diproses (sudah ✅ via NotificationGateway)
- [ ] **Hak akses**: warga login bisa lihat semua pengajuan & berkas miliknya (sudah ✅ di /akun)
- [ ] **Hak rektifikasi**: warga dapat minta perbaikan data via WA bot atau loket
- [ ] **Hak menghapus** ("right to be forgotten"): sediakan endpoint admin untuk soft-delete + scrub berkas
- [ ] **Hak portabilitas**: ekspor data warga ke JSON/PDF atas permintaan
- [ ] **Hak menarik persetujuan**: saat ditarik, otomatis hapus data dalam 30 hari

### B3. Prinsip Minimisasi & Tujuan
- [ ] Hanya kumpulkan data yang relevan dengan layanan (jangan minta NPWP untuk layanan KIE)
- [ ] Setiap field di form punya alasan jelas (dokumentasikan di SOP per layanan)
- [ ] Tidak ada cross-selling data lintas layanan tanpa persetujuan ulang

### B4. Retensi Data (Pasal 38)
- [ ] Berkas KTP/KK dihapus otomatis 3 tahun setelah pengajuan selesai
- [ ] Foto PPKS dihapus 1 tahun setelah pengajuan selesai
- [ ] OTP code dihapus setelah 5 menit (sudah ✅ via expires_at)
- [ ] Application logs disimpan 10 tahun untuk audit (anonymize NIK)
- [ ] Backup database dihapus setelah 1 tahun

### B5. Pelindungan Teknis (Pasal 39-41)
- [ ] Enkripsi at-rest untuk NIK & KK di DB (gunakan Laravel encryption casts)
- [ ] Enkripsi at-transit (HTTPS only)
- [ ] Pseudonim untuk analytics/laporan (gunakan ID acak, bukan NIK)
- [ ] Audit log: siapa-akses-apa-kapan untuk seluruh akses ke data PPKS
- [ ] Access log di-review bulanan oleh DPO (Data Protection Officer)

### B6. DPO & Tata Kelola (Pasal 53-55)
- [ ] Tunjuk **Data Protection Officer (DPO)** — disarankan pegawai Sekretariat Dinas dengan SK Kadis
- [ ] DPO punya akses langsung ke Kadis & Bupati
- [ ] Dokumen DPIA (Data Protection Impact Assessment) dibuat sebelum tambah layanan baru
- [ ] Buku log "Daftar Kegiatan Pemrosesan Data Pribadi" (DPIA inventory)

### B7. Pelanggaran Data (Pasal 46)
- [ ] Notifikasi ke Komisi PDP & subjek data dalam **3x24 jam** sejak insiden
- [ ] Buku log insiden + remediasi
- [ ] Tabletop exercise pelanggaran data tiap 6 bulan
- [ ] Cyber insurance (opsional, tapi disarankan)

### B8. Pemrosesan oleh Pihak Ketiga
- [ ] Perjanjian Pemrosesan Data dengan vendor WA gateway (Fonnte/Wablas), Dukcapil, DTSEN
- [ ] Klausul pelarangan vendor menyimpan data > kebutuhan layanan
- [ ] Audit tahunan vendor

---

## C. Audit Trail yang Wajib Tersedia

Sistem **sudah** mencatat:
- ✅ Setiap perubahan status pengajuan (`application_logs`)
- ✅ Login terakhir user (`users.last_login_at`)
- ✅ Pengaduan & responsnya (`complaints`)
- ✅ Notifikasi outbound (`storage/outbox/*.log`)

Belum dicatat (TODO untuk compliance penuh):
- [ ] Akses baca ke data PPKS oleh petugas (tambahkan `data_access_logs` table)
- [ ] Login attempts gagal (gunakan Laravel `Illuminate\Auth\Events\Failed`)
- [ ] Ekspor / unduh berkas (track via middleware)
- [ ] Perubahan konfigurasi sistem (audit `settings`/`env` changes)

---

## D. Action Plan Implementasi (3 Bulan)

| Minggu | Aktivitas |
|---|---|
| W1-2 | Pengerasan server, HTTPS, CSP, 2FA Kadis & Admin |
| W3-4 | Enkripsi at-rest NIK/KK, audit access logs, pindah berkas ke disk `secure` |
| W5-6 | Tunjuk DPO + SK Kadis, buat DPIA per layanan |
| W7-8 | Soft-delete + scrub function, hak portabilitas |
| W9-10 | Pen-test eksternal (BSSN/Diskominfo) + remediasi |
| W11 | Tabletop exercise pelanggaran data |
| W12 | Audit internal kepatuhan UU PDP + sign-off Kadis |

---

## E. Referensi
- UU No. 27/2022 tentang Pelindungan Data Pribadi
- Permenpan RB No. 14/2017 (SKM)
- Maklumat Pelayanan Dinas Sosial Pringsewu No. 920/460/D.04/X/2023
- OWASP Top 10 (2021)
- ISO/IEC 27001:2022 (opsional sertifikasi)
- Pedoman SPBE (Sistem Pemerintahan Berbasis Elektronik)
