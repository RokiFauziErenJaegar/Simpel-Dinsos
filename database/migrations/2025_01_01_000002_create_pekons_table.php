<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('pekons', function (Blueprint $table) {
            $table->id();
            $table->foreignId('kecamatan_id')->constrained('kecamatans')->cascadeOnDelete();
            $table->string('name', 100);
            $table->string('type', 20)->default('pekon');
            $table->timestamps();

            $table->index(['kecamatan_id', 'name']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pekons');
    }
};
