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

        $indexName = 'membership_enforcements_review_request_idx';

        if (! collect(Schema::getIndexes('membership_enforcements'))
            ->contains(fn (array $index): bool => $index['name'] === $indexName)) {
            Schema::table('membership_enforcements', function (Blueprint $table) use ($indexName) {
                $table->index([
                    'user_id',
                    'action',
                    'status',
                    'review_requested_at',
                ], $indexName);
            });
        }
    }

    public function down(): void
    {
        $indexName = 'membership_enforcements_review_request_idx';

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
