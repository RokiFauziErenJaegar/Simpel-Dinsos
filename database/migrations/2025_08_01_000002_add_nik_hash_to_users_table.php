<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // Drop unique index lama
            $table->dropUnique(['nik']);
        });

        Schema::table('users', function (Blueprint $table) {
            // Hash deterministik untuk lookup (SHA-256 dari NIK + APP_KEY)
            $table->string('nik_hash', 64)->nullable()->after('nik')->unique();
            $table->index('nik_hash', 'users_nik_hash_idx');
        });

        Schema::table('users', function (Blueprint $table) {
            // Perbesar kolom nik agar muat hasil enkripsi Laravel (ciphertext panjang)
            $table->text('nik')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropIndex('users_nik_hash_idx');
            $table->dropColumn('nik_hash');
        });
        Schema::table('users', function (Blueprint $table) {
            $table->string('nik', 16)->nullable()->unique()->change();
        });
    }
};
