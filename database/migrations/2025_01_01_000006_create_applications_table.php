<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('applications', function (Blueprint $table) {
            $table->id();
            $table->string('code', 30)->unique();
            $table->foreignId('service_type_id')->constrained('service_types');
            $table->foreignId('applicant_user_id')->constrained('users');
            $table->foreignId('current_handler_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('beneficiary_name', 150);
            $table->string('beneficiary_nik', 16)->nullable();
            $table->string('beneficiary_relation', 50)->default('diri_sendiri');
            $table->text('purpose')->nullable();
            $table->string('status', 30)->default('submitted');
            $table->string('current_step', 50)->default('verifikasi_loket');
            $table->string('priority', 20)->default('normal');
            $table->timestamp('submitted_at')->nullable();
            $table->timestamp('sla_due_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->text('rejection_reason')->nullable();
            $table->json('meta')->nullable();
            $table->timestamps();

            $table->index('status');
            $table->index('current_step');
            $table->index('sla_due_at');
            $table->index('submitted_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('applications');
    }
};
