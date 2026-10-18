<?php

use App\Mail\LandownerPermissionNotice;
use App\Models\LandownerPermission;
use App\Models\MemberProfile;
use App\Models\User;
use Illuminate\Support\Facades\Mail;

it('restricts permission oversight to administrators', function () {
    $member = User::factory()->create();
    $admin = User::factory()->create(['is_admin' => true]);

    $this->actingAs($member)->get(route('admin.landowner-permissions.index'))->assertForbidden();
    $this->actingAs($admin)->get(route('admin.landowner-permissions.index'))->assertOk();
});

it('emails a member after a landowner approves without exposing the response token', function () {
    Mail::fake();
    $member = User::factory()->create();
    $profile = MemberProfile::factory()->for($member)->create();
    $token = str_repeat('K', 64);
    $permission = LandownerPermission::forceCreate([
        'member_profile_id' => $profile->id,
        'landowner_email' => 'landowner@example.com',
        'property_label' => 'Orchard',
        'proposed_activity' => 'Surface detecting',
        'starts_on' => today()->addDay(),
        'ends_on' => today()->addDays(2),
        'access_token_hash' => hash('sha256', $token),
    ]);

    $this->post(route('landowner-permissions.decision', ['token' => $token]), ['decision' => 'approved'])->assertRedirect();
    expect($permission->fresh()->status)->toBe('approved');
    Mail::assertSent(LandownerPermissionNotice::class, 1);
});

it('reminds a member once when approved permission approaches expiration', function () {
    Mail::fake();
    $member = User::factory()->create();
    $profile = MemberProfile::factory()->for($member)->create();
    $permission = LandownerPermission::forceCreate([
        'member_profile_id' => $profile->id,
        'landowner_email' => 'landowner@example.com',
        'property_label' => 'Meadow',
        'proposed_activity' => 'Surface detecting',
        'starts_on' => today()->subDay(),
        'ends_on' => today()->addDay(),
        'status' => 'approved',
        'access_token_hash' => hash('sha256', str_repeat('Q', 64)),
    ]);

    $this->artisan('permissions:remind-expiring')->assertSuccessful();
    $this->artisan('permissions:remind-expiring')->assertSuccessful();
    expect($permission->fresh()->last_reminded_at)->not->toBeNull();
    Mail::assertSent(LandownerPermissionNotice::class, 1);
});
