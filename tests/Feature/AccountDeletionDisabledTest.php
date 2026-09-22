<?php

use App\Models\MemberProfile;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;

test('account deletion page is unavailable to an authenticated owner', function () {
    $user = User::factory()->create(['membership_status' => 'active']);
    $this->actingAs($user)->get(route('account.delete'))->assertForbidden();
    $this->assertDatabaseHas('users', ['id' => $user->id]);
});

test('old livewire deletion action cannot delete accounts profiles or images even with the correct password', function () {
    Storage::fake('public');
    config(['filesystems.profile_images_disk' => 'public']);
    Storage::disk('public')->put('profile-images/retained.jpg', 'image');
    $user = User::factory()->create([
        'membership_status' => 'active',
        'password' => Hash::make('correct-password'),
    ]);
    $profile = MemberProfile::factory()->create([
        'user_id' => $user->id,
        'profile_image' => 'profile-images/retained.jpg',
    ]);

    $this->actingAs($user);
    Livewire::test('account.delete')
        ->set('password', 'correct-password')
        ->call('deleteAccount')
        ->assertForbidden();

    $this->assertDatabaseHas('users', ['id' => $user->id]);
    $this->assertDatabaseHas('member_profiles', ['id' => $profile->id]);
    Storage::disk('public')->assertExists('profile-images/retained.jpg');
    $this->assertAuthenticatedAs($user);
});

test('profile deletion requests are forbidden for owners and other members', function () {
    $owner = User::factory()->create(['membership_status' => 'active']);
    $profile = MemberProfile::factory()->create(['user_id' => $owner->id]);
    $this->actingAs($owner)->delete(route('account.profiles.destroy', $profile))->assertForbidden();
    $this->actingAs(User::factory()->create(['membership_status' => 'active']))
        ->delete(route('account.profiles.destroy', $profile))->assertForbidden();
    $this->assertDatabaseHas('member_profiles', ['id' => $profile->id]);
});

test('account page contains neither account nor profile deletion links', function () {
    $owner = User::factory()->create(['membership_status' => 'active']);
    $profile = MemberProfile::factory()->create(['user_id' => $owner->id]);
    $this->actingAs($owner)->get(route('account'))
        ->assertOk()
        ->assertDontSee('href="'.route('account.delete').'"', false)
        ->assertDontSee('action="'.route('account.profiles.destroy', $profile).'"', false);
});
