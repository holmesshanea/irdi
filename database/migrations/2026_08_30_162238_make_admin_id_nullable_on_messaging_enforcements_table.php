<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('messaging_enforcements', function (Blueprint $table) {
            $table->foreignId('admin_id')
                ->nullable()
                ->change();
        });
    }

    public function down(): void
    {
        Schema::table('messaging_enforcements', function (Blueprint $table) {
            $table->foreignId('admin_id')
                ->nullable(false)
                ->change();
        });
    }
};
