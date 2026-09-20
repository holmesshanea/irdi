<?php

use App\Models\MemberProfile;
use App\Models\User;
use Livewire\Livewire;

test('member profile website rejects non http and https schemes', function () {
    $member = User::factory()->create([
        'membership_status' => 'active',
    ]);

    $profile = MemberProfile::factory()->create([
        'user_id' => $member->id,
        'profile_name' => 'Security Test Detectorist',
        'website' => null,
    ]);

    $this->actingAs($member);

    Livewire::test('member-profiles.edit', ['profile' => $profile])
        ->set('profileName', 'Security Test Detectorist')
        ->set('city', 'Saranac Lake')
        ->set('stateProvince', 'New York')
        ->set('countryCode', 'US')
        ->set('website', 'ftp://example.com/profile')
        ->call('save')
        ->assertHasErrors(['website']);

    expect($profile->fresh()->website)->toBeNull();
});

test('member profile website accepts https scheme', function () {
    $member = User::factory()->create([
        'membership_status' => 'active',
    ]);

    $profile = MemberProfile::factory()->create([
        'user_id' => $member->id,
        'profile_name' => 'Security Test Detectorist',
        'website' => null,
    ]);

    $this->actingAs($member);

    Livewire::test('member-profiles.edit', ['profile' => $profile])
        ->set('profileName', 'Security Test Detectorist')
        ->set('city', 'Saranac Lake')
        ->set('stateProvince', 'New York')
        ->set('countryCode', 'US')
        ->set('website', 'https://example.com/profile')
        ->call('save')
        ->assertHasNoErrors();

    expect($profile->fresh()->website)
        ->toBe('https://example.com/profile');
});
