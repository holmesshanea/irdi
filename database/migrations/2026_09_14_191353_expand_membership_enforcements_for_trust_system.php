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
        Schema::table('membership_enforcements', function (Blueprint $table) {
            $table->string('status')
                ->default('active')
                ->after('action');

            $table->text('details')
                ->nullable()
                ->after('reason');

            $table->timestamp('starts_at')
                ->nullable()
                ->after('details');

            $table->timestamp('eligible_for_review_at')
                ->nullable()
                ->after('starts_at');

            $table->text('restoration_requirements')
                ->nullable()
                ->after('eligible_for_review_at');

            $table->text('member_response')
                ->nullable()
                ->after('restoration_requirements');

            $table->timestamp('member_responded_at')
                ->nullable()
                ->after('member_response');

            $table->timestamp('resolved_at')
                ->nullable()
                ->after('member_responded_at');

            $table->foreignId('resolved_by')
                ->nullable()
                ->after('resolved_at')
                ->constrained('users')
                ->nullOnDelete();

            $table->timestamp('reinstatement_eligible_at')
                ->nullable()
                ->after('resolved_by');

            $table->boolean('permanently_ineligible')
                ->default(false)
                ->after('reinstatement_eligible_at');

            $table->index(['user_id', 'action', 'status']);
            $table->index(['user_id', 'eligible_for_review_at']);
        });

        Schema::table('membership_enforcements', function (Blueprint $table) {
            $table->foreignId('admin_id')
                ->nullable()
                ->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('membership_enforcements', function (Blueprint $table) {
            $table->dropIndex([
                'user_id',
                'action',
                'status',
            ]);

            $table->dropIndex([
                'user_id',
                'eligible_for_review_at',
            ]);

            $table->dropConstrainedForeignId('resolved_by');

            $table->dropColumn([
                'status',
                'details',
                'starts_at',
                'eligible_for_review_at',
                'restoration_requirements',
                'member_response',
                'member_responded_at',
                'resolved_at',
                'reinstatement_eligible_at',
                'permanently_ineligible',
            ]);
        });

        Schema::table('membership_enforcements', function (Blueprint $table) {
            $table->foreignId('admin_id')
                ->nullable(false)
                ->change();
        });
    }
};
