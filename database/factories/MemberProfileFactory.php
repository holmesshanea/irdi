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

        /** @var array{city:string,state_province:string,country:string} $location */
        $location = fake()->randomElement([
            [
                'city' => 'Saranac Lake',
                'state_province' => 'New York',
                'country' => 'United States',
            ],
            [
                'city' => 'Albany',
                'state_province' => 'New York',
                'country' => 'United States',
            ],
            [
                'city' => 'Burlington',
                'state_province' => 'Vermont',
                'country' => 'United States',
            ],
            [
                'city' => 'Boston',
                'state_province' => 'Massachusetts',
                'country' => 'United States',
            ],
            [
                'city' => 'Philadelphia',
                'state_province' => 'Pennsylvania',
                'country' => 'United States',
            ],
            [
                'city' => 'Toronto',
                'state_province' => 'Ontario',
                'country' => 'Canada',
            ],
            [
                'city' => 'Ottawa',
                'state_province' => 'Ontario',
                'country' => 'Canada',
            ],
            [
                'city' => 'Montreal',
                'state_province' => 'Quebec',
                'country' => 'Canada',
            ],
            [
                'city' => 'London',
                'state_province' => 'England',
                'country' => 'United Kingdom',
            ],
            [
                'city' => 'Manchester',
                'state_province' => 'England',
                'country' => 'United Kingdom',
            ],
            [
                'city' => 'Sydney',
                'state_province' => 'New South Wales',
                'country' => 'Australia',
            ],
            [
                'city' => 'Melbourne',
                'state_province' => 'Victoria',
                'country' => 'Australia',
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

            'bio' => fake()->paragraph(),

            'website' => fake()->optional()->url(),

            'profile_image' => null,

            'directory_visible' => true,
        ];
    }
}
