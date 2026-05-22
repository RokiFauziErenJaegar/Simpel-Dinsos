<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('role', 30)->default('warga')->after('password');
            $table->string('nik', 16)->nullable()->unique()->after('role');
            $table->string('phone', 20)->nullable()->after('nik');
            $table->string('address', 500)->nullable()->after('phone');
            $table->foreignId('kecamatan_id')->nullable()->after('address')->constrained('kecamatans')->nullOnDelete();
            $table->foreignId('pekon_id')->nullable()->after('kecamatan_id')->constrained('pekons')->nullOnDelete();
            $table->boolean('is_active')->default(true)->after('pekon_id');
            $table->timestamp('last_login_at')->nullable()->after('is_active');

            $table->index('role');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['kecamatan_id']);
            $table->dropForeign(['pekon_id']);
            $table->dropColumn(['role', 'nik', 'phone', 'address', 'kecamatan_id', 'pekon_id', 'is_active', 'last_login_at']);
        });
    }
};
