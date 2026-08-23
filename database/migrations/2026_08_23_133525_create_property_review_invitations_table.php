<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('property_review_invitations', function (Blueprint $table) {
            $table->id();

            $table->foreignId('member_profile_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->string('token', 64)->unique();

            $table->string('reviewer_email')->nullable();

            $table->string('verification_code_hash')->nullable();

            $table->timestamp('verification_expires_at')->nullable();

            $table->timestamp('email_verified_at')->nullable();

            $table->timestamp('expires_at');

            $table->timestamp('used_at')->nullable();

            $table->timestamps();

            $table->index(['member_profile_id', 'expires_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('property_review_invitations');
    }
};
