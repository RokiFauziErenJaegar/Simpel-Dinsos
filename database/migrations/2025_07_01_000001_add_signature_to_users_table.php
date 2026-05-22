<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('signature_path')->nullable()->after('last_login_at');
            $table->string('stamp_path')->nullable()->after('signature_path');
            $table->string('jabatan_full', 200)->nullable()->after('stamp_path');
            $table->string('nip', 30)->nullable()->after('jabatan_full');
            $table->string('pangkat', 100)->nullable()->after('nip');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['signature_path', 'stamp_path', 'jabatan_full', 'nip', 'pangkat']);
        });
    }
};
