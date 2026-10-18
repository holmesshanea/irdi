<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('founding_supporters', function (Blueprint $table) {
            $table->id();
            $table->string('display_name', 100);
            $table->string('tier', 20);
            $table->boolean('is_public')->default(false);
            $table->string('kickstarter_backer_id')->nullable()->unique();
            $table->timestamps();
            $table->index(['tier', 'is_public']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('founding_supporters');
    }
};
