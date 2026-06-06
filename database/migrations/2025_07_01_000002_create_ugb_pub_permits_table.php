<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ugb_pub_permits', function (Blueprint $table) {
            $table->id();
            $table->string('permit_number', 50)->unique();
            $table->string('type', 10); // UGB | PUB
            $table->string('organization', 200);
            $table->string('pic_name', 150);
            $table->string('pic_phone', 30);
            $table->string('pic_email', 150)->nullable();

            // Badan hukum
            $table->string('legal_form', 50)->nullable(); // PT, Yayasan, Koperasi, CV, dll
            $table->string('akta_notaris', 100)->nullable();
            $table->string('npwp', 30)->nullable();
            $table->string('nib', 30)->nullable();

            // Detail kegiatan
            $table->text('purpose');
            $table->date('start_date');
            $table->date('end_date');
            $table->string('area_scope', 100); // Kab. Pringsewu, Provinsi Lampung, Nasional
            $table->bigInteger('target_amount')->nullable(); // target dana/barang
            $table->string('collection_method', 200)->nullable();
            $table->string('distribution_plan', 500)->nullable();

            // Lokasi
            $table->foreignId('kecamatan_id')->nullable()->constrained('kecamatans')->nullOnDelete();
            $table->string('location_address', 500);

            // Status
            $table->string('status', 30)->default('diajukan');
            $table->foreignId('reviewed_by_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('reviewed_at')->nullable();
            $table->text('review_notes')->nullable();
            $table->string('rekomendasi_file_path')->nullable();
            $table->timestamps();

            $table->index('status');
            $table->index('type');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ugb_pub_permits');
    }
};
