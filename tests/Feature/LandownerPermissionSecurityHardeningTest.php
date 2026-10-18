<?php

use App\Models\LandownerPermission;
use App\Models\MemberProfile;
use App\Models\User;
use Illuminate\Support\Facades\Mail;

function hardeningPermission(string $token, array $overrides = []): LandownerPermission
{
    $profile = MemberProfile::factory()->for(User::factory()->create())->create();

    return LandownerPermission::forceCreate(array_merge([
        'member_profile_id' => $profile->id,
        'landowner_email' => 'landowner@example.com',
        'property_label' => 'Private field',
        'proposed_activity' => 'Surface detecting',
        'starts_on' => today(),
        'ends_on' => today()->addDays(2),
        'access_token_hash' => hash('sha256', $token),
        'response_link_expires_at' => now()->addDays(30),
    ], $overrides));
}

it('requires explicit confirmation of authority before approval', function () {
    $token = str_repeat('H', 64);
    $permission = hardeningPermission($token);

    $this->post(route('landowner-permissions.decision', $token), ['decision' => 'approved'])
        ->assertSessionHasErrors('authority_confirmed');

    expect($permission->fresh()->status)->toBe('pending');
});

it('rejects decisions from an expired invitation link', function () {
    $token = str_repeat('I', 64);
    $permission = hardeningPermission($token, ['response_link_expires_at' => now()->subMinute()]);

    $this->post(route('landowner-permissions.decision', $token), [
        'decision' => 'approved', 'authority_confirmed' => '1',
    ])->assertStatus(409);

    expect($permission->fresh()->status)->toBe('pending');
});

it('allows an approved landowner to withdraw after the invitation expires', function () {
    Mail::fake();
    $token = str_repeat('J', 64);
    $permission = hardeningPermission($token, [
        'status' => 'approved',
        'responded_at' => now()->subDays(35),
        'response_link_expires_at' => now()->subDays(5),
    ]);

    $this->post(route('landowner-permissions.decision', $token), ['decision' => 'withdrawn'])
        ->assertRedirect();

    expect($permission->fresh()->withdrawn_at)->not->toBeNull();
});

it('does not allow approval after the proposed permission dates have ended', function () {
    $token = str_repeat('L', 64);
    $permission = hardeningPermission($token, [
        'starts_on' => today()->subDays(3),
        'ends_on' => today()->subDay(),
    ]);

    $this->post(route('landowner-permissions.decision', $token), [
        'decision' => 'approved', 'authority_confirmed' => '1',
    ])->assertStatus(409);

    expect($permission->fresh()->status)->toBe('pending');
});

it('does not send expiration reminders for withdrawn or expired permissions', function () {
    Mail::fake();
    hardeningPermission(str_repeat('M', 64), [
        'status' => 'approved', 'withdrawn_at' => now(), 'ends_on' => today()->addDay(),
    ]);
    hardeningPermission(str_repeat('N', 64), [
        'status' => 'approved', 'ends_on' => today()->subDay(),
    ]);

    $this->artisan('permissions:remind-expiring')->assertSuccessful();
    Mail::assertNothingSent();
});
