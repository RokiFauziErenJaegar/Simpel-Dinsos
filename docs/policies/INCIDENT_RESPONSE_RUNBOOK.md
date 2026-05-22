# Incident Response Runbook — SIMPEL DINSOS

> Panduan operasional saat terjadi insiden keamanan informasi pada SIMPEL DINSOS. Wajib diikuti DPO, Admin TI, dan Kepala Dinas saat insiden terdeteksi.
>
> **Kontrol ISO 27001:** A.5.24–A.5.30 (Incident Management & Business Continuity)
> **Regulasi:** UU PDP 27/2022 Pasal 46 (notifikasi dalam 3×24 jam)

## 1. Definisi & Klasifikasi

### Kategori Insiden

| Kategori | Contoh | Severity | Notifikasi |
|---|---|---|---|
| **P0 Kritis** | Data breach masif (>1000 NIK bocor), ransomware aktif, sistem down >2 jam | URGENT | Komisi PDP, Bupati, Polri (3×24 jam) |
| **P1 Tinggi** | Bocor data <100 NIK, akun admin dibajak, DDoS aktif | HIGH | Kadis, Inspektorat (1×24 jam) |
| **P2 Sedang** | Bug yang berpotensi data leak, brute force gagal, phishing email pegawai | MEDIUM | Kadis (3×24 jam) |
| **P3 Rendah** | Spam, false positive antivirus, kesalahan input pegawai | LOW | Internal DPO |

## 2. Tim Tanggap Insiden (CSIRT)

| Peran | Penanggung Jawab | Kontak |
|---|---|---|
| **Incident Commander** | Sekretaris Dinas | TBD |
| **Lead Investigator** | DPO | TBD |
| **Technical Lead** | Admin TI / Diskominfo | TBD |
| **Communications** | Humas Pemda Pringsewu | TBD |
| **Legal Advisor** | Bagian Hukum Pemda | TBD |

### Channel Komunikasi Darurat
- WhatsApp Grup "CSIRT Dinsos" (terenkripsi end-to-end)
- Bridge phone: ditentukan saat insiden
- Backup: email ke csirt@dinsospringsewu.local

## 3. Tahap Tanggap (NIST SP 800-61)

### 3.1 DETECTION (deteksi)

**Sumber deteksi:**
- Alert otomatis dari sistem (failed login spike, audit log anomali, antivirus)
- Laporan pegawai → DPO
- Laporan warga → call center → DPO
- Laporan eksternal (BSSN, peneliti keamanan)

**Tindakan awal (5 menit pertama):**
1. Triase severity P0/P1/P2/P3
2. Aktifkan CSIRT WA grup
3. DPO buka tiket di buku log insiden (file fisik + digital terenkripsi)
4. Jangan panik, jangan langsung shutdown server

### 3.2 CONTAINMENT (penahanan)

#### Skenario A: Data Breach Aktif
```bash
# 1. Isolasi: putus akses publik tanpa hapus data
php artisan down --message="Maintenance"

# 2. Cabut seluruh API token aktif
php artisan tinker
> DB::table('personal_access_tokens')->delete();
> DB::table('sessions')->delete();

# 3. Reset password seluruh role admin/kadis
> User::whereIn('role', ['admin', 'kadis'])->each(fn($u) => $u->update(['password' => Hash::make(str()->random(32))]));

# 4. Identifikasi entry point dari audit log
> DataAccessLog::where('created_at', '>', now()->subHour())->orderByDesc('id')->get();
```

#### Skenario B: Ransomware
1. **JANGAN** matikan komputer terinfeksi — gunakan disconnect kabel jaringan.
2. Foto layar untuk evidence.
3. Hubungi Diskominfo + BSSN (cert.bssn.go.id, hotline 021-7805522).
4. **JANGAN BAYAR TEBUSAN** — selalu tolak.
5. Restore dari backup terbersih (cek backup integrity via `php artisan backup:list`).

#### Skenario C: Akun Admin Dibajak
1. Disable akun: `User::where('email', $compromised)->update(['is_active' => false, 'two_factor_secret' => null]);`
2. Force logout semua session: `DB::table('sessions')->where('user_id', $userId)->delete();`
3. Audit semua aktivitas dari akun: `DataAccessLog::where('actor_user_id', $userId)->get();`
4. Cek IP login terakhir → cross-check dengan IP yang dikenal pegawai
5. Jika data dimodifikasi: rollback dari audit log atau backup

