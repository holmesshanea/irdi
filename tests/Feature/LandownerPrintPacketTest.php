<?php

use App\Models\MemberProfile;
use App\Models\User;

test('active public member introduction includes a printable signed agreement', function () {
    $user = User::factory()->create(['membership_status' => 'active']);
    $profile = MemberProfile::factory()->for($user)->create(['directory_visible' => true, 'profile_name' => 'Example Detectorist']);

    $this->get(route('member-profiles.introduction', $profile))
        ->assertOk()
        ->assertSee('Landowner Metal Detecting Permission Agreement')
        ->assertSee('Print or Save as PDF — Introduction &amp; Permission Packet', false)
        ->assertSee('Landowner / authorized representative signature')
        ->assertSee('Detectorist signature')
        ->assertSee('may be withdrawn at any time')
        ->assertSee('IRDI is not a party');
});

test('member permission dashboard offers printed packet without requiring landowner email', function () {
    $user = User::factory()->create(['membership_status' => 'active']);
    $profile = MemberProfile::factory()->for($user)->create(['directory_visible' => true]);

    $this->actingAs($user)->get(route('account.permissions.index'))
        ->assertOk()
        ->assertSee('Print introduction & agreement')
        ->assertSee(route('member-profiles.introduction', $profile))
        ->assertDontSee('name="landowner_email"', false);
});
