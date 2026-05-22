<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('lks', function (Blueprint $table) {
            $table->id();
            $table->string('registration_number', 50)->unique();
            $table->string('name', 200);
            $table->string('type', 50)->default('LKS');
            $table->string('address', 500);
            $table->foreignId('kecamatan_id')->nullable()->constrained('kecamatans')->nullOnDelete();
            $table->string('contact_person', 150)->nullable();
            $table->string('phone', 30)->nullable();
            $table->string('email', 150)->nullable();
            $table->string('akta_notaris', 100)->nullable();
            $table->string('npwp', 30)->nullable();
            $table->string('kemenkumham_no', 100)->nullable();
            $table->date('registered_at')->nullable();
            $table->date('valid_until')->nullable();
            $table->integer('client_count')->default(0);
            $table->string('status', 30)->default('aktif');
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index('status');
            $table->index('type');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('lks');
    }
};
