<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('property_reviews', function (Blueprint $table) {
            $table->timestamp('approved_at')->nullable();
        });

        // Preserve previously published reviews; do not expose previously hidden ones.
        DB::table('property_reviews')
            ->whereNull('hidden_at')
            ->update(['approved_at' => DB::raw('created_at')]);
    }

    public function down(): void
    {
        Schema::table('property_reviews', function (Blueprint $table) {
            $table->dropColumn('approved_at');
        });
    }
};
