<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * is_validated butuh 3 keadaan untuk alur verifikasi/pengembalian:
 *   null  = belum direview (pending)
 *   true  = valid / sesuai
 *   false = ditandai tidak sesuai (perlu diunggah ulang)
 *
 * Default lama `false` membuat berkas yang belum direview rancu dengan
 * berkas yang ditandai bermasalah. Ubah jadi nullable default null,
 * dan normalisasi data lama (false → null = belum direview).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('application_documents', function (Blueprint $table) {
            $table->boolean('is_validated')->nullable()->default(null)->change();
        });

        // Aman: belum ada mekanisme lama yang men-set `true`, jadi semua `false`
        // sebenarnya berarti "belum direview".
        DB::table('application_documents')->where('is_validated', false)->update(['is_validated' => null]);
    }

    public function down(): void
    {
        DB::table('application_documents')->whereNull('is_validated')->update(['is_validated' => false]);

        Schema::table('application_documents', function (Blueprint $table) {
            $table->boolean('is_validated')->default(false)->change();
        });
    }
};
