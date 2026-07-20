<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Fitur 5 — Lokasi pelayanan pada pengajuan.
 * Nullable: pengajuan online yang belum ditangani petugas belum punya lokasi
 * (ditampilkan sebagai "Online / Belum diproses"). Di-stamp mengikuti lokasi
 * petugas saat pertama kali pengajuan disentuh (panggil/verifikasi/terbit).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('applications', function (Blueprint $table) {
            $table->string('location', 10)->nullable()->after('priority');
            $table->index('location');
        });
    }

    public function down(): void
    {
        Schema::table('applications', function (Blueprint $table) {
            $table->dropIndex(['location']);
            $table->dropColumn('location');
        });
    }
};
