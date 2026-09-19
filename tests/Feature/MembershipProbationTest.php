<?php

use App\Models\MembershipEnforcement;
use App\Models\StaffActivityLog;
use App\Models\User;
use App\Notifications\MembershipProbationContinuedNotification;
use App\Notifications\MembershipProbationResolvedNotification;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Livewire\Livewire;

uses(RefreshDatabase::class);

function probationStaffUser(array $attributes = []): User
{
    return User::factory()->create(array_merge([
        'membership_status' => 'active',
        'member_since' => now()->subYear(),
        'ethics_agreed_at' => now()->subYear(),
        'best_practices_agreed_at' => now()->subYear(),
    ], $attributes));
}

test('administrator can place ordinary member on probation', function () {
    Notification::fake();

    $admin = probationStaffUser([
        'is_admin' => true,
    ]);

    $member = probationStaffUser();

    $this->actingAs($admin);

    Livewire::test('admin.members.show', ['user' => $member])
        ->set('probationReason', 'Repeated conduct requires formal membership probation.')
        ->set('probationDetails', 'Internal administrative context for this probation.')
        ->set('probationRequirements', 'Complete the required corrective actions before requesting restoration.')
        ->set('probationReviewDays', '90')
        ->call('placeOnProbation')
        ->assertHasNoErrors();

    $probation = MembershipEnforcement::query()
        ->where('user_id', $member->id)
        ->where('action', 'probation')
        ->first();

    expect($probation)->not->toBeNull()
        ->and($probation->status)->toBe('active')
        ->and($probation->admin_id)->toBe($admin->id)
        ->and($probation->starts_at)->not->toBeNull()
        ->and($probation->eligible_for_review_at)->not->toBeNull()
        ->and($probation->restoration_requirements)
        ->toBe('Complete the required corrective actions before requesting restoration.');

    expect($member->fresh()->membership_status)->toBe('active')
        ->and($member->fresh()->isOnProbation())->toBeTrue()
        ->and($member->fresh()->isInGoodStanding())->toBeFalse();
});

test('probation defaults to ninety day review eligibility', function () {
    Notification::fake();

    $admin = probationStaffUser([
        'is_admin' => true,
    ]);

    $member = probationStaffUser();

    $this->actingAs($admin);

    Livewire::test('admin.members.show', ['user' => $member])
        ->set('probationReason', 'Formal probation is required for this membership concern.')
        ->set('probationRequirements', 'Complete all documented requirements before formal review.')
        ->call('placeOnProbation')
        ->assertHasNoErrors();

    $probation = MembershipEnforcement::query()
        ->where('user_id', $member->id)
        ->where('action', 'probation')
        ->firstOrFail();

    expect($probation->starts_at)->not->toBeNull()
        ->and($probation->eligible_for_review_at)->not->toBeNull()
        ->and($probation->starts_at->diffInDays($probation->eligible_for_review_at))
        ->toBe(90.0);
});

test('moderator cannot place member on probation', function () {
    $moderator = probationStaffUser([
        'is_moderator' => true,
    ]);

    $member = probationStaffUser();

    $this->actingAs($moderator);

    Livewire::test('admin.members.show', ['user' => $member])
        ->set('probationReason', 'This reason is sufficiently long for validation.')
        ->set('probationRequirements', 'These requirements are sufficiently long for validation.')
        ->call('placeOnProbation')
        ->assertForbidden();

    expect(
        MembershipEnforcement::query()
            ->where('user_id', $member->id)
            ->where('action', 'probation')
            ->exists()
    )->toBeFalse();
});

test('administrator cannot place another administrator on probation', function () {
    $admin = probationStaffUser([
        'is_admin' => true,
    ]);

    $targetAdmin = probationStaffUser([
        'is_admin' => true,
    ]);

    $this->actingAs($admin);

    Livewire::test('admin.members.show', ['user' => $targetAdmin])
        ->set('probationReason', 'This reason is sufficiently long for validation.')
        ->set('probationRequirements', 'These requirements are sufficiently long for validation.')
        ->call('placeOnProbation')
        ->assertForbidden();

    expect($targetAdmin->fresh()->isOnProbation())->toBeFalse();
});

test('super administrator can place administrator on probation', function () {
    Notification::fake();

    $superAdmin = probationStaffUser([
        'is_super_admin' => true,
    ]);

    $admin = probationStaffUser([
        'is_admin' => true,
    ]);

    $this->actingAs($superAdmin);

    Livewire::test('admin.members.show', ['user' => $admin])
        ->set('probationReason', 'Formal probation is required following administrative review.')
        ->set('probationRequirements', 'Complete all corrective requirements before restoration review.')
        ->call('placeOnProbation')
        ->assertHasNoErrors();

    expect($admin->fresh()->membership_status)->toBe('active')
        ->and($admin->fresh()->isOnProbation())->toBeTrue()
        ->and($admin->fresh()->isInGoodStanding())->toBeFalse();
});

