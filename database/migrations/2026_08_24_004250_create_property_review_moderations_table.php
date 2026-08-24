<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('property_review_moderations', function (Blueprint $table) {
            $table->id();

            $table->foreignId('property_review_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->foreignId('user_id')
                ->nullable()
                ->constrained()
                ->nullOnDelete();

            $table->string('action', 20);

            $table->text('note')->nullable();

            $table->timestamps();

            $table->index(['property_review_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('property_review_moderations');
    }
};
