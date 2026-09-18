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

test('administrator cannot assign administrator access', function () {
    $administrator = activeStaffUser(['is_admin' => true]);
    $member = activeStaffUser();

    $this->actingAs($administrator);

    Livewire::test('admin.members.show', ['user' => $member])
        ->call('makeAdministrator')
        ->assertStatus(403);

    expect($member->fresh()->is_admin)->toBeFalse();
});

test('administrator cannot remove another administrator', function () {
    $administrator = activeStaffUser(['is_admin' => true]);
    $otherAdministrator = activeStaffUser(['is_admin' => true]);

    $this->actingAs($administrator);

    Livewire::test('admin.members.show', ['user' => $otherAdministrator])
        ->call('removeAdministrator')
        ->assertStatus(403);

    expect($otherAdministrator->fresh()->is_admin)->toBeTrue();
});

test('administrator can assign and remove moderator access', function () {
    $administrator = activeStaffUser(['is_admin' => true]);
    $member = activeStaffUser();

    $this->actingAs($administrator);

    Livewire::test('admin.members.show', ['user' => $member])
        ->call('makeModerator')
        ->assertHasNoErrors();

    expect($member->fresh()->is_moderator)->toBeTrue();

    Livewire::test('admin.members.show', ['user' => $member->fresh()])
        ->call('removeModerator')
        ->assertHasNoErrors();

    expect($member->fresh()->is_moderator)->toBeFalse();
});

test('administrator can enforce against moderator', function () {
    $administrator = activeStaffUser(['is_admin' => true]);
    $moderator = activeStaffUser(['is_moderator' => true]);

    $this->actingAs($administrator);

    Livewire::test('admin.members.show', ['user' => $moderator])
        ->set('suspensionReason', 'Administrator enforcement against a moderator is permitted.')
        ->call('suspendMembership')
        ->assertHasNoErrors();

    expect($moderator->fresh()->membership_status)->toBe('suspended');
});

test('moderator cannot enforce against administrator', function () {
    $moderator = activeStaffUser(['is_moderator' => true]);
    $administrator = activeStaffUser(['is_admin' => true]);

    $this->actingAs($moderator);

    Livewire::test('admin.members.show', ['user' => $administrator])
        ->set('suspensionReason', 'A moderator must not enforce against an administrator.')
        ->call('suspendMembership')
        ->assertStatus(403);

    expect($administrator->fresh()->membership_status)->toBe('active');
});

test('moderator cannot enforce against another moderator', function () {
    $moderator = activeStaffUser(['is_moderator' => true]);
    $otherModerator = activeStaffUser(['is_moderator' => true]);

    $this->actingAs($moderator);

    Livewire::test('admin.members.show', ['user' => $otherModerator])
        ->set('suspensionReason', 'A moderator must not enforce against another moderator.')
        ->call('suspendMembership')
        ->assertStatus(403);

    expect($otherModerator->fresh()->membership_status)->toBe('active');
});

test('super administrator can enforce against administrator', function () {
    $superAdmin = activeStaffUser([
        'is_super_admin' => true,
        'is_admin' => false,
        'is_moderator' => false,
    ]);
    $administrator = activeStaffUser(['is_admin' => true]);

    $this->actingAs($superAdmin);

    Livewire::test('admin.members.show', ['user' => $administrator])
        ->set('suspensionReason', 'Super Administrator enforcement against an administrator is permitted.')
        ->call('suspendMembership')
        ->assertHasNoErrors();

    expect($administrator->fresh()->membership_status)->toBe('suspended');
});

test('moderator cannot suspend ordinary member', function () {
    $moderator = activeStaffUser([
        'is_moderator' => true,
    ]);

    $member = activeStaffUser();

    $this->actingAs($moderator);

    Livewire::test('admin.members.show', ['user' => $member])
        ->set(
            'suspensionReason',
            'Moderators must not be allowed to suspend members.'
        )
        ->call('suspendMembership')
        ->assertStatus(403);

    expect($member->fresh()->membership_status)->toBe('active');
});

test('administrator cannot ban ordinary member', function () {
    $administrator = activeStaffUser([
        'is_admin' => true,
    ]);

    $member = activeStaffUser();

    $this->actingAs($administrator);

    Livewire::test('admin.members.show', ['user' => $member])
        ->set(
            'banReason',
            'Administrators must not be allowed to ban members.'
        )
        ->call('banMember')
        ->assertStatus(403);

    expect($member->fresh()->membership_status)->toBe('active');
});

test('administrator cannot unban member', function () {
    $administrator = activeStaffUser([
        'is_admin' => true,
    ]);

    $member = activeStaffUser([
        'membership_status' => 'banned',
    ]);

    $this->actingAs($administrator);

    Livewire::test('admin.members.show', ['user' => $member])
        ->set(
            'unbanReason',
            'Administrators must not be allowed to unban members.'
        )
        ->call('reinstateToGoodStanding')
        ->assertStatus(403);

    expect($member->fresh()->membership_status)->toBe('banned');
});

test('super administrator can ban ordinary member', function () {
    $superAdmin = activeStaffUser([
        'is_super_admin' => true,
        'is_admin' => false,
        'is_moderator' => false,
    ]);

    $member = activeStaffUser();

    $this->actingAs($superAdmin);

    Livewire::test('admin.members.show', ['user' => $member])
        ->set(
            'banReason',
            'Super Administrator ban authority is permitted.'
        )
        ->call('banMember')
        ->assertHasNoErrors();

    expect($member->fresh()->membership_status)->toBe('banned');
});
