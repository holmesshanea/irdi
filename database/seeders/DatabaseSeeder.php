<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use RuntimeException;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        if (app()->environment('production')) {
            throw new RuntimeException('Demo database seeding is disabled in production.');
        }

        $password = config('demo.admin_password');
        if (! is_string($password) || strlen($password) < 12) {
            throw new RuntimeException('Set IRDI_DEMO_ADMIN_PASSWORD in your local .env (minimum 12 characters) before seeding.');
        }

        User::create([
            'name' => 'Shane Holmes',
            'email' => 'holmesshanea@yahoo.com',
            'email_verified_at' => now(),
            'is_admin' => true,

            'ethics_agreed_at' => now(),
            'best_practices_agreed_at' => now(),
            'membership_status' => 'active',
            'member_since' => now(),

            'password' => Hash::make($password),
        ]);

        $this->call([FakeMembersSeeder::class, PropertyOwnerFeedbackSeeder::class]);
    }
}
