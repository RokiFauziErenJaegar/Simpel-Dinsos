<?php

/*
 * Konfigurasi tenant — siapkan ekspansi ke kabupaten lain.
 *
 * Saat ini sistem berjalan single-tenant (Kabupaten Pringsewu).
 * Untuk multi-tenant penuh (mis. ekspansi ke 15 Dinsos kabupaten lain di Lampung),
 * lihat panduan migrasi di docs/MULTI_TENANT.md.
 */

return [

    'mode' => env('TENANT_MODE', 'single'), // single | shared-db | per-db

    'current' => [
        'id' => env('TENANT_ID', 'pringsewu'),
        'name' => env('TENANT_NAME', 'Kabupaten Pringsewu'),
        'kode_wilayah' => env('TENANT_KODE_WILAYAH', '187103'), // kode kab di NIK
        'instansi' => env('TENANT_INSTANSI', 'Dinas Sosial Kabupaten Pringsewu'),
        'alamat' => env('TENANT_ALAMAT', 'Jl. Dr. dr. Sugiri Syarief, Komplek Perkantoran Pemda Pringsewu'),
        'kode_pos' => env('TENANT_KODE_POS', '35372'),
        'call_center' => env('TENANT_CALL_CENTER', '0822-6986-7911'),
        'email' => env('TENANT_EMAIL', 'pringsewudinsos@gmail.com'),
        'maklumat' => env('TENANT_MAKLUMAT', '920/460/D.04/X/2023'),
        'kop_logo' => env('TENANT_KOP_LOGO', 'kops/pringsewu.png'),
        'primary_color' => env('TENANT_PRIMARY_COLOR', '#1E4D8C'),
    ],

    /*
     * Untuk mode 'shared-db': tambahkan kolom tenant_id ke tabel utama
     * (applications, complaints, lks, dst.) dan filter via global scope.
     *
     * Untuk mode 'per-db': sediakan database terpisah per kabupaten,
     * resolve connection name via subdomain atau header X-Tenant.
     */
    'tenants' => [
        // Daftar tenant aktif — di-load dari DB ketika mode != 'single'.
        // Format: 'id' => ['name' => ..., 'kode_wilayah' => ..., 'db_connection' => ...]
    ],

];
