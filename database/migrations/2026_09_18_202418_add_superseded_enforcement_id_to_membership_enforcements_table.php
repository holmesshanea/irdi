<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('membership_enforcements', function (Blueprint $table) {
            $table->foreignId('superseded_enforcement_id')
                ->nullable()
                ->after('admin_id')
                ->constrained('membership_enforcements')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('membership_enforcements', function (Blueprint $table) {
            $table->dropConstrainedForeignId('superseded_enforcement_id');
        });
    }
};
