<?php

use App\Models\BanIdentifier;
use App\Models\MembershipEnforcement;
use App\Models\User;
use App\Notifications\MembershipBannedNotification;
use Illuminate\Support\Facades\Notification;
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

test('administrative review outcome controls allow formal ban outcome', function () {
    Notification::fake();
    $this->freezeTime();

    $superAdmin = administrativeReviewLockUser([
        'is_super_admin' => true,
        'email' => 'super-admin@example.com',
    ]);

    $member = administrativeReviewLockUser([
        'email' => 'ADMINISTRATIVE-REVIEW-MEMBER@example.com',
        'registration_ip' => '8.8.8.8',
        'last_login_ip' => '1.1.1.1',
    ]);

    $hold = activeAdministrativeReview($member, $superAdmin);

    $this->actingAs($superAdmin);

    Livewire::test('admin.members.show', ['user' => $member])
        ->set(
            'banReason',
            'The completed administrative review supports permanent removal from IRDI.'
        )
        ->call('resolveAdministrativeHoldWithBan', $hold->id)
        ->assertHasNoErrors();

    $member->refresh();
    $hold->refresh();

    $ban = MembershipEnforcement::query()
        ->where('user_id', $member->id)
        ->where('action', 'banned')
        ->where('status', 'active')
        ->sole();

    expect($member->membership_status)->toBe('banned')
        ->and($hold->status)->toBe('resolved')
        ->and($hold->resolved_at?->toDateTimeString())->toBe(now()->toDateTimeString())
        ->and($hold->resolved_by)->toBe($superAdmin->id)
        ->and($ban->reason)->toBe('The completed administrative review supports permanent removal from IRDI.')
        ->and($ban->starts_at?->toDateTimeString())->toBe(now()->toDateTimeString())
        ->and($ban->reinstatement_eligible_at?->toDateTimeString())
        ->toBe(now()->addYears(3)->toDateTimeString())
        ->and($ban->permanently_ineligible)->toBeFalse();

    expect(
        MembershipEnforcement::query()
            ->where('user_id', $member->id)
            ->where('action', 'administrative_hold')
            ->where('status', 'active')
            ->count()
    )->toBe(0);

    $this->assertDatabaseHas('ban_identifiers', [
        'user_id' => $member->id,
        'membership_enforcement_id' => $ban->id,
        'type' => 'email',
        'value' => 'administrative-review-member@example.com',
    ]);

    $this->assertDatabaseHas('ban_identifiers', [
        'user_id' => $member->id,
        'membership_enforcement_id' => $ban->id,
        'type' => 'ip',
        'value' => '8.8.8.8',
    ]);

    $this->assertDatabaseHas('ban_identifiers', [
        'user_id' => $member->id,
        'membership_enforcement_id' => $ban->id,
        'type' => 'ip',
        'value' => '1.1.1.1',
    ]);

    expect(
        BanIdentifier::query()
            ->where('membership_enforcement_id', $ban->id)
            ->count()
    )->toBe(3);

    $this->assertDatabaseHas('staff_activity_logs', [
        'target_user_id' => $member->id,
        'action' => 'membership_administrative_hold_resolved_ban',
        'subject_type' => MembershipEnforcement::class,
        'subject_id' => $hold->id,
    ]);

    $this->assertDatabaseHas('staff_activity_logs', [
        'target_user_id' => $member->id,
        'action' => 'member_banned',
        'subject_type' => MembershipEnforcement::class,
        'subject_id' => $ban->id,
    ]);

    Notification::assertSentTo(
        $member,
        MembershipBannedNotification::class,
        function (MembershipBannedNotification $notification): bool {
            return $notification->reason
                === 'The completed administrative review supports permanent removal from IRDI.';
        }
    );
});