test('member cannot receive duplicate active probation', function () {
    Notification::fake();

    $admin = probationStaffUser([
        'is_admin' => true,
    ]);

    $member = probationStaffUser();

    MembershipEnforcement::create([
        'user_id' => $member->id,
        'admin_id' => $admin->id,
        'action' => 'probation',
        'status' => 'active',
        'reason' => 'Existing probation remains active for this member.',
        'starts_at' => now()->subDay(),
        'eligible_for_review_at' => now()->addDays(89),
        'restoration_requirements' => 'Complete the existing probation requirements.',
    ]);

    $this->actingAs($admin);

    Livewire::test('admin.members.show', ['user' => $member])
        ->set('probationReason', 'Attempting to create another active probation record.')
        ->set('probationRequirements', 'Complete another set of corrective requirements.')
        ->call('placeOnProbation')
        ->assertHasErrors(['probationReason']);

    expect(
        MembershipEnforcement::query()
            ->where('user_id', $member->id)
            ->where('action', 'probation')
            ->where('status', 'active')
            ->count()
    )->toBe(1);
});

test('administrator can resolve probation and restore good standing', function () {
    Notification::fake();
    $admin = probationStaffUser([
        'is_admin' => true,
    ]);

    $member = probationStaffUser();

    $probation = MembershipEnforcement::create([
        'user_id' => $member->id,
        'admin_id' => $admin->id,
        'action' => 'probation',
        'status' => 'active',
        'reason' => 'Membership probation requiring corrective action.',
        'starts_at' => now()->subDays(100),
        'eligible_for_review_at' => now()->subDays(10),
        'restoration_requirements' => 'Complete the required corrective action.',
    ]);

    expect($member->fresh()->isInGoodStanding())->toBeFalse();

    $this->actingAs($admin);

    Livewire::test('admin.members.show', ['user' => $member])
        ->set('probationRestoreReason', 'The member completed all required corrective actions.')
        ->call('restoreFromProbation', $probation->id)
        ->assertHasNoErrors();

    $probation->refresh();

    expect($probation->status)->toBe('resolved')
        ->and($probation->resolved_at)->not->toBeNull()
        ->and($probation->resolved_by)->toBe($admin->id);

    expect($member->fresh()->membership_status)->toBe('active')
        ->and($member->fresh()->isOnProbation())->toBeFalse()
        ->and($member->fresh()->isInGoodStanding())->toBeTrue();

    Notification::assertSentTo(
        $member,
        MembershipProbationResolvedNotification::class,
        function (MembershipProbationResolvedNotification $notification): bool {
            return $notification->inGoodStanding === true;
        }
    );
});

test('resolving probation does not claim good standing while administrative review remains active', function () {
    $admin = probationStaffUser([
        'is_admin' => true,
    ]);

    $member = probationStaffUser();

    $probation = MembershipEnforcement::create([
        'user_id' => $member->id,
        'admin_id' => $admin->id,
        'action' => 'probation',
        'status' => 'active',
        'reason' => 'Membership probation requiring corrective action.',
        'starts_at' => now()->subDays(100),
        'eligible_for_review_at' => now()->subDays(10),
        'restoration_requirements' => 'Complete the required corrective action.',
    ]);

    MembershipEnforcement::create([
        'user_id' => $member->id,
        'admin_id' => $admin->id,
        'action' => 'administrative_hold',
        'status' => 'active',
        'reason' => 'A separate concern requires administrative review.',
        'starts_at' => now()->subDay(),
    ]);

    expect($member->fresh()->isOnProbation())->toBeTrue()
        ->and($member->fresh()->isUnderAdministrativeReview())->toBeTrue()
        ->and($member->fresh()->isInGoodStanding())->toBeFalse();

    $this->actingAs($admin);

    Livewire::test('admin.members.show', ['user' => $member])
        ->set(
            'probationRestoreReason',
            'The member completed all requirements associated with the probation.'
        )
        ->call('restoreFromProbation', $probation->id)
        ->assertHasNoErrors();

    $member->refresh();
    $probation->refresh();

    expect($probation->status)->toBe('resolved')
        ->and($probation->resolved_at)->not->toBeNull()
        ->and($member->isOnProbation())->toBeFalse()
        ->and($member->isUnderAdministrativeReview())->toBeTrue()
        ->and($member->isInGoodStanding())->toBeFalse();

    $this->assertDatabaseHas('staff_activity_logs', [
        'actor_id' => $admin->id,
        'target_user_id' => $member->id,
        'action' => 'membership_probation_resolved',
        'subject_type' => MembershipEnforcement::class,
        'subject_id' => $probation->id,
        'description' => 'Resolved membership probation for '.$member->name.'.',
    ]);
});

test('restoring probation requires documented reason', function () {
    $admin = probationStaffUser([
        'is_admin' => true,
    ]);

    $member = probationStaffUser();

    $probation = MembershipEnforcement::create([
        'user_id' => $member->id,
        'admin_id' => $admin->id,
        'action' => 'probation',
        'status' => 'active',
        'reason' => 'Membership probation requiring corrective action.',
        'starts_at' => now()->subDays(100),
        'eligible_for_review_at' => now()->subDays(10),
        'restoration_requirements' => 'Complete the required corrective action.',
    ]);

    $this->actingAs($admin);

    Livewire::test('admin.members.show', ['user' => $member])
        ->call('restoreFromProbation', $probation->id)
        ->assertHasErrors(['probationRestoreReason']);

    expect($probation->fresh()->status)->toBe('active')
        ->and($probation->fresh()->resolved_at)->toBeNull();
});

