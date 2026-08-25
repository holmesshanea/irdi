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
        Schema::create('member_profiles', function (Blueprint $table) {
            $table->id();

            $table->foreignId('user_id')
                ->unique()
                ->constrained()
                ->cascadeOnDelete();

            $table->string('username')->unique();

            $table->string('profile_name');

            $table->string('city')->nullable();
            $table->string('state_province')->nullable();
            $table->string('country')->nullable();

            $table->text('bio')->nullable();

            $table->string('website')->nullable();

            $table->string('profile_image')->nullable();

            $table->boolean('directory_visible')->default(false);

            /*
            |--------------------------------------------------------------------------
            | Directory Indexes
            |--------------------------------------------------------------------------
            */

            $table->index('directory_visible');
            $table->index('profile_name');
            $table->index('country');
            $table->index('state_province');
            $table->index('city');

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('member_profiles');
    }
};
