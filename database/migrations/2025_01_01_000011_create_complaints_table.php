<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('complaints', function (Blueprint $table) {
            $table->id();
            $table->string('code', 30)->unique();
            $table->string('channel', 30)->default('web');
            $table->string('reporter_name', 150)->nullable();
            $table->string('reporter_contact', 100)->nullable();
            $table->boolean('is_anonymous')->default(false);
            $table->string('subject', 200);
            $table->text('content');
            $table->string('status', 30)->default('open');
            $table->foreignId('assigned_to_id')->nullable()->constrained('users')->nullOnDelete();
            $table->text('response')->nullable();
            $table->timestamp('responded_at')->nullable();
            $table->timestamp('resolved_at')->nullable();
            $table->timestamps();

            $table->index('status');
            $table->index('channel');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('complaints');
    }
};