test('probation cannot be resolved through a different member component', function () {
    $admin = probationStaffUser([
        'is_admin' => true,
    ]);

    $member = probationStaffUser();
    $otherMember = probationStaffUser();

    $probation = MembershipEnforcement::create([
        'user_id' => $member->id,
        'admin_id' => $admin->id,
        'action' => 'probation',
        'status' => 'active',
        'reason' => 'Membership probation requiring corrective action.',
        'starts_at' => now()->subDays(100),
        'eligible_for_review_at' => now()->subDays(10),
        'restoration_requirements' => 'Complete the required corrective action.',
    ]);

    $this->actingAs($admin);

    expect(fn () => Livewire::test('admin.members.show', ['user' => $otherMember])
        ->set('probationRestoreReason', 'Attempting restoration through the wrong member record.')
        ->call('restoreFromProbation', $probation->id))
        ->toThrow(ModelNotFoundException::class);

    expect($probation->fresh()->status)->toBe('active')
        ->and($probation->fresh()->resolved_at)->toBeNull();
});

test('administrator can continue probation after formal review', function () {
    Notification::fake();
    $admin = probationStaffUser([
        'is_admin' => true,
    ]);

    $member = probationStaffUser();

    $probation = MembershipEnforcement::create([
        'user_id' => $member->id,
        'admin_id' => $admin->id,
        'action' => 'probation',
        'status' => 'active',
        'reason' => 'Probation remains necessary while corrective requirements are completed.',
        'starts_at' => now()->subDays(100),
        'eligible_for_review_at' => now()->subDays(10),
        'restoration_requirements' => 'Complete the remaining corrective requirements.',
        'review_requested_at' => now()->subDay(),
    ]);

    $this->actingAs($admin);

    Livewire::test('admin.members.show', ['user' => $member])
        ->set(
            'probationContinueReason',
            'Additional time is required to complete the established restoration requirements.'
        )
        ->call('continueProbation', $probation->id)
        ->assertHasNoErrors();

    $probation->refresh();

    expect($probation->status)->toBe('active')
        ->and($probation->resolved_at)->toBeNull()
        ->and($probation->review_requested_at)->toBeNull()
        ->and($probation->eligible_for_review_at)->not->toBeNull()
        ->and(now()->diffInDays($probation->eligible_for_review_at))
        ->toBeGreaterThanOrEqual(89.0)
        ->and(now()->diffInDays($probation->eligible_for_review_at))
        ->toBeLessThanOrEqual(90.0);

    Notification::assertSentTo(
        $member,
        MembershipProbationContinuedNotification::class,
        function (MembershipProbationContinuedNotification $notification) use ($probation): bool {
            $probation->refresh();

            return $notification->eligibleForReviewAt->equalTo(
                $probation->eligible_for_review_at
            );
        }
    );
});

test('continuing probation requires documented reason', function () {
    $admin = probationStaffUser([
        'is_admin' => true,
    ]);

    $member = probationStaffUser();

    $probation = MembershipEnforcement::create([
        'user_id' => $member->id,
        'admin_id' => $admin->id,
        'action' => 'probation',
        'status' => 'active',
        'reason' => 'Probation remains under formal administrative review.',
        'starts_at' => now()->subDays(100),
        'eligible_for_review_at' => now()->subDays(10),
        'restoration_requirements' => 'Complete the established restoration requirements.',
    ]);

    $this->actingAs($admin);

    Livewire::test('admin.members.show', ['user' => $member])
        ->set('probationContinueReason', 'Too short')
        ->call('continueProbation', $probation->id)
        ->assertHasErrors(['probationContinueReason']);

    $probation->refresh();

    expect($probation->eligible_for_review_at?->isPast())->toBeTrue();
});

test('moderator cannot continue probation', function () {
    $moderator = probationStaffUser([
        'is_moderator' => true,
    ]);

    $member = probationStaffUser();

    $probation = MembershipEnforcement::create([
        'user_id' => $member->id,
        'admin_id' => $moderator->id,
        'action' => 'probation',
        'status' => 'active',
        'reason' => 'This probation is ready for formal administrative review.',
        'starts_at' => now()->subDays(100),
        'eligible_for_review_at' => now()->subDays(10),
        'restoration_requirements' => 'Complete the established restoration requirements.',
    ]);

    $this->actingAs($moderator);

    Livewire::test('admin.members.show', ['user' => $member])
        ->set(
            'probationContinueReason',
            'Additional probation time is required before restoration.'
        )
        ->call('continueProbation', $probation->id)
        ->assertForbidden();

    expect($probation->fresh()->eligible_for_review_at?->isPast())->toBeTrue();
});

