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

test('member cannot update a profile after ownership changes', function () {
    $originalOwner = User::factory()->create([
        'membership_status' => 'active',
    ]);

    $newOwner = User::factory()->create([
        'membership_status' => 'active',
    ]);

    $profile = MemberProfile::factory()->create([
        'user_id' => $originalOwner->id,
        'profile_name' => 'Original Profile Name',
        'city' => 'Saranac Lake',
        'state_province' => 'New York',
        'country' => 'United States',
        'country_code' => 'US',
    ]);

    $this->actingAs($originalOwner);

    $component = Livewire::test('member-profiles.edit', [
        'profile' => $profile,
    ]);

    $profile->update([
        'user_id' => $newOwner->id,
    ]);

    $component
        ->set('profileName', 'Unauthorized Profile Change')
        ->set('city', 'Lake Placid')
        ->set('stateProvince', 'New York')
        ->set('countryCode', 'US')
        ->call('save')
        ->assertForbidden();

    expect($profile->fresh())
        ->profile_name->toBe('Original Profile Name')
        ->city->toBe('Saranac Lake')
        ->user_id->toBe($newOwner->id);
});

test('member cannot remove a profile image after ownership changes', function () {
    Storage::fake('public');

    $originalOwner = User::factory()->create([
        'membership_status' => 'active',
    ]);

    $newOwner = User::factory()->create([
        'membership_status' => 'active',
    ]);

    Storage::disk('public')->put(
        'profile-images/profile.jpg',
        'fake-image-contents'
    );

    $profile = MemberProfile::factory()->create([
        'user_id' => $originalOwner->id,
        'profile_image' => 'profile-images/profile.jpg',
    ]);

    $this->actingAs($originalOwner);

    $component = Livewire::test('member-profiles.edit', [
        'profile' => $profile,
    ]);

    $profile->update([
        'user_id' => $newOwner->id,
    ]);

    $component
        ->call('removeProfileImage')
        ->assertForbidden();

    expect($profile->fresh())
        ->profile_image->toBe('profile-images/profile.jpg')
        ->user_id->toBe($newOwner->id);

    Storage::disk('public')
        ->assertExists('profile-images/profile.jpg');
});
