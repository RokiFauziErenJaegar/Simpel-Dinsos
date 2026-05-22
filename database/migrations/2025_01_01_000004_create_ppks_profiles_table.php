<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('ppks_profiles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->date('birth_date')->nullable();
            $table->string('birth_place', 100)->nullable();
            $table->string('gender', 10)->nullable();
            $table->string('occupation', 100)->nullable();
            $table->string('family_card_no', 20)->nullable();
            $table->tinyInteger('dtsen_desil')->nullable();
            $table->date('dtsen_verified_at')->nullable();
            $table->string('photo_path')->nullable();
            $table->string('house_photo_path')->nullable();
            $table->json('categories')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ppks_profiles');
    }
};