test('probation cannot be continued through a different member component', function () {
    $admin = probationStaffUser([
        'is_admin' => true,
    ]);

    $member = probationStaffUser();
    $otherMember = probationStaffUser();

    $probation = MembershipEnforcement::create([
        'user_id' => $member->id,
        'admin_id' => $admin->id,
        'action' => 'probation',
        'status' => 'active',
        'reason' => 'This probation belongs to a different member.',
        'starts_at' => now()->subDays(100),
        'eligible_for_review_at' => now()->subDays(10),
        'restoration_requirements' => 'Complete the established restoration requirements.',
    ]);

    $this->actingAs($admin);

    expect(fn () => Livewire::test('admin.members.show', ['user' => $otherMember])
        ->set(
            'probationContinueReason',
            'Attempting to continue another member probation record.'
        )
        ->call('continueProbation', $probation->id))
        ->toThrow(ModelNotFoundException::class);

    expect($probation->fresh()->eligible_for_review_at?->isPast())->toBeTrue();
});

test('suspending member closes active probation as escalation', function () {
    $admin = probationStaffUser([
        'is_admin' => true,
    ]);

    $member = probationStaffUser();

    $originalStartsAt = now()->subDays(100);
    $originalEligibleForReviewAt = now()->subDays(10);
    $originalReviewRequestedAt = now()->subDay();

    $probation = MembershipEnforcement::create([
        'user_id' => $member->id,
        'admin_id' => $admin->id,
        'action' => 'probation',
        'status' => 'active',
        'reason' => 'Probation requires escalation because the concern remains unresolved.',
        'details' => 'The member was given additional context about the conduct concerns.',
        'starts_at' => $originalStartsAt,
        'eligible_for_review_at' => $originalEligibleForReviewAt,
        'restoration_requirements' => 'Complete the established corrective requirements.',
        'review_requested_at' => $originalReviewRequestedAt,
    ]);

    $this->actingAs($admin);

    Livewire::test('admin.members.show', ['user' => $member])
        ->set(
            'suspensionReason',
            'The unresolved probation concerns require temporary suspension of membership.'
        )
        ->call('suspendMembership')
        ->assertHasNoErrors();

    $member->refresh();
    $probation->refresh();

    expect($member->membership_status)
        ->toBe('suspended')
        ->and($probation->status)
        ->toBe('resolved')
        ->and($probation->resolved_at)
        ->not->toBeNull()
        ->and($probation->resolved_by)
        ->toBe($admin->id)
        ->and($member->isOnProbation())
        ->toBeFalse()
        ->and($probation->admin_id)
        ->toBe($admin->id)
        ->and($probation->reason)
        ->toBe('Probation requires escalation because the concern remains unresolved.')
        ->and($probation->details)
        ->toBe('The member was given additional context about the conduct concerns.')
        ->and($probation->starts_at->format('Y-m-d H:i:s'))
        ->toBe($originalStartsAt->format('Y-m-d H:i:s'))
        ->and($probation->eligible_for_review_at->format('Y-m-d H:i:s'))
        ->toBe($originalEligibleForReviewAt->format('Y-m-d H:i:s'))
        ->and($probation->restoration_requirements)
        ->toBe('Complete the established corrective requirements.')
        ->and($probation->review_requested_at->format('Y-m-d H:i:s'))
        ->toBe($originalReviewRequestedAt->format('Y-m-d H:i:s'));

    $this->assertDatabaseHas('membership_enforcements', [
        'user_id' => $member->id,
        'admin_id' => $admin->id,
        'action' => 'suspended',
        'reason' => 'The unresolved probation concerns require temporary suspension of membership.',
    ]);

    $this->assertDatabaseHas('staff_activity_logs', [
        'actor_id' => $admin->id,
        'target_user_id' => $member->id,
        'action' => 'membership_probation_escalated',
        'subject_type' => MembershipEnforcement::class,
        'subject_id' => $probation->id,
    ]);
});

test('membership probation cannot be resolved more than once', function () {
    Notification::fake();

    $administrator = User::factory()->create([
        'is_admin' => true,
        'email' => 'duplicate-probation-resolution-admin@example.com',
    ]);

    $member = User::factory()->create([
        'membership_status' => 'active',
        'email' => 'duplicate-probation-resolution-member@example.com',
    ]);

    $probation = MembershipEnforcement::create([
        'user_id' => $member->id,
        'admin_id' => $administrator->id,
        'action' => 'probation',
        'status' => 'active',
        'reason' => 'The member requires a period of formal membership probation.',
        'starts_at' => now()->subDays(100),
        'eligible_for_review_at' => now()->subDays(10),
        'restoration_requirements' => 'Demonstrate continued compliance with IRDI standards.',
    ]);

    $this->actingAs($administrator);

    Livewire::test('admin.members.show', ['user' => $member])
        ->set(
            'probationRestoreReason',
            'The member has successfully satisfied the probation requirements.'
        )
        ->call('restoreFromProbation', $probation->id)
        ->assertHasNoErrors();

    $probation->refresh();

    $originalResolvedAt = $probation->resolved_at;
    $originalResolvedBy = $probation->resolved_by;

    expect($probation->status)->toBe('resolved')
        ->and($originalResolvedAt)->not->toBeNull()
        ->and($originalResolvedBy)->toBe($administrator->id);

    expect(
        StaffActivityLog::query()
            ->where('target_user_id', $member->id)
            ->where('action', 'membership_probation_resolved')
            ->count()
    )->toBe(1);

    Notification::assertSentToTimes(
        $member,
        MembershipProbationResolvedNotification::class,
        1
    );

    /*
     * A stale or repeated request against the same probation must
     * not resolve it again or create duplicate history.
     */
    Livewire::test('admin.members.show', ['user' => $member])
        ->set(
            'probationRestoreReason',
            'This second resolution attempt must not create duplicate history.'
        )
        ->call('restoreFromProbation', $probation->id)
        ->assertHasErrors(['probationRestoreReason']);

    $probation->refresh();

    expect($probation->status)->toBe('resolved')
        ->and($probation->resolved_at?->toDateTimeString())
        ->toBe($originalResolvedAt?->toDateTimeString())
        ->and($probation->resolved_by)->toBe($originalResolvedBy);

    expect(
        StaffActivityLog::query()
            ->where('target_user_id', $member->id)
            ->where('action', 'membership_probation_resolved')
            ->count()
    )->toBe(1);

    Notification::assertSentToTimes(
        $member,
        MembershipProbationResolvedNotification::class,
        1
    );
});

