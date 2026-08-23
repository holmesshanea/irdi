<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('member_profiles', function (Blueprint $table) {
            $table->index('directory_visible');
            $table->index('profile_name');
            $table->index('username');
            $table->index('profile_type');
            $table->index('country');
            $table->index('state_province');
            $table->index('city');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('member_profiles', function (Blueprint $table) {
            $table->dropIndex(['directory_visible']);
            $table->dropIndex(['profile_name']);
            $table->dropIndex(['username']);
            $table->dropIndex(['profile_type']);
            $table->dropIndex(['country']);
            $table->dropIndex(['state_province']);
            $table->dropIndex(['city']);
        });
    }
};
