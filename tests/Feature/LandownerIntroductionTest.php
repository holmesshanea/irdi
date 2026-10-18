<?php

use App\Models\MemberProfile;
use App\Models\User;

test('active public member can share a printable landowner introduction', function () {
    $user = User::factory()->create(['membership_status' => 'active']);
    $profile = MemberProfile::factory()->for($user)->create(['directory_visible' => true, 'profile_name' => 'Example Detectorist']);

    $this->get(route('member-profiles.introduction', $profile))
        ->assertOk()
        ->assertSee('Example Detectorist')
        ->assertSee('Print or Save as PDF')
        ->assertSee('Your property, your decision')
        ->assertSee(route('member-profiles.show', $profile));
});

test('inactive or nonpublic member introduction is not accessible to strangers', function () {
    $user = User::factory()->create(['membership_status' => 'active']);
    $profile = MemberProfile::factory()->for($user)->create(['directory_visible' => false, 'personal_information_hidden' => false]);
    $this->get(route('member-profiles.introduction', $profile))->assertNotFound();
    $this->actingAs($user)->get(route('member-profiles.introduction', $profile))->assertOk();

    $user->update(['membership_status' => 'suspended']);
    $this->actingAs($user)->get(route('member-profiles.introduction', $profile))->assertNotFound();
});
