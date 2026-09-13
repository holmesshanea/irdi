<?php

use App\Models\MemberProfile;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

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
        ->assertSeeText('Country statistics represent active IRDI membership in aggregate');

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

    $component = \Livewire\Livewire::test('pages::statistics');
    $growth = $component->get('membershipGrowthData');

    expect($growth)->toHaveCount(3)
        ->and($growth[0]['members'])->toBe(1)
        ->and($growth[1]['members'])->toBe(1)
        ->and($growth[2]['members'])->toBe(2);
});
