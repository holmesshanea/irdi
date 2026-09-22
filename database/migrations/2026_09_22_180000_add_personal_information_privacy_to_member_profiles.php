<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('member_profiles', function (Blueprint $table) {
            $table->boolean('personal_information_hidden')->default(false);
            $table->string('encrypted_profile_image')->nullable();
        });
    }

    public function down(): void
    {
        // Restore archived photos before rolling back this migration.
        if (DB::table('member_profiles')->whereNotNull('encrypted_profile_image')->exists()) {
            throw new RuntimeException('Restore encrypted profile images before rolling back profile privacy.');
        }

        Schema::table('member_profiles', function (Blueprint $table) {
            $table->dropColumn(['personal_information_hidden', 'encrypted_profile_image']);
        });
    }
};
