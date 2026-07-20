<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Fitur 2 — Konsultasi Warga (KIE: Komunikasi, Informasi & Edukasi).
 *
 * Modul TERPISAH dari 16 layanan (punya tabel & counter sendiri) untuk
 * mendokumentasikan warga yang datang berkonsultasi ke Dinsos/MPP — pekerjaan
 * petugas yang selama ini tidak tercatat. Warga mengisi data diri + no. WA
 * lewat web sebelum konsultasi; sistem mengirim pesan WA konfirmasi.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('kie_consultations', function (Blueprint $table) {
            $table->id();
            $table->string('code', 30)->unique();           // KIE-YYYY-####
            $table->string('name', 150);                     // nama warga
            $table->string('phone', 20);                     // no. WA (wajib, utk notifikasi)
            $table->string('nik', 32)->nullable();           // opsional
            $table->string('address', 255)->nullable();
            $table->string('topic', 100)->nullable();        // kategori/topik konsultasi
            $table->text('description')->nullable();         // uraian keperluan
            $table->string('status', 20)->default('registered'); // registered | served | done
            $table->string('location', 10)->nullable();      // dinsos | mpp (ikut petugas / ?loc)
            $table->foreignId('handled_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('notified_at')->nullable();    // kapan WA konfirmasi terkirim
            $table->timestamp('served_at')->nullable();
            $table->unsignedBigInteger('tenant_id')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index('created_at');
            $table->index('status');
            $table->index('location');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('kie_consultations');
    }
};
