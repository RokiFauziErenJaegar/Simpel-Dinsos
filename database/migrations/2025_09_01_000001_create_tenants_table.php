<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tenants', function (Blueprint $table) {
            $table->id();
            $table->string('slug', 50)->unique();
            $table->string('name', 200);
            $table->string('kode_wilayah', 10)->nullable();
            $table->string('instansi', 200);
            $table->string('alamat', 500)->nullable();
            $table->string('kode_pos', 10)->nullable();
            $table->string('call_center', 30)->nullable();
            $table->string('email', 150)->nullable();
            $table->string('maklumat', 100)->nullable();
            $table->string('kop_logo')->nullable();
            $table->string('primary_color', 20)->default('#1E4D8C');
            $table->json('settings')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tenants');
    }
};
