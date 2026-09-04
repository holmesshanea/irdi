<?php

namespace Database\Factories;

use App\Models\MemberProfile;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<MemberProfile>
 */
class MemberProfileFactory extends Factory
{
    protected $model = MemberProfile::class;

    public function definition(): array
    {
        $profileName = fake()->name();

        /** @var array{city:string,state_province:string,country:string,country_code:string} $location */
        $location = fake()->randomElement([
            [
                'city' => 'Saranac Lake',
                'state_province' => 'New York',
                'country' => 'United States',
                'country_code' => 'US',
            ],
            [
                'city' => 'Albany',
                'state_province' => 'New York',
                'country' => 'United States',
                'country_code' => 'US',
            ],
            [
                'city' => 'Burlington',
                'state_province' => 'Vermont',
                'country' => 'United States',
                'country_code' => 'US',
            ],
            [
                'city' => 'Boston',
                'state_province' => 'Massachusetts',
                'country' => 'United States',
                'country_code' => 'US',
            ],
            [
                'city' => 'Philadelphia',
                'state_province' => 'Pennsylvania',
                'country' => 'United States',
                'country_code' => 'US',
            ],
            [
                'city' => 'Toronto',
                'state_province' => 'Ontario',
                'country' => 'Canada',
                'country_code' => 'CA',
            ],
            [
                'city' => 'Ottawa',
                'state_province' => 'Ontario',
                'country' => 'Canada',
                'country_code' => 'CA',
            ],
            [
                'city' => 'Montreal',
                'state_province' => 'Quebec',
                'country' => 'Canada',
                'country_code' => 'CA',
            ],
            [
                'city' => 'London',
                'state_province' => 'England',
                'country' => 'United Kingdom',
                'country_code' => 'GB',
            ],
            [
                'city' => 'Manchester',
                'state_province' => 'England',
                'country' => 'United Kingdom',
                'country_code' => 'GB',
            ],
            [
                'city' => 'Sydney',
                'state_province' => 'New South Wales',
                'country' => 'Australia',
                'country_code' => 'AU',
            ],
            [
                'city' => 'Melbourne',
                'state_province' => 'Victoria',
                'country' => 'Australia',
                'country_code' => 'AU',
            ],
        ]);

        return [
            'user_id' => User::factory(),

            'username' => Str::lower(
                Str::slug($profileName).'-'.fake()->unique()->numberBetween(1000, 999999)
            ),

            'profile_name' => $profileName,

            'city' => $location['city'],
            'state_province' => $location['state_province'],
            'country' => $location['country'],
            'country_code' => $location['country_code'],

            'bio' => fake()->paragraph(),

            'website' => fake()->optional()->url(),

            'profile_image' => null,

            'directory_visible' => true,
        ];
    }
}
