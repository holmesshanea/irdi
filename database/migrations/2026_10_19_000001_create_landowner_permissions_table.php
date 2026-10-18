<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('landowner_permissions', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('member_profile_id')->constrained()->cascadeOnDelete();
            $table->string('landowner_email');
            $table->string('property_label');
            $table->text('proposed_activity');
            $table->text('requested_conditions')->nullable();
            $table->date('starts_on');
            $table->date('ends_on');
            $table->string('status')->default('pending');
            $table->string('access_token_hash', 64)->unique();
            $table->timestamp('responded_at')->nullable();
            $table->timestamp('withdrawn_at')->nullable();
            $table->text('landowner_conditions')->nullable();
            $table->timestamp('last_reminded_at')->nullable();
            $table->timestamps();
            $table->index(['member_profile_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('landowner_permissions');
    }
};
