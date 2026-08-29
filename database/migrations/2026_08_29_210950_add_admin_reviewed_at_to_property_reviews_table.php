<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('property_reviews', function (Blueprint $table) {
            $table->timestamp('admin_reviewed_at')
                ->nullable()
                ->after('hidden_at');
        });
    }

    public function down(): void
    {
        Schema::table('property_reviews', function (Blueprint $table) {
            $table->dropColumn('admin_reviewed_at');
        });
    }
};
