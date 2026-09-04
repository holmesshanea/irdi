<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('member_profiles', function (Blueprint $table) {
            $table->string('country_code', 2)
                ->nullable()
                ->after('country');
        });

        // Backfill existing profiles.
        DB::table('member_profiles')
            ->where('country', 'United States')
            ->whereNull('country_code')
            ->update([
                'country_code' => 'US',
            ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('member_profiles', function (Blueprint $table) {
            $table->dropColumn('country_code');
        });
    }
};
