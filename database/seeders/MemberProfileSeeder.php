<?php

namespace Database\Seeders;

use App\Models\MemberProfile;
use App\Models\User;
use Illuminate\Database\Seeder;

class MemberProfileSeeder extends Seeder
{
    public function run(): void
    {
        $user = User::where('email', 'holmesshanea@yahoo.com')->firstOrFail();

        MemberProfile::updateOrCreate(
            ['user_id' => $user->id],
            [
                'username' => 'shaneholmes',
                'profile_name' => 'Shane Holmes',
                'city' => 'Saranac Lake',
                'state_province' => 'New York',
                'country' => 'United States',
                'country_code' => 'US',
                'bio' => 'IRDI Member and responsible metal detectorist.',
                'directory_visible' => true,
            ]
        );
    }
}
