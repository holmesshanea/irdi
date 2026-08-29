<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('message_reports', function (Blueprint $table) {
            $table->id();

            $table->foreignId('message_id')
                ->constrained('messages')
                ->cascadeOnDelete();

            $table->foreignId('reporter_id')
                ->constrained('users')
                ->cascadeOnDelete();

            $table->string('reason', 100);

            $table->text('details')
                ->nullable();

            $table->string('status', 30)
                ->default('pending');

            $table->timestamps();

            $table->unique([
                'message_id',
                'reporter_id',
            ]);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('message_reports');
    }
};