test('probation resolution rechecks authoritative membership state inside the transaction', function () {
    Notification::fake();

    $administrator = User::factory()->create([
        'is_admin' => true,
        'email' => 'stale-probation-user-admin@example.com',
    ]);

    $member = User::factory()->create([
        'membership_status' => 'active',
        'email' => 'stale-probation-user-member@example.com',
    ]);

    $probation = MembershipEnforcement::create([
        'user_id' => $member->id,
        'admin_id' => $administrator->id,
        'action' => 'probation',
        'status' => 'active',
        'reason' => 'The member requires formal membership probation.',
        'starts_at' => now()->subDays(100),
        'eligible_for_review_at' => now()->subDays(10),
        'restoration_requirements' => 'Demonstrate continued compliance with IRDI standards.',
    ]);

    $this->actingAs($administrator);

    $component = Livewire::test('admin.members.show', ['user' => $member]);

    /*
     * Simulate another request changing the authoritative membership
     * state after this component was loaded.
     */
    $member->update([
        'membership_status' => 'suspended',
    ]);

    $component
        ->set(
            'probationRestoreReason',
            'This stale request must not resolve the membership probation.'
        )
        ->call('restoreFromProbation', $probation->id)
        ->assertHasErrors(['probationRestoreReason']);

    $probation->refresh();

    expect($member->fresh()->membership_status)->toBe('suspended')
        ->and($probation->status)->toBe('active')
        ->and($probation->resolved_at)->toBeNull()
        ->and($probation->resolved_by)->toBeNull();

    $this->assertDatabaseMissing('staff_activity_logs', [
        'target_user_id' => $member->id,
        'action' => 'membership_probation_resolved',
    ]);

    Notification::assertNotSentTo(
        $member,
        MembershipProbationResolvedNotification::class,
    );
});

test('probation resolution rechecks authoritative probation state inside the transaction', function () {
    Notification::fake();

    $administrator = User::factory()->create([
        'is_admin' => true,
        'email' => 'stale-probation-state-admin@example.com',
    ]);

    $member = User::factory()->create([
        'membership_status' => 'active',
        'email' => 'stale-probation-state-member@example.com',
    ]);

    $probation = MembershipEnforcement::create([
        'user_id' => $member->id,
        'admin_id' => $administrator->id,
        'action' => 'probation',
        'status' => 'active',
        'reason' => 'The member requires formal membership probation.',
        'starts_at' => now()->subDays(100),
        'eligible_for_review_at' => now()->subDays(10),
        'restoration_requirements' => 'Demonstrate continued compliance with IRDI standards.',
    ]);

    $this->actingAs($administrator);

    $component = Livewire::test('admin.members.show', ['user' => $member]);

    /*
     * Simulate another request resolving the probation after this
     * component was loaded while the membership remains active.
     */
    $probation->update([
        'status' => 'resolved',
        'resolved_at' => now(),
        'resolved_by' => $administrator->id,
    ]);

    $originalResolvedAt = $probation->resolved_at;
    $originalResolvedBy = $probation->resolved_by;

    $component
        ->set(
            'probationRestoreReason',
            'This stale request must not resolve the probation a second time.'
        )
        ->call('restoreFromProbation', $probation->id)
        ->assertHasErrors(['probationRestoreReason']);

    $probation->refresh();

    expect($member->fresh()->membership_status)->toBe('active')
        ->and($probation->status)->toBe('resolved')
        ->and($probation->resolved_at?->toDateTimeString())
        ->toBe($originalResolvedAt?->toDateTimeString())
        ->and($probation->resolved_by)->toBe($originalResolvedBy);

    $this->assertDatabaseMissing('staff_activity_logs', [
        'target_user_id' => $member->id,
        'action' => 'membership_probation_resolved',
    ]);

    Notification::assertNotSentTo(
        $member,
        MembershipProbationResolvedNotification::class,
    );
});

