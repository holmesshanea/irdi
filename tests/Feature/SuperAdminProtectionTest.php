<?php

use App\Models\User;
use Illuminate\Support\Facades\Route;
use Livewire\Livewire;

function activeStaffUser(array $attributes = []): User
{
    return User::factory()->create(array_merge([
        'membership_status' => 'active',
        'member_since' => now()->toDateString(),
        'ethics_agreed_at' => now(),
        'best_practices_agreed_at' => now(),
    ], $attributes));
}

test('super administrator passes administrator middleware without the administrator flag', function () {
    $superAdmin = activeStaffUser([
        'is_super_admin' => true,
        'is_admin' => false,
        'is_moderator' => false,
    ]);

    Route::get('/_tests/admin-only', fn () => response('OK'))
        ->middleware(['web', 'auth', 'verified', 'admin']);

    $this->actingAs($superAdmin)
        ->get('/_tests/admin-only')
        ->assertOk();
});

test('administrator cannot restrict super administrator messaging', function () {
    $administrator = activeStaffUser(['is_admin' => true]);
    $superAdmin = activeStaffUser(['is_super_admin' => true]);

    $this->actingAs($administrator);

    Livewire::test('admin.members.show', ['user' => $superAdmin])
        ->set('restrictionType', '7_days')
        ->set('enforcementReason', 'This restriction should never be allowed.')
        ->call('disableMessaging')
        ->assertStatus(403);

    expect($superAdmin->fresh()->messaging_disabled_at)->toBeNull();
});

test('administrator cannot suspend super administrator membership', function () {
    $administrator = activeStaffUser(['is_admin' => true]);
    $superAdmin = activeStaffUser(['is_super_admin' => true]);

    $this->actingAs($administrator);

    Livewire::test('admin.members.show', ['user' => $superAdmin])
        ->set('suspensionReason', 'This suspension should never be allowed.')
        ->call('suspendMembership')
        ->assertStatus(403);

    expect($superAdmin->fresh()->membership_status)->toBe('active');
});

test('administrator cannot ban super administrator', function () {
    $administrator = activeStaffUser(['is_admin' => true]);
    $superAdmin = activeStaffUser(['is_super_admin' => true]);

    $this->actingAs($administrator);

    Livewire::test('admin.members.show', ['user' => $superAdmin])
        ->set('banReason', 'This ban should never be allowed to succeed.')
        ->call('banMember')
        ->assertStatus(403);

    expect($superAdmin->fresh()->membership_status)->toBe('active');
});

test('administrator cannot change super administrator staff roles', function () {
    $administrator = activeStaffUser(['is_admin' => true]);
    $superAdmin = activeStaffUser([
        'is_super_admin' => true,
        'is_admin' => false,
        'is_moderator' => false,
    ]);

    $this->actingAs($administrator);

    Livewire::test('admin.members.show', ['user' => $superAdmin])
        ->call('makeAdministrator')
        ->assertStatus(403);

    $superAdmin->refresh();

    expect($superAdmin->is_super_admin)->toBeTrue()
        ->and($superAdmin->is_admin)->toBeFalse()
        ->and($superAdmin->is_moderator)->toBeFalse();
});

test('moderator cannot enforce against super administrator', function () {
    $moderator = activeStaffUser(['is_moderator' => true]);
    $superAdmin = activeStaffUser(['is_super_admin' => true]);

    $this->actingAs($moderator);

    Livewire::test('admin.members.show', ['user' => $superAdmin])
        ->set('suspensionReason', 'A moderator must not suspend a super administrator.')
        ->call('suspendMembership')
        ->assertStatus(403);

    expect($superAdmin->fresh()->membership_status)->toBe('active');
});

test('super administrator can manage ordinary staff without administrator flag', function () {
    $superAdmin = activeStaffUser([
        'is_super_admin' => true,
        'is_admin' => false,
        'is_moderator' => false,
    ]);
    $member = activeStaffUser();

    $this->actingAs($superAdmin);

    Livewire::test('admin.members.show', ['user' => $member])
        ->call('makeAdministrator')
        ->assertHasNoErrors();

    $member->refresh();

    expect($member->is_admin)->toBeTrue()
        ->and($member->is_moderator)->toBeFalse();

    Livewire::test('admin.members.show', ['user' => $member])
        ->call('removeAdministrator')
        ->assertHasNoErrors();

    expect($member->fresh()->is_admin)->toBeFalse();
});
