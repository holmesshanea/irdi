<?php

namespace Database\Seeders;

use App\Models\MemberProfile;
use Illuminate\Database\Seeder;

class MemberProfileSeeder extends Seeder
{
    public function run(): void
    {
        MemberProfile::factory()
            ->count(75)
            ->create();
    }
}