test('probation resolution rolls back completely when staff activity creation fails', function () {
    Notification::fake();

    $administrator = User::factory()->create([
        'is_admin' => true,
        'email' => 'probation-resolution-rollback-admin@example.com',
    ]);

    $member = User::factory()->create([
        'membership_status' => 'active',
        'email' => 'probation-resolution-rollback-member@example.com',
    ]);

    $probation = MembershipEnforcement::create([
        'user_id' => $member->id,
        'admin_id' => $administrator->id,
        'action' => 'probation',
        'status' => 'active',
        'reason' => 'The member requires formal membership probation.',
        'starts_at' => now()->subDays(100),
        'eligible_for_review_at' => now()->subDays(10),
        'restoration_requirements' => 'Demonstrate continued compliance with IRDI standards.',
    ]);

    StaffActivityLog::creating(function (StaffActivityLog $activity): void {
        if ($activity->action === 'membership_probation_resolved') {
            throw new RuntimeException(
                'Forced staff activity failure during probation resolution.'
            );
        }
    });

    $this->actingAs($administrator);

    expect(fn () => Livewire::test('admin.members.show', ['user' => $member])
        ->set(
            'probationRestoreReason',
            'The member has successfully satisfied the probation requirements.'
        )
        ->call('restoreFromProbation', $probation->id)
    )->toThrow(
        RuntimeException::class,
        'Forced staff activity failure during probation resolution.'
    );

    $probation->refresh();

    expect($member->fresh()->membership_status)->toBe('active')
        ->and($probation->status)->toBe('active')
        ->and($probation->resolved_at)->toBeNull()
        ->and($probation->resolved_by)->toBeNull();

    $this->assertDatabaseMissing('staff_activity_logs', [
        'target_user_id' => $member->id,
        'action' => 'membership_probation_resolved',
    ]);

    Notification::assertNotSentTo(
        $member,
        MembershipProbationResolvedNotification::class,
    );
});

test('probation resolution preserves historical probation evidence', function () {
    Notification::fake();
    $this->freezeTime();

    $originalAdministrator = User::factory()->create([
        'is_admin' => true,
        'email' => 'probation-history-original-admin@example.com',
    ]);

    $resolvingAdministrator = User::factory()->create([
        'is_admin' => true,
        'email' => 'probation-history-resolving-admin@example.com',
    ]);

    $member = User::factory()->create([
        'membership_status' => 'active',
        'email' => 'probation-history-member@example.com',
    ]);

    $startsAt = now()->subDays(120);
    $eligibleForReviewAt = now()->subDays(30);

    $probation = MembershipEnforcement::create([
        'user_id' => $member->id,
        'admin_id' => $originalAdministrator->id,
        'action' => 'probation',
        'status' => 'active',
        'reason' => 'Original probation reason that must remain part of the historical record.',
        'starts_at' => $startsAt,
        'eligible_for_review_at' => $eligibleForReviewAt,
        'restoration_requirements' => 'Complete the original probation requirements and maintain compliance with IRDI standards.',
    ]);

    $originalId = $probation->id;
    $originalAdminId = $probation->admin_id;
    $originalReason = $probation->reason;
    $originalStartsAt = $probation->starts_at;
    $originalEligibleForReviewAt = $probation->eligible_for_review_at;
    $originalRequirements = $probation->restoration_requirements;

    $this->actingAs($resolvingAdministrator);

    Livewire::test('admin.members.show', ['user' => $member])
        ->set(
            'probationRestoreReason',
            'The member has successfully completed every probation requirement.'
        )
        ->call('restoreFromProbation', $probation->id)
        ->assertHasNoErrors();

    $probation->refresh();

    expect($probation->id)->toBe($originalId)
        ->and($probation->admin_id)->toBe($originalAdminId)
        ->and($probation->action)->toBe('probation')
        ->and($probation->reason)->toBe($originalReason)
        ->and($probation->starts_at?->toDateTimeString())
        ->toBe($originalStartsAt?->toDateTimeString())
        ->and($probation->eligible_for_review_at?->toDateTimeString())
        ->toBe($originalEligibleForReviewAt?->toDateTimeString())
        ->and($probation->restoration_requirements)->toBe($originalRequirements)
        ->and($probation->status)->toBe('resolved')
        ->and($probation->resolved_at?->toDateTimeString())
        ->toBe(now()->toDateTimeString())
        ->and($probation->resolved_by)->toBe($resolvingAdministrator->id);

    $member->refresh();

    expect($member->membership_status)->toBe('active')
        ->and($member->isOnProbation())->toBeFalse();

    $this->assertDatabaseHas('staff_activity_logs', [
        'actor_id' => $resolvingAdministrator->id,
        'target_user_id' => $member->id,
        'action' => 'membership_probation_resolved',
        'subject_type' => $probation->getMorphClass(),
        'subject_id' => $probation->id,
    ]);

    Notification::assertSentToTimes(
        $member,
        MembershipProbationResolvedNotification::class,
        1
    );
});

