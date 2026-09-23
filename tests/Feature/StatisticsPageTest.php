<?php

use App\Models\MemberProfile;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

test('statistics page is public', function () {
    $this->get(route('statistics'))
        ->assertOk()
        ->assertSeeText('IRDI by the Numbers');
});

test('statistics page counts active members countries charter members and public directory members', function () {
    $publicCharterMember = User::factory()->create([
        'membership_status' => 'active',
        'is_charter_member' => true,
    ]);

    MemberProfile::factory()->for($publicCharterMember)->create([
        'country' => 'United States',
        'country_code' => 'US',
        'directory_visible' => true,
    ]);

    $privateActiveMember = User::factory()->create([
        'membership_status' => 'active',
        'is_charter_member' => false,
    ]);

    MemberProfile::factory()->for($privateActiveMember)->create([
        'country' => 'Canada',
        'country_code' => 'CA',
        'directory_visible' => false,
    ]);

    $suspendedMember = User::factory()->create([
        'membership_status' => 'suspended',
        'is_charter_member' => true,
    ]);

    MemberProfile::factory()->for($suspendedMember)->create([
        'country' => 'United Kingdom',
        'country_code' => 'GB',
        'directory_visible' => true,
    ]);

    $response = $this->get(route('statistics'));

    $response
        ->assertOk()
        ->assertSeeText('Active Members')
        ->assertSeeText('Countries Represented')
        ->assertSeeText('Charter Members')
        ->assertSeeText('Public Directory Members')
        ->assertSeeText('Members Around the World')
        ->assertSeeText('United States')
        ->assertSeeText('Canada')
        ->assertSeeText('50.0%', false)
        ->assertDontSeeText('United Kingdom')
        ->assertSeeText(__('privacy.stats'));

    expect(User::query()->where('membership_status', 'active')->count())->toBe(2)
        ->and(MemberProfile::query()
            ->whereHas('user', fn ($query) => $query->where('membership_status', 'active'))
            ->distinct()
            ->count('country_code'))->toBe(2)
        ->and(User::query()
            ->where('membership_status', 'active')
            ->where('is_charter_member', true)
            ->count())->toBe(1)
        ->and(MemberProfile::query()->publicDirectory()->count())->toBe(1);
});

test('membership growth preserves historical joins regardless of current membership status', function () {
    User::factory()->create([
        'membership_status' => 'active',
        'member_since' => now()->copy()->subMonths(2)->startOfMonth()->addDay()->toDateString(),
    ]);

    User::factory()->create([
        'membership_status' => 'suspended',
        'member_since' => now()->copy()->startOfMonth()->addDay()->toDateString(),
    ]);

    $response = $this->get(route('statistics'));

    $response
        ->assertOk()
        ->assertSeeText('Membership Growth')
        ->assertSeeText('Current enforcement status does not rewrite when a member originally joined IRDI');

    $component = Livewire::test('pages::statistics');
    $growth = $component->get('membershipGrowthData');

    expect($growth)->toHaveCount(3)
        ->and($growth[0]['members'])->toBe(1)
        ->and($growth[1]['members'])->toBe(1)
        ->and($growth[2]['members'])->toBe(2);
});

test('country chart groups smaller countries into other while keeping the complete ranking', function () {
    $countries = [
        ['United States', 'US', 8],
        ['Canada', 'CA', 7],
        ['United Kingdom', 'GB', 6],
        ['Australia', 'AU', 5],
        ['Germany', 'DE', 4],
        ['France', 'FR', 3],
        ['Italy', 'IT', 2],
        ['Netherlands', 'NL', 1],
        ['Spain', 'ES', 1],
    ];

    foreach ($countries as [$country, $countryCode, $memberCount]) {
        for ($i = 0; $i < $memberCount; $i++) {
            $user = User::factory()->create(['membership_status' => 'active']);

            MemberProfile::factory()->for($user)->create([
                'country' => $country,
                'country_code' => $countryCode,
                'directory_visible' => false,
            ]);
        }
    }

    $component = Livewire::test('pages::statistics');
    $chartData = $component->get('chartCountryData');
    $countryData = $component->get('countryData');

    expect($chartData)->toHaveCount(8)
        ->and($chartData[7]['id'])->toBe('other')
        ->and($chartData[7]['label'])->toBe('Other')
        ->and($chartData[7]['value'])->toBe(2)
        ->and($countryData)->toHaveCount(9)
        ->and(collect($countryData)->pluck('label'))->toContain('Netherlands', 'Spain');
});
