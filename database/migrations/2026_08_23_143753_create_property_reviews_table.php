<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('property_reviews', function (Blueprint $table) {
            $table->id();

            $table->foreignId('member_profile_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->foreignId('property_review_invitation_id')
                ->unique()
                ->constrained()
                ->cascadeOnDelete();

            $table->string('reviewer_email');

            $table->unsignedTinyInteger('respect_for_property');

            $table->unsignedTinyInteger('communication_courtesy');

            $table->unsignedTinyInteger('care_of_property');

            $table->boolean('would_allow_return');

            $table->text('comments')->nullable();

            $table->timestamp('hidden_at')->nullable();

            $table->text('moderation_note')->nullable();

            $table->timestamps();

            $table->index('reviewer_email');
            $table->index(['member_profile_id', 'reviewer_email']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('property_reviews');
    }
};
