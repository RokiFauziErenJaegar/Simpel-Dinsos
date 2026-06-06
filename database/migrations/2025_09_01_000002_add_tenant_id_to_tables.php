<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Tambah kolom tenant_id ke tabel-tabel utama untuk mendukung
     * mode shared-db multi-tenant. Default value diisi via seeder.
     */
    public function up(): void
    {
        $tables = [
            'applications',
            'application_documents',
            'application_logs',
            'complaints',
            'lks',
            'output_documents',
            'ppks_profiles',
            'queue_tickets',
            'satisfaction_surveys',
            'ugb_pub_permits',
            'users',
            'service_types',
            'kecamatans',
            'pekons',
        ];

        foreach ($tables as $t) {
            Schema::table($t, function (Blueprint $table) use ($t) {
                $table->foreignId('tenant_id')->nullable()->after('id')->constrained('tenants')->nullOnDelete();
                $table->index('tenant_id', $t.'_tenant_idx');
            });
        }
    }

    public function down(): void
    {
        $tables = [
            'applications', 'application_documents', 'application_logs',
            'complaints', 'lks', 'output_documents', 'ppks_profiles',
            'queue_tickets', 'satisfaction_surveys', 'ugb_pub_permits',
            'users', 'service_types', 'kecamatans', 'pekons',
        ];
        foreach ($tables as $t) {
            Schema::table($t, function (Blueprint $table) use ($t) {
                $table->dropIndex($t.'_tenant_idx');
                $table->dropForeign(['tenant_id']);
                $table->dropColumn('tenant_id');
            });
        }
    }
};
