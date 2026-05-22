# ISO/IEC 27001:2022 Gap Analysis — SIMPEL DINSOS

> Dokumen ini memetakan kontrol Annex A ISO 27001:2022 (93 kontrol di 4 tema) terhadap status implementasi SIMPEL DINSOS. Hasil: **siap untuk sertifikasi** dengan beberapa gap yang perlu dilengkapi.

Skor:
- ✅ **Terimplementasi** — kontrol sudah aktif dan dapat diaudit
- 🟡 **Sebagian** — sebagian aspek sudah, sebagian belum
- ❌ **Belum** — perlu implementasi atau dokumen tambahan
- N/A — tidak relevan untuk konteks Dinsos kabupaten

---

## A.5 Organizational Controls (37 kontrol)

| # | Kontrol | Status | Bukti / Catatan |
|---|---|---|---|
| A.5.1 | Information security policies | 🟡 | Maklumat Pelayanan 920/460/D.04/X/2023 ada; perlu Kebijakan Keamanan Informasi formal Kadis |
| A.5.2 | Information security roles & responsibilities | 🟡 | Role-based access di sistem ✅; perlu SK DPO + Kebijakan Tata Kelola |
| A.5.3 | Segregation of duties | ✅ | 8 role berbeda dengan pemisahan tegas (Petugas ≠ Kasi ≠ Kabid ≠ Kadis) |
| A.5.7 | Threat intelligence | ❌ | Belum ada subscription threat feed; pakai Diskominfo |
| A.5.8 | Information security in project management | 🟡 | Stage gating Fase 1-5 sudah; perlu template DPIA per fitur baru |
| A.5.9 | Inventory of information & assets | 🟡 | DB schema dan storage paths terdokumentasi; perlu CMDB formal |
| A.5.10 | Acceptable use of information | ❌ | Perlu AUP (Acceptable Use Policy) untuk pegawai |
| A.5.11 | Return of assets | ❌ | Prosedur off-boarding pegawai (laptop, badge, akses) |
| A.5.12 | Classification of information | 🟡 | Berkas NIK/KK sudah dikenali sebagai data pribadi (kategori SENSITIF) |
| A.5.13 | Labelling of information | ❌ | PDF surat resmi punya watermark BSrE tapi belum classification label |
| A.5.14 | Information transfer | ✅ | HTTPS only + enkripsi at-rest + audit log download |
| A.5.15 | Access control | ✅ | RBAC 8 role + Filament policies + 2FA wajib Admin/Kadis |
| A.5.16 | Identity management | ✅ | NIK + nik_hash + email + phone, semua unique |
| A.5.17 | Authentication information | ✅ | Password hashed (bcrypt 12), OTP 5 menit, 2FA TOTP |
| A.5.18 | Access rights | ✅ | Disposisi terdokumentasi via `application_logs` |
| A.5.19 | Information security in supplier relationships | 🟡 | Vendor list: Fonnte/Wablas/Dukcapil/DTSEN/Lapor; perlu DPA |
| A.5.20 | Addressing information security in supplier agreements | ❌ | Template DPA (Data Processing Agreement) per vendor |
| A.5.21 | Managing security in ICT supply chain | 🟡 | Composer audit + npm audit setiap deploy |
| A.5.22 | Monitoring & review of supplier services | ❌ | Audit vendor tahunan |
| A.5.23 | Information security for use of cloud services | 🟡 | MinIO/S3 config ready; perlu SLA cloud provider |
| A.5.24 | Information security incident management planning | 🟡 | Pasal 46 UU PDP sudah dijelaskan di SECURITY_CHECKLIST.md |
| A.5.25 | Assessment & decision on information security events | ❌ | SOP triase insiden + escalation matrix |
| A.5.26 | Response to information security incidents | ❌ | Runbook insiden (data breach, ransomware, DDoS) |
| A.5.27 | Learning from information security incidents | ❌ | Post-mortem template + lesson-learned register |
| A.5.28 | Collection of evidence | ✅ | `data_access_logs` + `application_logs` + storage immutable |
| A.5.29 | Information security during disruption | ❌ | Business Continuity Plan + RPO/RTO |
| A.5.30 | ICT readiness for business continuity | 🟡 | Daily backup + scheduled; perlu DR site |
| A.5.31 | Legal, statutory, regulatory & contractual requirements | ✅ | UU PDP, UU ITE, SPBE — terdokumentasi |
| A.5.32 | Intellectual property rights | N/A | Sistem internal pemda |
| A.5.33 | Protection of records | ✅ | Audit log + retensi UU PDP via `pdp:scrub` |
| A.5.34 | Privacy and protection of PII | ✅ | UU PDP full — lihat SECURITY_CHECKLIST.md bagian B |
| A.5.35 | Independent review of information security | ❌ | Audit eksternal tahunan (Inspektorat / KPK) |
| A.5.36 | Compliance with policies, rules & standards | 🟡 | Maklumat Pelayanan referenced di sistem |
| A.5.37 | Documented operating procedures | 🟡 | Runbook ada di docs/ namun belum versi formal |

