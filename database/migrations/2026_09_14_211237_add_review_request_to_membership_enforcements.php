<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('membership_enforcements', 'review_requested_at')) {
            Schema::table('membership_enforcements', function (Blueprint $table) {
                $table->timestamp('review_requested_at')
                    ->nullable()
                    ->after('member_responded_at');
            });
        }

        $indexName = 'membership_enforcements_user_id_action_status_review_requested_at_index';

        if (! collect(Schema::getIndexes('membership_enforcements'))
            ->contains(fn (array $index): bool => $index['name'] === $indexName)) {
            Schema::table('membership_enforcements', function (Blueprint $table) {
                $table->index([
                    'user_id',
                    'action',
                    'status',
                    'review_requested_at',
                ]);
            });
        }
    }

    public function down(): void
    {
        $indexName = 'membership_enforcements_user_id_action_status_review_requested_at_index';

        if (collect(Schema::getIndexes('membership_enforcements'))
            ->contains(fn (array $index): bool => $index['name'] === $indexName)) {
            Schema::table('membership_enforcements', function (Blueprint $table) use ($indexName) {
                $table->dropIndex($indexName);
            });
        }

        if (Schema::hasColumn('membership_enforcements', 'review_requested_at')) {
            Schema::table('membership_enforcements', function (Blueprint $table) {
                $table->dropColumn('review_requested_at');
            });
        }
    }
};
