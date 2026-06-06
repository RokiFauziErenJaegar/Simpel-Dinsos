<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // beneficiary_nik di applications: perbesar untuk muat ciphertext
        Schema::table('applications', function (Blueprint $table) {
            $table->text('beneficiary_nik')->nullable()->change();
        });

        // family_card_no di ppks_profiles: perbesar untuk muat ciphertext
        Schema::table('ppks_profiles', function (Blueprint $table) {
            $table->text('family_card_no')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('applications', function (Blueprint $table) {
            $table->string('beneficiary_nik', 16)->nullable()->change();
        });
        Schema::table('ppks_profiles', function (Blueprint $table) {
            $table->string('family_card_no', 20)->nullable()->change();
        });
    }
};
