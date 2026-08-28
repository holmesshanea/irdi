<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class ActivateMemberSeeder extends Seeder
{
    public function run(): void
    {
        $user = User::where('email', 'holmesshanea@yahoo.com')->firstOrFail();

        $user->update([
            'ethics_agreed_at' => $user->ethics_agreed_at ?? now(),
            'best_practices_agreed_at' => $user->best_practices_agreed_at ?? now(),
            'membership_status' => 'active',
            'member_since' => $user->member_since ?? now(),
        ]);
    }
}
