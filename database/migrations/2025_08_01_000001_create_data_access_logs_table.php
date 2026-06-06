<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('data_access_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('actor_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('actor_role', 30)->nullable();
            $table->string('action', 30); // view, list, export, download
            $table->string('subject_type', 50); // model class atau jenis (mis. ppks_profile, application_document)
            $table->unsignedBigInteger('subject_id')->nullable();
            $table->string('subject_owner_nik', 16)->nullable();
            $table->string('reason', 200)->nullable(); // alasan akses (opsional)
            $table->string('ip', 45)->nullable();
            $table->string('user_agent', 500)->nullable();
            $table->string('route', 150)->nullable();
            $table->timestamps();

            $table->index(['actor_user_id', 'created_at']);
            $table->index(['subject_type', 'subject_id']);
            $table->index('subject_owner_nik');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('data_access_logs');
    }
};
