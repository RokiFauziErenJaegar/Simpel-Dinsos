<?php

namespace Database\Seeders;

use App\Models\Tenant;
use Illuminate\Database\Seeder;

class TenantSeeder extends Seeder
{
    public function run(): void
    {
        $tenant = Tenant::firstOrCreate(
            ['slug' => 'pringsewu'],
            [
                'name' => 'Kabupaten Pringsewu',
                'kode_wilayah' => '187103',
                'instansi' => 'Dinas Sosial Kabupaten Pringsewu',
                'alamat' => 'Jl. Dr. dr. Sugiri Syarief, Komplek Perkantoran Pemerintah Daerah Kabupaten Pringsewu',
                'kode_pos' => '35372',
                'call_center' => '0822-6986-7911',
                'email' => 'pringsewudinsos@gmail.com',
                'maklumat' => '920/460/D.04/X/2023',
                'primary_color' => '#1E4D8C',
                'is_active' => true,
            ]
        );

        // Backfill tenant_id ke seluruh data eksisting
        $tables = [
            'applications', 'application_documents', 'application_logs',
            'complaints', 'lks', 'output_documents', 'ppks_profiles',
            'queue_tickets', 'satisfaction_surveys', 'ugb_pub_permits',
            'users', 'service_types', 'kecamatans', 'pekons',
        ];

        foreach ($tables as $t) {
            \DB::table($t)->whereNull('tenant_id')->update(['tenant_id' => $tenant->id]);
        }

        // Tenant contoh tambahan (untuk demo multi-tenant) — non-aktif by default
        Tenant::firstOrCreate(
            ['slug' => 'pesawaran'],
            [
                'name' => 'Kabupaten Pesawaran',
                'kode_wilayah' => '180209',
                'instansi' => 'Dinas Sosial Kabupaten Pesawaran',
                'is_active' => false,
            ]
        );
        Tenant::firstOrCreate(
            ['slug' => 'tanggamus'],
            [
                'name' => 'Kabupaten Tanggamus',
                'kode_wilayah' => '180202',
                'instansi' => 'Dinas Sosial Kabupaten Tanggamus',
                'is_active' => false,
            ]
        );
    }
}
