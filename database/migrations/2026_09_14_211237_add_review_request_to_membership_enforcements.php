<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('membership_enforcements', function (Blueprint $table) {
            $table->timestamp('review_requested_at')
                ->nullable()
                ->after('member_responded_at');

            $table->index([
                'user_id',
                'action',
                'status',
                'review_requested_at',
            ]);
        });
    }

    public function down(): void
    {
        Schema::table('membership_enforcements', function (Blueprint $table) {
            $table->dropIndex([
                'user_id',
                'action',
                'status',
                'review_requested_at',
            ]);

            $table->dropColumn('review_requested_at');
        });
    }
};