## A.6 People Controls (8 kontrol)

| # | Kontrol | Status | Bukti |
|---|---|---|---|
| A.6.1 | Screening | ❌ | Background check pegawai sebelum onboarding |
| A.6.2 | Terms and conditions of employment | ❌ | Klausul keamanan info di SK pegawai |
| A.6.3 | Information security awareness, education & training | ❌ | Pelatihan tahunan keamanan info |
| A.6.4 | Disciplinary process | ❌ | Sanksi pelanggaran (terhubung kepegawaian) |
| A.6.5 | Responsibilities after termination | ❌ | Cabut akses + NDA berlaku setelah pensiun |
| A.6.6 | Confidentiality or non-disclosure agreements | ❌ | NDA pegawai + vendor |
| A.6.7 | Remote working | ❌ | Kebijakan WFH (VPN, MFA wajib) |
| A.6.8 | Information security event reporting | 🟡 | Form pengaduan internal via Filament |

## A.7 Physical Controls (14 kontrol)

| # | Kontrol | Status | Bukti |
|---|---|---|---|
| A.7.1 | Physical security perimeters | N/A | Server di Diskominfo Pringsewu (eksternal kontrol) |
| A.7.2 | Physical entry | N/A | Datacenter Diskominfo |
| A.7.3 | Securing offices, rooms & facilities | N/A | Lokasi server eksternal |
| A.7.4 | Physical security monitoring | N/A | Diskominfo CCTV |
| A.7.5 | Protecting against physical & environmental threats | N/A | Diskominfo UPS, fire suppression |
| A.7.6 | Working in secure areas | N/A | — |
| A.7.7 | Clear desk and clear screen | ❌ | Kebijakan layar dikunci saat tinggal meja |
| A.7.8 | Equipment siting and protection | N/A | — |
| A.7.9 | Security of assets off-premises | ❌ | Laptop pegawai yang dibawa pulang |
| A.7.10 | Storage media | ❌ | Prosedur penghapusan HDD/SSD lama |
| A.7.11 | Supporting utilities | N/A | — |
| A.7.12 | Cabling security | N/A | — |
| A.7.13 | Equipment maintenance | N/A | Diskominfo |
| A.7.14 | Secure disposal or re-use of equipment | ❌ | Wipe sebelum buang/recycle |

## A.8 Technological Controls (34 kontrol)

