# Panduan Multi-Tenant — SIMPEL DINSOS

> Status saat ini: **single-tenant** (Kabupaten Pringsewu). Dokumen ini menjelaskan strategi untuk ekspansi ke kabupaten lain.

## Tiga Mode Tenancy

### Mode 1: `single` (saat ini)
Satu instalasi = satu kabupaten. Paling sederhana, performa tertinggi. Cocok untuk Pringsewu sendirian.

Konfigurasi:
```env
TENANT_MODE=single
TENANT_ID=pringsewu
```

### Mode 2: `shared-db` (multi-tenant kolom)
Satu database, tabel utama punya kolom `tenant_id`. Lebih hemat infrastruktur, cocok 5–20 kabupaten.

Langkah migrasi:
1. Set `TENANT_MODE=shared-db` di `.env`
2. Buat migrasi tambahan: tambah kolom `tenant_id` ke `applications`, `complaints`, `lks`, `ugb_pub_permits`, `users`, `output_documents`, `ppks_profiles`
3. Backfill `tenant_id='pringsewu'` ke seluruh data eksisting
4. Buat model `Tenant` + tabel `tenants`
5. Tambah global scope di model utama untuk auto-filter berdasarkan tenant aktif
6. Buat middleware `IdentifyTenant` — resolve dari subdomain (`pringsewu.simpel-dinsos.id`) atau header
7. Update Filament resource agar tampilkan dropdown tenant untuk role super-admin

### Mode 3: `per-db` (database terpisah)
Setiap kabupaten = database terpisah. Isolasi data maksimal, cocok 20+ kabupaten atau jika ada kebutuhan regulasi pemisahan.

Langkah migrasi:
1. Set `TENANT_MODE=per-db`
2. Tambah connection per tenant di `config/database.php`
3. Buat middleware yang `DB::setDefaultConnection($tenant->db_connection)` di awal request
4. Buat command `tenant:create {id}` untuk provisioning DB baru + migrate + seed
5. Storage juga dipisah (folder `storage/app/{tenant_id}/...`)

## Komponen yang Sudah Tenant-Aware

| Komponen | Status |
|---|---|
| `config/tenant.php` | ✅ Konfigurasi current tenant (Pringsewu) di env |
| Template surat PDF | ⚠ Sudah pakai data dari `config('tenant.current.*')` jika diaktifkan |
| Kop logo | ⚠ Simpan per tenant di `storage/app/public/kops/{id}.png` |
| Kode wilayah NIK | ✅ Tervalidasi via `tenant.current.kode_wilayah` di DukcapilService mock |
| Laporan Bulanan PDF | ⚠ Bisa dipisah per tenant via scope |

## Yang Perlu Diadaptasi untuk Multi-Tenant

- Daftar 16 layanan: bisa di-share global (semua kabupaten punya layanan Dinsos sama) atau di-override per tenant
- 9 kecamatan + pekons: per tenant (data wilayah berbeda)
- Akun pegawai: per tenant
- Kapasitas SLA: bisa berbeda per tenant
- Stempel & TTD Kadis: per tenant

## Checklist Migrasi Single → Shared-DB (4 jam estimasi)

- [ ] Buat tabel `tenants` (id, name, kode_wilayah, settings JSON)
- [ ] Buat model `Tenant` + seeder Pringsewu
- [ ] Migration: add `tenant_id` ke 7 tabel utama, default ke ID Pringsewu
- [ ] Buat trait `BelongsToTenant` + global scope
- [ ] Buat middleware `IdentifyTenant` (subdomain / header / session)
- [ ] Update `AppServiceProvider` untuk binding tenant resolver
- [ ] Update Filament: tambah role `super-admin` yang bypass tenant scope
- [ ] Update `MonthlyReportGenerator` untuk filter tenant
- [ ] Update kop di template surat (logo + nama instansi dari tenant)
- [ ] Update SignatureAssetGenerator (per tenant per signer)
- [ ] Test pen-test ulang: pastikan tidak ada data leakage lintas tenant
- [ ] Smoke test 2 tenant simulasi
