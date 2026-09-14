<?php

use App\Models\MembershipEnforcement;
use App\Models\User;
use Livewire\Livewire;

function administrativeReviewLockUser(array $attributes = []): User
{
    return User::factory()->create(array_merge([
        'membership_status' => 'active',
        'member_since' => now()->toDateString(),
        'ethics_agreed_at' => now(),
        'best_practices_agreed_at' => now(),
        'is_admin' => false,
        'is_super_admin' => false,
        'is_moderator' => false,
    ], $attributes));
}

function activeAdministrativeReview(User $member, ?User $admin = null): MembershipEnforcement
{
    return MembershipEnforcement::create([
        'user_id' => $member->id,
        'admin_id' => $admin?->id,
        'action' => 'administrative_hold',
        'status' => 'active',
        'reason' => 'A serious concern requires investigation before any disciplinary outcome is selected.',
        'starts_at' => now()->subDay(),
    ]);
}

test('ordinary warning cannot bypass an active administrative review', function () {
    $admin = administrativeReviewLockUser(['is_admin' => true]);
    $member = administrativeReviewLockUser();
    activeAdministrativeReview($member, $admin);

    $this->actingAs($admin);

    Livewire::test('admin.members.show', ['user' => $member])
        ->set('warningReason', 'This ordinary warning must not bypass the open administrative review.')
        ->call('issueWarning')
        ->assertHasErrors(['warningReason']);

    $this->assertDatabaseMissing('membership_enforcements', [
        'user_id' => $member->id,
        'action' => 'warning',
        'status' => 'active',
    ]);

    expect($member->fresh()->isUnderAdministrativeReview())->toBeTrue();
});

test('ordinary probation cannot bypass an active administrative review', function () {
    $admin = administrativeReviewLockUser(['is_admin' => true]);
    $member = administrativeReviewLockUser();
    activeAdministrativeReview($member, $admin);

    $this->actingAs($admin);

    Livewire::test('admin.members.show', ['user' => $member])
        ->set('probationReason', 'This ordinary probation must not bypass the open administrative review.')
        ->set('probationRequirements', 'Complete the documented requirements before restoration.')
        ->call('placeOnProbation')
        ->assertHasErrors(['probationReason']);

    $this->assertDatabaseMissing('membership_enforcements', [
        'user_id' => $member->id,
        'action' => 'probation',
        'status' => 'active',
    ]);

    expect($member->fresh()->isUnderAdministrativeReview())->toBeTrue();
});

test('ordinary suspension cannot bypass an active administrative review', function () {
    $admin = administrativeReviewLockUser(['is_admin' => true]);
    $member = administrativeReviewLockUser();
    activeAdministrativeReview($member, $admin);

    $this->actingAs($admin);

    Livewire::test('admin.members.show', ['user' => $member])
        ->set('suspensionReason', 'This direct suspension must not bypass the open administrative review.')
        ->set('suspensionReviewPeriod', '90')
        ->call('suspendMembership')
        ->assertHasErrors(['suspensionReason']);

    expect($member->fresh()->membership_status)->toBe('active');

    $this->assertDatabaseMissing('membership_enforcements', [
        'user_id' => $member->id,
        'action' => 'suspended',
        'status' => 'active',
    ]);
});

test('ordinary direct ban cannot bypass an active administrative review', function () {
    $superAdmin = administrativeReviewLockUser(['is_super_admin' => true]);
    $member = administrativeReviewLockUser();
    activeAdministrativeReview($member, $superAdmin);

    $this->actingAs($superAdmin);

    Livewire::test('admin.members.show', ['user' => $member])
        ->set('banReason', 'This direct ban must not bypass the open administrative review.')
        ->call('banMember')
        ->assertHasErrors(['banReason']);

    expect($member->fresh()->membership_status)->toBe('active');

    $this->assertDatabaseMissing('membership_enforcements', [
        'user_id' => $member->id,
        'action' => 'banned',
    ]);
});

test('administrative review outcome controls still allow formal suspension outcome', function () {
    $admin = administrativeReviewLockUser(['is_admin' => true]);
    $member = administrativeReviewLockUser();
    $hold = activeAdministrativeReview($member, $admin);

    $this->actingAs($admin);

    Livewire::test('admin.members.show', ['user' => $member])
        ->set(
            'suspensionReason',
            'The completed investigation supports a formal membership suspension.'
        )
        ->set('suspensionReviewPeriod', '90')
        ->call('resolveAdministrativeHoldWithSuspension', $hold->id)
        ->assertHasNoErrors();

    expect($member->fresh()->membership_status)->toBe('suspended')
        ->and($hold->fresh()->status)->toBe('resolved');
});
