<?php

namespace Database\Seeders;

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
        // Use create() instead of factory()->create()
        \App\Models\User::create([
            'name' => 'Shane Holmes',
            'email' => 'holmesshanea@yahoo.com',
            'email_verified_at' => now(),
            'is_admin' => true,
            'password' => \Illuminate\Support\Facades\Hash::make('Colvin1Blake2!'),
        ]);
    }
}
