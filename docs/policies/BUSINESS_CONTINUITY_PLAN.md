# Business Continuity Plan & Disaster Recovery (BCP/DRP)

> **Kontrol ISO 27001:** A.5.29 (Information Security during disruption), A.5.30 (ICT readiness for BCP), A.8.13 (Backup), A.8.14 (Redundancy)

## 1. Tujuan

Memastikan layanan SIMPEL DINSOS tetap berjalan saat terjadi gangguan: bencana alam, ransomware, hardware failure, atau gangguan operasional lainnya.

## 2. Target Recovery

| Layanan | RTO (max downtime) | RPO (max data loss) |
|---|---|---|
| **Antrian TV Lobi** | 1 jam | 0 |
| **Pengajuan online** | 4 jam | 1 jam |
| **Filament admin (petugas)** | 4 jam | 1 jam |
| **Dashboard Kadis** | 8 jam | 1 hari |
| **Laporan Bulanan PDF** | 24 jam | 1 hari |
| **REST API mobile** | 4 jam | 1 jam |

## 3. Skenario Gangguan & Tindakan

### S1. Server Aplikasi Down (hardware/network)
**Detection**: monitoring uptime gagal 3 menit
**Response**:
1. Auto-failover ke standby server (jika ada HA)
2. Manual: DNS switch ke server backup di Diskominfo
3. RTO: 1-2 jam

### S2. Database Korup / Hilang
**Detection**: query gagal, integritas referensial pecah
**Response**:
1. Stop aplikasi: `php artisan down`
2. Backup database saat ini (forensic): `mysqldump --routines simpel_dinsos > forensic.sql`
3. Restore dari backup terbaru
4. Reconcile data via audit log untuk gap antara backup dan crash
5. RTO: 4 jam, RPO: 1 hari (atau 1 jam jika backup hourly aktif)

### S3. Datacenter Diskominfo Tidak Tersedia
**Detection**: koneksi internal hilang >15 menit
**Response**:
1. Spin up DR site di provider cloud (Biznet/Indonesian Cloud/AWS ap-southeast-3)
2. Restore database snapshot terakhir
3. Restore storage berkas dari S3/MinIO offsite backup
4. Update DNS ke DR site
5. RTO: 8 jam, RPO: 24 jam

### S4. Ransomware Massal
Lihat [INCIDENT_RESPONSE_RUNBOOK.md](INCIDENT_RESPONSE_RUNBOOK.md) Skenario B.

### S5. Bencana Alam (Banjir, Gempa, Kebakaran Kantor)
**Pre-event**:
- Backup harian rutin offsite (S3/MinIO di datacenter berbeda)
- Kontak alternatif (HP backup, hotspot 4G)
**Post-event**:
1. Aktivasi command center di lokasi alternatif (kantor kecamatan, balai desa, hotel)
2. Tim CSIRT remote work dari rumah dengan VPN
3. Komunikasi ke warga: TV lobi + radio lokal + medsos Dinsos

## 4. Strategi Backup

### 4.1 Frekuensi
| Aset | Frekuensi | Lokasi | Retensi |
|---|---|---|---|
| Database SQL dump | Setiap jam | Local + S3 offsite | 30 hari local, 12 bulan offsite |
| Storage berkas (KTP/KK) | Real-time sync | S3 offsite | 3 tahun (sesuai UU PDP) |
| `storage/app/secure/` | Harian terenkripsi | S3 offsite | 3 tahun |
| Source code | Git remote | GitHub/GitLab | Permanen |
| .env (terenkripsi GPG) | Setiap perubahan | Vault DPO | Permanen |
| Logs aplikasi | Harian | S3 offsite | 1 tahun |

### 4.2 Enkripsi Backup
```bash
# Backup database terenkripsi
mysqldump simpel_dinsos | gzip | gpg -c --batch --passphrase-file /etc/backup.key > backup-$(date +%F).sql.gz.gpg

# Restore
gpg -d --batch --passphrase-file /etc/backup.key backup.sql.gz.gpg | gunzip | mysql simpel_dinsos
```

### 4.3 Test Restore (Wajib Kuartalan)
```bash
# Setiap 3 bulan, lakukan dry-run restore ke staging database
# Verifikasi:
# 1. Backup tidak korup
# 2. Schema migrate clean
# 3. Sample query mengembalikan hasil sama
# 4. Foreign key integrity 100%

php artisan backup:test --db=simpel_dinsos_test
```

## 5. Infrastruktur HA (Roadmap)

### Phase 1 (saat ini): Single Server
- 1 server aplikasi + DB di datacenter Diskominfo
- Daily backup ke S3 offsite

### Phase 2 (6 bulan): Active-Passive
- 2 server: primary + standby (warm)
- DB replication master-slave
- DNS failover otomatis (Cloudflare)

### Phase 3 (12 bulan): Active-Active
- Load balancer (HAProxy / Nginx)
- 2+ application server stateless
- DB cluster (MySQL Group Replication / Galera)
- Redis untuk session & cache shared
- MinIO cluster untuk storage

## 6. Komunikasi Krisis

### 6.1 Internal
- WA Grup CSIRT (terenkripsi)
- Bridge phone Zoom/Meet untuk daily standup saat krisis
- Status page internal: `status.simpel-dinsos.internal`

### 6.2 Eksternal
- Halaman publik status: `simpel-dinsos.pringsewu.go.id/status` (statis di CDN, tidak terdampak server down)
- Twitter/Instagram resmi Dinsos: announce setiap perubahan status
- Banner di TV lobi: instruksi alternatif (datang ke loket manual jika web down)

### 6.3 Press Release
- Disusun oleh Humas + DPO
- Approval Kadis sebelum publish
- Hindari blame-storming publik

## 7. Drill Schedule

| Waktu | Drill |
|---|---|
| Setiap bulan | Test backup restore (otomatis script) |
| Setiap kuartal | Tabletop exercise CSIRT (1 jam) |
| Setiap semester | Failover drill ke DR site (4 jam) |
| Setiap tahun | Full BCP exercise dengan stakeholder (1 hari) |

## 8. Anggaran BCP Tahunan

| Item | Estimasi |
|---|---|
| Storage backup S3 (1 TB) | Rp 1.5 jt/tahun |
| Standby server DR | Rp 12 jt/tahun |
| MinIO cluster on-premise | Rp 30 jt sekali (hardware) |
| Cyber insurance | Rp 8-15 jt/tahun |
| Tabletop exercise (catering, fasilitas) | Rp 2 jt/tahun |
| Pelatihan CSIRT | Rp 5 jt/tahun |
| **Total estimasi** | **Rp 50-65 jt/tahun** |

## 9. Sign-off

| Peran | Nama | Tanda Tangan | Tanggal |
|---|---|---|---|
| Kepala Dinas | Debi Hardian, S.Pi., M.Si. | | |
| Sekretaris (Incident Commander) | TBD | | |
| DPO | TBD | | |
| Admin TI / Diskominfo Liaison | TBD | | |

---

**Versi**: 1.0 · **Review**: tahunan · **Approved**: Kepala Dinas Sosial Kab. Pringsewu
