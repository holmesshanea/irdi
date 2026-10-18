<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('founding_supporters', function (Blueprint $table) {
            $table->timestamp('recognition_consented_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('founding_supporters', function (Blueprint $table) {
            $table->dropColumn('recognition_consented_at');
        });
    }
};