#### Skenario D: DDoS
1. Aktifkan Cloudflare WAF / rate limiter
2. Set `RateLimiter::for('global', fn () => Limit::perMinute(60))` di `bootstrap/app.php`
3. Blokir IP/range pelaku via Diskominfo firewall
4. Notifikasi warga: pakai TV lobi dan media sosial Dinsos

### 3.3 ERADICATION (eradikasi)

1. Patch celah keamanan (update Laravel, Filament, paket pihak ketiga)
2. Scan malware pada seluruh komputer pegawai
3. Force password change untuk semua user
4. Audit kode untuk pastikan tidak ada backdoor
5. Update tanda tangan VAPID / API token

### 3.4 RECOVERY (pemulihan)

1. Restore dari backup terbersih jika data terkorupsi
2. Verifikasi integritas data: hash check, foreign key sanity
3. Bertahap buka akses: internal dulu → publik
4. Monitor anomali 72 jam pasca-pemulihan
5. `php artisan up` untuk mengembalikan layanan

### 3.5 POST-INCIDENT (pasca-insiden)

#### Notifikasi UU PDP Pasal 46 (3×24 jam)

**Wajib notifikasi:**
1. **Komisi PDP** (kominfo.go.id/pdp) jika data pribadi terdampak
2. **Subjek data** (warga terdampak) via WA + email
3. **Otoritas terkait** (Inspektorat, Bupati, Polri jika tindak pidana)

**Template notifikasi ke subjek data:**

> Yth. Bapak/Ibu [Nama],
>
> Kami dari Dinas Sosial Kabupaten Pringsewu memberitahukan bahwa pada tanggal [tgl] terjadi insiden keamanan informasi yang berdampak pada data pribadi Anda di SIMPEL DINSOS, berupa: [jenis data].
>
> Tindakan yang telah kami lakukan: [containment + recovery].
>
> Saran untuk Anda: [ganti password, awasi pinjol mengatasnamakan Anda, dll].
>
> Hubungi kami untuk informasi lebih lanjut: 0822-6986-7911.
>
> Kepala Dinas Sosial,
> [Nama Kadis]

#### Post-Mortem (1 minggu setelah resolved)

Template di `docs/incidents/INC-YYYY-NNN-post-mortem.md`:
```markdown
# INC-2026-001: [Judul Singkat]

## Ringkasan
- **Waktu deteksi**: 2026-XX-XX HH:MM WIB
- **Waktu resolved**: 2026-XX-XX HH:MM WIB
- **Severity**: P0/P1/P2/P3
- **Tim**: [siapa terlibat]

## Timeline
- HH:MM — [event]
- HH:MM — [event]

## Akar Masalah (5-Whys)
1. ...

## Dampak
- Subjek data terdampak: X warga
- Layanan terganggu: [list]
- Reputasi: [dampak]

## Action Items
- [ ] [tindakan + PIC + tenggat]

## Lesson Learned
- [pembelajaran]
```

## 4. Tabletop Exercise

Wajib dilakukan **tiap 6 bulan** oleh CSIRT. Skenario rotasi:
1. Phishing email Kadis → akun dibajak → audit log akses
2. Ransomware menyerang server → backup restore drill
3. Pegawai bocor 50 NIK ke WA grup → notifikasi UU PDP
4. DDoS saat penyaluran bansos → activate failover

Dokumentasi exercise: `docs/incidents/tabletop-YYYY-MM.md`

## 5. Backup & Restore

### Backup Otomatis
- Database: harian via `php artisan backup:run` (jika spatie/laravel-backup terpasang) atau cron `mysqldump` ke `/var/backups/`
- Storage berkas: harian sync ke S3/MinIO bucket terpisah
- Retensi: harian 30 hari, mingguan 12 minggu, bulanan 12 bulan

### Test Restore (kuartalan)
```bash
# 1. Buat database test
mysql -e "CREATE DATABASE simpel_dinsos_restore_test"

# 2. Restore backup
mysql simpel_dinsos_restore_test < /var/backups/simpel-dinsos-2026-05-20.sql

# 3. Verifikasi integritas
APP_DB=simpel_dinsos_restore_test php artisan db:check
```

## 6. Kontak Eksternal

- **BSSN CERT-ID**: csirt@bssn.go.id · 021-7805522
- **Komisi PDP Kominfo**: pdp@kominfo.go.id
- **Polri Cyber Crime**: cybercrime.polri.go.id
- **Diskominfo Pringsewu**: ext. internal

---

**Versi**: 1.0 · **Berlaku**: 1 Juni 2026 · **Review**: 6 bulan sekali oleh DPO + CSIRT
