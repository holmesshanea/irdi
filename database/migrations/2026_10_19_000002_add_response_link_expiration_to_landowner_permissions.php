<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('landowner_permissions', function (Blueprint $table): void {
            $table->timestamp('response_link_expires_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('landowner_permissions', function (Blueprint $table): void {
            $table->dropColumn('response_link_expires_at');
        });
    }
};
