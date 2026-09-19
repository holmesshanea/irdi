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
        Schema::table('ban_identifiers', function (Blueprint $table) {
            $table->dropUnique('ban_identifiers_type_value_unique');
        });
    }

    public function down(): void
    {
        Schema::table('ban_identifiers', function (Blueprint $table) {
            $table->unique(
                ['type', 'value'],
                'ban_identifiers_type_value_unique'
            );
        });
    }
};