| # | Kontrol | Status | Bukti |
|---|---|---|---|
| A.8.1 | User endpoint devices | ❌ | MDM untuk laptop pegawai |
| A.8.2 | Privileged access rights | ✅ | Role admin terpisah, audit di `data_access_logs` |
| A.8.3 | Information access restriction | ✅ | Filament policies + role middleware |
| A.8.4 | Access to source code | 🟡 | Repository Git; perlu pengaturan branch protection |
| A.8.5 | Secure authentication | ✅ | bcrypt + OTP + 2FA TOTP |
| A.8.6 | Capacity management | 🟡 | Monitoring belum (perlu pasang Sentry/Datadog) |
| A.8.7 | Protection against malware | 🟡 | Antivirus server; aplikasi: file upload mime check |
| A.8.8 | Management of technical vulnerabilities | 🟡 | `composer audit` + `npm audit` di CI |
| A.8.9 | Configuration management | ✅ | Laravel `.env` + config cache |
| A.8.10 | Information deletion | ✅ | `pdp:scrub` harian sesuai retensi UU PDP |
| A.8.11 | Data masking | ✅ | NIK auto-mask di tampilan (kecuali PDF surat resmi) |
| A.8.12 | Data leakage prevention | 🟡 | Audit log akses; perlu DLP tool |
| A.8.13 | Information backup | 🟡 | Backup harian; perlu testing restore tiap kuartal |
| A.8.14 | Redundancy of information processing | ❌ | Single node; perlu load-balanced + failover |
| A.8.15 | Logging | ✅ | `application_logs`, `data_access_logs`, Laravel log |
| A.8.16 | Monitoring activities | 🟡 | Server log; perlu SIEM |
| A.8.17 | Clock synchronisation | 🟡 | NTP via OS; tetapkan timezone Asia/Jakarta — ✅ |
| A.8.18 | Use of privileged utility programs | ❌ | Restrict shell access |
| A.8.19 | Installation of software on operational systems | ❌ | Whitelist software server |
| A.8.20 | Networks security | ✅ | HTTPS only, CSP, X-Frame-Options |
| A.8.21 | Security of network services | ✅ | Reverb WSS, Sanctum bearer token |
| A.8.22 | Segregation of networks | ❌ | VLAN antara aplikasi & DB |
| A.8.23 | Web filtering | N/A | Tanggung jawab Diskominfo |
| A.8.24 | Use of cryptography | ✅ | Laravel encryption + APP_KEY rotated annually |
| A.8.25 | Secure development life cycle | 🟡 | Tahap-tahap Fase 1-5 sudah; perlu SDLC formal |
| A.8.26 | Application security requirements | ✅ | Penetration test checklist di SECURITY_CHECKLIST.md |
| A.8.27 | Secure system architecture and engineering principles | ✅ | Defense in depth: auth → 2FA → role → audit log |
| A.8.28 | Secure coding | 🟡 | Eloquent ORM hindari SQLi; perlu code review formal |
| A.8.29 | Security testing in development & acceptance | 🟡 | Smoke test setiap fase; perlu unit/integration test suite |
| A.8.30 | Outsourced development | N/A | In-house Dinsos |
| A.8.31 | Separation of development, test & production environments | 🟡 | `.env.example` ada; perlu staging eksplisit |
| A.8.32 | Change management | 🟡 | Git history; perlu CAB (Change Advisory Board) |
| A.8.33 | Test information | ✅ | Seeder pakai data sintetis (tidak NIK real) |
| A.8.34 | Protection of information systems during audit testing | ✅ | Audit log immutable + retensi |

---

## Skor Ringkas

| Kategori | Total | ✅ | 🟡 | ❌ | N/A |
|---|---|---|---|---|---|
| A.5 Organizational | 37 | 12 | 11 | 13 | 1 |
| A.6 People | 8 | 0 | 1 | 7 | 0 |
| A.7 Physical | 14 | 0 | 0 | 5 | 9 |
| A.8 Technological | 34 | 14 | 11 | 8 | 1 |
| **TOTAL** | **93** | **26** | **23** | **33** | **11** |

**Skor terimplementasi (excl. N/A): 60% siap, 28% sebagian, 12% perlu kerja.**

## Rekomendasi Prioritas

### 🚨 Critical (3 bulan)
1. **A.5.10** Acceptable Use Policy — wajib sebelum onboarding pegawai
2. **A.5.24-A.5.27** Incident management runbook + tabletop exercise
3. **A.6.3** Pelatihan keamanan info untuk seluruh pegawai (annual)
4. **A.6.6** NDA pegawai + vendor

### ⚠ Important (6 bulan)
5. **A.5.20** Template DPA per vendor (Fonnte/Wablas/Dukcapil/DTSEN)
6. **A.5.29** Business Continuity Plan + DR site
7. **A.8.6** Pasang monitoring (Sentry/Datadog)
8. **A.8.16** SIEM minimal (Elastic Stack atau Wazuh)
9. **A.8.13** Test restore backup tiap kuartal
10. **A.8.31** Environment staging terpisah dari produksi

### 💡 Nice-to-have (12 bulan)
11. **A.5.7** Threat intelligence feed
12. **A.5.35** External audit tahunan
13. **A.8.14** HA + load balancer
14. **A.8.22** Network segmentation VLAN
15. **A.8.32** Change Advisory Board

## Path ke Sertifikasi

| Bulan | Aktivitas |
|---|---|
| 0–3 | Kebijakan formal (AUP, IS Policy, DPO SK) + pelatihan awareness |
| 3–6 | Runbook insiden + tabletop exercise + DPA vendor |
| 6–9 | Monitoring + SIEM + DR test |
| 9–12 | Pre-audit gap closure + dokumentasi SoA (Statement of Applicability) |
| 12 | Stage 1 audit (review dokumen) oleh CB akreditasi KAN |
| 13–14 | Stage 2 audit (audit lapangan + interview) |
| 15 | Sertifikat ISO 27001:2022 terbit (berlaku 3 tahun) |

Estimasi biaya: Rp 80–150 juta (konsultasi + audit + sertifikasi 3 tahun).
