<?php

namespace Database\Seeders;

use App\Models\MemberProfile;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $user = User::create([
            'name' => 'Shane Holmes',
            'email' => 'holmesshanea@yahoo.com',
            'email_verified_at' => now(),
            'is_admin' => true,
            'membership_status' => 'active',
            'member_since' => now(),
            'ethics_agreed_at' => now(),
            'best_practices_agreed_at' => now(),
            'password' => Hash::make('Colvin1Blake2!'),
        ]);

        MemberProfile::create([
            'user_id' => $user->id,
            'username' => 'shaneholmes',
            'profile_name' => 'Shane Holmes',
            'city' => 'Saranac Lake',
            'state_province' => 'New York',
            'country' => 'United States',
            'bio' => 'IRDI Member and responsible metal detectorist.',
            'directory_visible' => true,
        ]);
    }
}
