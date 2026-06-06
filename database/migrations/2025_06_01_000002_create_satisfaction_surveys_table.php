<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('satisfaction_surveys', function (Blueprint $table) {
            $table->id();
            $table->foreignId('application_id')->constrained('applications')->cascadeOnDelete();
            // Skala 1-5 untuk 9 unsur SKM (Permenpan 14/2017)
            $table->tinyInteger('persyaratan')->nullable();
            $table->tinyInteger('prosedur')->nullable();
            $table->tinyInteger('waktu')->nullable();
            $table->tinyInteger('biaya')->nullable();
            $table->tinyInteger('produk')->nullable();
            $table->tinyInteger('kompetensi')->nullable();
            $table->tinyInteger('perilaku')->nullable();
            $table->tinyInteger('sarana')->nullable();
            $table->tinyInteger('penanganan_pengaduan')->nullable();
            $table->text('saran')->nullable();
            $table->string('respondent_name', 150)->nullable();
            $table->string('respondent_contact', 100)->nullable();
            $table->timestamp('submitted_at')->useCurrent();
            $table->timestamps();

            $table->index('submitted_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('satisfaction_surveys');
    }
};
