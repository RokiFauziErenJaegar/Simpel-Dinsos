<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('queue_tickets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('application_id')->nullable()->constrained('applications')->nullOnDelete();
            $table->string('ticket_number', 10);
            $table->date('ticket_date');
            $table->string('counter', 20)->nullable();
            $table->string('priority', 20)->default('normal');
            $table->string('status', 20)->default('waiting');
            $table->string('walk_in_name', 100)->nullable();
            $table->string('walk_in_phone', 20)->nullable();
            $table->foreignId('called_by_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('called_at')->nullable();
            $table->timestamp('served_at')->nullable();
            $table->timestamp('done_at')->nullable();
            $table->timestamps();

            $table->unique(['ticket_number', 'ticket_date']);
            $table->index(['ticket_date', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('queue_tickets');
    }
};
