<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('property_reviews', function (Blueprint $table) {
            $table->unsignedTinyInteger('overall_experience')
                ->nullable()
                ->after('reviewer_email');

            $table->unsignedTinyInteger('care_of_property')
                ->nullable()
                ->change();

            $table->boolean('property_left_satisfactorily')
                ->nullable()
                ->after('care_of_property');

            $table->boolean('private_concern')
                ->nullable()
                ->after('comments');

            $table->text('private_concern_details')
                ->nullable()
                ->after('private_concern');
        });
    }

    public function down(): void
    {
        Schema::table('property_reviews', function (Blueprint $table) {
            $table->dropColumn([
                'overall_experience',
                'property_left_satisfactorily',
                'private_concern',
                'private_concern_details',
            ]);

            $table->unsignedTinyInteger('care_of_property')
                ->nullable(false)
                ->change();
        });
    }
};
