<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('membership_enforcements', function (Blueprint $table) {
            $table->unsignedInteger('superseded_probation_remaining_days')
                ->nullable()
                ->after('superseded_enforcement_id');
        });
    }

    public function down(): void
    {
        Schema::table('membership_enforcements', function (Blueprint $table) {
            $table->dropColumn('superseded_probation_remaining_days');
        });
    }
};
