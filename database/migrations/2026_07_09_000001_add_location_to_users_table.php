<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Fitur 5 — Lokasi pelayanan (Dinsos vs MPP).
 * Tiap akun petugas diberi lokasi kerja tetap. Pengajuan/KIE yang ditangani
 * petugas akan mengambil lokasi ini. Default 'dinsos' agar data lama konsisten.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('location', 10)->nullable()->default('dinsos')->after('is_active');
            $table->index('location');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropIndex(['location']);
            $table->dropColumn('location');
        });
    }
};
