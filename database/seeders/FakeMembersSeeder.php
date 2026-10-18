<?php

namespace Database\Seeders;

use App\Models\MemberProfile;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use RuntimeException;

class FakeMembersSeeder extends Seeder
{
    private const COUNT = 158;

    public function run(): void
    {
        if (App::environment('production')) {
            throw new RuntimeException('Fake member seeding is disabled in production.');
        }

        $password = Hash::make(bin2hex(random_bytes(32)));
        $locations = [
            ['Saranac Lake', 'New York', 'United States', 'US'],
            ['Albany', 'New York', 'United States', 'US'],
            ['Burlington', 'Vermont', 'United States', 'US'],
            ['Boston', 'Massachusetts', 'United States', 'US'],
            ['Philadelphia', 'Pennsylvania', 'United States', 'US'],
            ['Toronto', 'Ontario', 'Canada', 'CA'],
            ['Ottawa', 'Ontario', 'Canada', 'CA'],
            ['Montreal', 'Quebec', 'Canada', 'CA'],
            ['London', 'England', 'United Kingdom', 'GB'],
            ['Manchester', 'England', 'United Kingdom', 'GB'],
            ['Sydney', 'New South Wales', 'Australia', 'AU'],
            ['Melbourne', 'Victoria', 'Australia', 'AU'],
        ];

        for ($start = 1; $start <= self::COUNT; $start += 250) {
            DB::transaction(function () use ($start, $password, $locations): void {
                $end = min($start + 249, self::COUNT);

                for ($i = $start; $i <= $end; $i++) {
                    $email = sprintf('irdi-test-%05d@example.invalid', $i);
                    $username = sprintf('irdi_test_%05d', $i);
                    $user = User::query()->where('email', $email)->first();

                    if ($user === null) {
                        $name = fake()->name();
                        $joined = now()->subDays(random_int(0, 1095));
                        $user = User::query()->create([
                            'name' => $name,
                            'email' => $email,
                            'password' => $password,
                            'membership_status' => 'active',
                            'member_since' => $joined->toDateString(),
                            'ethics_agreed_at' => $joined,
                            'best_practices_agreed_at' => $joined,
                        ]);
                        $user->forceFill(['email_verified_at' => $joined])->save();
                    }

                    if ($user->memberProfile()->exists()) {
                        continue;
                    }

                    $location = $locations[($i - 1) % count($locations)];
                    MemberProfile::query()->create([
                        'user_id' => $user->id,
                        'username' => $username,
                        'profile_name' => $user->name,
                        'city' => $location[0],
                        'state_province' => $location[1],
                        'country' => $location[2],
                        'country_code' => $location[3],
                        'bio' => fake()->sentence(14),
                        'directory_visible' => $i % 10 !== 0,
                        'allow_member_messages' => false,
                    ]);
                }
            });

            $this->command->info('Processed '.min($start + 249, self::COUNT).' / '.self::COUNT.' fake members.');
        }
    }
}
