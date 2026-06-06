<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('service_types', function (Blueprint $table) {
            $table->id();
            $table->string('code', 10)->unique();
            $table->string('slug', 100)->unique();
            $table->string('name', 200);
            $table->text('description');
            $table->json('requirements');
            $table->json('procedure');
            $table->string('output', 200);
            $table->string('bidang', 50);
            $table->integer('sla_minutes')->default(1440);
            $table->string('sla_display', 50);
            $table->string('icon', 30)->default('document');
            $table->string('color', 20)->default('primary');
            $table->boolean('is_active')->default(true);
            $table->boolean('is_featured')->default(false);
            $table->integer('order_no')->default(0);
            $table->timestamps();

            $table->index('is_active');
            $table->index('bidang');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('service_types');
    }
};