test('probation continuation rechecks authoritative membership state inside the transaction', function () {
    Notification::fake();

    $administrator = User::factory()->create([
        'is_admin' => true,
        'email' => 'stale-probation-continuation-user-admin@example.com',
    ]);

    $member = User::factory()->create([
        'membership_status' => 'active',
        'email' => 'stale-probation-continuation-user-member@example.com',
    ]);

    $originalReviewDate = now()->subDays(10)->startOfSecond();
    $originalReviewRequestedAt = now()->subDay()->startOfSecond();

    $probation = MembershipEnforcement::create([
        'user_id' => $member->id,
        'admin_id' => $administrator->id,
        'action' => 'probation',
        'status' => 'active',
        'reason' => 'The member requires formal membership probation.',
        'starts_at' => now()->subDays(100),
        'eligible_for_review_at' => $originalReviewDate,
        'review_requested_at' => $originalReviewRequestedAt,
        'restoration_requirements' => 'Demonstrate continued compliance with IRDI standards.',
    ]);

    $this->actingAs($administrator);

    $component = Livewire::test('admin.members.show', ['user' => $member]);

    /*
     * Simulate another request changing the authoritative membership
     * state after this component was loaded.
     */
    $member->update([
        'membership_status' => 'suspended',
    ]);

    $component
        ->set(
            'probationContinueReason',
            'This stale request must not continue the membership probation.'
        )
        ->call('continueProbation', $probation->id)
        ->assertHasErrors(['probationContinueReason']);

    $probation->refresh();

    expect($member->fresh()->membership_status)->toBe('suspended')
        ->and($probation->status)->toBe('active')
        ->and($probation->eligible_for_review_at?->toDateTimeString())
        ->toBe($originalReviewDate->toDateTimeString())
        ->and($probation->review_requested_at?->toDateTimeString())
        ->toBe($originalReviewRequestedAt->toDateTimeString())
        ->and($probation->resolved_at)->toBeNull()
        ->and($probation->resolved_by)->toBeNull();

    $this->assertDatabaseMissing('staff_activity_logs', [
        'target_user_id' => $member->id,
        'action' => 'membership_probation_continued',
    ]);

    Notification::assertNotSentTo(
        $member,
        MembershipProbationContinuedNotification::class,
    );
});

test('probation continuation rechecks authoritative probation state inside the transaction', function () {
    Notification::fake();

    $administrator = User::factory()->create([
        'is_admin' => true,
        'email' => 'stale-probation-continuation-state-admin@example.com',
    ]);

    $member = User::factory()->create([
        'membership_status' => 'active',
        'email' => 'stale-probation-continuation-state-member@example.com',
    ]);

    $originalReviewDate = now()->subDays(10)->startOfSecond();
    $originalReviewRequestedAt = now()->subDay()->startOfSecond();

    $probation = MembershipEnforcement::create([
        'user_id' => $member->id,
        'admin_id' => $administrator->id,
        'action' => 'probation',
        'status' => 'active',
        'reason' => 'The member requires formal membership probation.',
        'starts_at' => now()->subDays(100),
        'eligible_for_review_at' => $originalReviewDate,
        'review_requested_at' => $originalReviewRequestedAt,
        'restoration_requirements' => 'Demonstrate continued compliance with IRDI standards.',
    ]);

    $this->actingAs($administrator);

    $component = Livewire::test('admin.members.show', ['user' => $member]);

    /*
     * Simulate another request resolving probation while this
     * component still holds stale actionable state.
     */
    $probation->update([
        'status' => 'resolved',
        'resolved_at' => now(),
        'resolved_by' => $administrator->id,
    ]);

    $originalResolvedAt = $probation->resolved_at;
    $originalResolvedBy = $probation->resolved_by;

    $component
        ->set(
            'probationContinueReason',
            'This stale request must not continue an already resolved probation.'
        )
        ->call('continueProbation', $probation->id)
        ->assertHasErrors(['probationContinueReason']);

    $probation->refresh();

    expect($member->fresh()->membership_status)->toBe('active')
        ->and($probation->status)->toBe('resolved')
        ->and($probation->resolved_at?->toDateTimeString())
        ->toBe($originalResolvedAt?->toDateTimeString())
        ->and($probation->resolved_by)->toBe($originalResolvedBy)
        ->and($probation->eligible_for_review_at?->toDateTimeString())
        ->toBe($originalReviewDate->toDateTimeString())
        ->and($probation->review_requested_at?->toDateTimeString())
        ->toBe($originalReviewRequestedAt->toDateTimeString());

    $this->assertDatabaseMissing('staff_activity_logs', [
        'target_user_id' => $member->id,
        'action' => 'membership_probation_continued',
    ]);

    Notification::assertNotSentTo(
        $member,
        MembershipProbationContinuedNotification::class,
    );
});

