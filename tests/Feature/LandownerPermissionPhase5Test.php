<?php

use App\Models\LandownerPermission;
use App\Models\MemberProfile;
use App\Models\User;

it('requires authentication for the private permissions dashboard', function () {
    $this->get(route('account.permissions.index'))->assertRedirect();
});

it('allows only the profile owner to see a permission record', function () {
    $owner = User::factory()->create();
    $stranger = User::factory()->create();
    $profile = MemberProfile::factory()->for($owner)->create();
    $permission = LandownerPermission::forceCreate([
        'member_profile_id' => $profile->id,
        'landowner_email' => 'owner@example.com',
        'property_label' => 'North field',
        'proposed_activity' => 'Surface detecting',
        'starts_on' => today()->addDay(),
        'ends_on' => today()->addDays(2),
        'access_token_hash' => hash('sha256', str_repeat('A', 64)),
    ]);

    $this->actingAs($stranger)->get(route('account.permissions.show', $permission))->assertForbidden();
    $this->actingAs($owner)->get(route('account.permissions.show', $permission))->assertOk();
});

it('does not expose a permission request with an invalid token', function () {
    $this->get(route('landowner-permissions.respond', ['token' => str_repeat('Z', 64)]))->assertNotFound();
});

it('records landowner approval and allows later withdrawal', function () {
    $owner = User::factory()->create();
    $profile = MemberProfile::factory()->for($owner)->create();
    $token = str_repeat('B', 64);
    $permission = LandownerPermission::forceCreate([
        'member_profile_id' => $profile->id,
        'landowner_email' => 'owner@example.com',
        'property_label' => 'North field',
        'proposed_activity' => 'Surface detecting',
        'starts_on' => today()->addDay(),
        'ends_on' => today()->addDays(2),
        'access_token_hash' => hash('sha256', $token),
    ]);

    $this->post(route('landowner-permissions.decision', ['token' => $token]), [
        'decision' => 'approved', 'authority_confirmed' => '1', 'landowner_conditions' => 'Close every hole.',
    ])->assertRedirect();
    expect($permission->fresh()->status)->toBe('approved')
        ->and($permission->fresh()->landowner_conditions)->toBe('Close every hole.');

    $this->post(route('landowner-permissions.decision', ['token' => $token]), [
        'decision' => 'withdrawn',
    ])->assertRedirect();
    expect($permission->fresh()->withdrawn_at)->not->toBeNull();
});