test('probation continuation rolls back completely when staff activity creation fails', function () {
    Notification::fake();

    $administrator = User::factory()->create([
        'is_admin' => true,
        'email' => 'probation-continuation-rollback-admin@example.com',
    ]);

    $member = User::factory()->create([
        'membership_status' => 'active',
        'email' => 'probation-continuation-rollback-member@example.com',
    ]);

    $originalReviewDate = now()->subDays(10)->startOfSecond();
    $originalReviewRequestedAt = now()->subDay()->startOfSecond();

    $probation = MembershipEnforcement::create([
        'user_id' => $member->id,
        'admin_id' => $administrator->id,
        'action' => 'probation',
        'status' => 'active',
        'reason' => 'The member requires formal membership probation.',
        'starts_at' => now()->subDays(100),
        'eligible_for_review_at' => $originalReviewDate,
        'review_requested_at' => $originalReviewRequestedAt,
        'restoration_requirements' => 'Demonstrate continued compliance with IRDI standards.',
    ]);

    StaffActivityLog::creating(function (StaffActivityLog $activity): void {
        if ($activity->action === 'membership_probation_continued') {
            throw new RuntimeException(
                'Forced staff activity failure during probation continuation.'
            );
        }
    });

    $this->actingAs($administrator);

    expect(fn () => Livewire::test('admin.members.show', ['user' => $member])
        ->set(
            'probationContinueReason',
            'The member requires an additional probation review period.'
        )
        ->call('continueProbation', $probation->id)
    )->toThrow(
        RuntimeException::class,
        'Forced staff activity failure during probation continuation.'
    );

    $probation->refresh();

    expect($member->fresh()->membership_status)->toBe('active')
        ->and($probation->status)->toBe('active')
        ->and($probation->eligible_for_review_at?->toDateTimeString())
        ->toBe($originalReviewDate->toDateTimeString())
        ->and($probation->review_requested_at?->toDateTimeString())
        ->toBe($originalReviewRequestedAt->toDateTimeString())
        ->and($probation->resolved_at)->toBeNull()
        ->and($probation->resolved_by)->toBeNull();

    $this->assertDatabaseMissing('staff_activity_logs', [
        'target_user_id' => $member->id,
        'action' => 'membership_probation_continued',
    ]);

    Notification::assertNotSentTo(
        $member,
        MembershipProbationContinuedNotification::class,
    );
});

test('probation continuation preserves historical probation evidence', function () {
    Notification::fake();
    $this->freezeTime();

    $originalAdministrator = User::factory()->create([
        'is_admin' => true,
        'email' => 'probation-continuation-history-original-admin@example.com',
    ]);

    $continuingAdministrator = User::factory()->create([
        'is_admin' => true,
        'email' => 'probation-continuation-history-continuing-admin@example.com',
    ]);

    $member = User::factory()->create([
        'membership_status' => 'active',
        'email' => 'probation-continuation-history-member@example.com',
    ]);

    $startsAt = now()->subDays(120);
    $originalReviewDate = now()->subDays(10);
    $originalReviewRequestedAt = now()->subDay();

    $probation = MembershipEnforcement::create([
        'user_id' => $member->id,
        'admin_id' => $originalAdministrator->id,
        'action' => 'probation',
        'status' => 'active',
        'reason' => 'Original probation reason that must remain part of the historical record.',
        'starts_at' => $startsAt,
        'eligible_for_review_at' => $originalReviewDate,
        'review_requested_at' => $originalReviewRequestedAt,
        'restoration_requirements' => 'Complete the original probation requirements and maintain compliance with IRDI standards.',
    ]);

    $originalId = $probation->id;
    $originalAdminId = $probation->admin_id;
    $originalReason = $probation->reason;
    $originalStartsAt = $probation->starts_at;
    $originalRequirements = $probation->restoration_requirements;

    $this->actingAs($continuingAdministrator);

    Livewire::test('admin.members.show', ['user' => $member])
        ->set(
            'probationContinueReason',
            'Additional observation is required before probation can be resolved.'
        )
        ->call('continueProbation', $probation->id)
        ->assertHasNoErrors();

    $probation->refresh();

    $nextReviewDate = now()->addDays(90);

    expect($probation->id)->toBe($originalId)
        ->and($probation->admin_id)->toBe($originalAdminId)
        ->and($probation->action)->toBe('probation')
        ->and($probation->reason)->toBe($originalReason)
        ->and($probation->starts_at?->toDateTimeString())
        ->toBe($originalStartsAt?->toDateTimeString())
        ->and($probation->restoration_requirements)->toBe($originalRequirements)
        ->and($probation->status)->toBe('active')
        ->and($probation->resolved_at)->toBeNull()
        ->and($probation->resolved_by)->toBeNull()
        ->and($probation->eligible_for_review_at?->toDateTimeString())
        ->toBe($nextReviewDate->toDateTimeString())
        ->and($probation->review_requested_at)->toBeNull();

    expect($member->fresh()->membership_status)->toBe('active')
        ->and($member->fresh()->isOnProbation())->toBeTrue();

    $activity = StaffActivityLog::query()
        ->where('actor_id', $continuingAdministrator->id)
        ->where('target_user_id', $member->id)
        ->where('action', 'membership_probation_continued')
        ->where('subject_type', $probation->getMorphClass())
        ->where('subject_id', $probation->id)
        ->sole();

    expect($activity->metadata['probation_id'])->toBe($probation->id)
        ->and($activity->metadata['reason'])
        ->toBe('Additional observation is required before probation can be resolved.')
        ->and($activity->metadata['previous_eligible_for_review_at'])
        ->toBe($originalReviewDate->toIso8601String())
        ->and($activity->metadata['previous_review_requested_at'])
        ->toBe($originalReviewRequestedAt->toIso8601String())
        ->and($activity->metadata['next_eligible_for_review_at'])
        ->toBe($nextReviewDate->toIso8601String());

    Notification::assertSentToTimes(
        $member,
        MembershipProbationContinuedNotification::class,
        1
    );
});
