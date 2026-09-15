
<?php

use App\Models\MembershipEnforcement;
use App\Models\User;
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

    $probation = MembershipEnforcement::create([
        'user_id' => $member->id,
        'admin_id' => $admin->id,
        'action' => 'probation',
        'status' => 'active',
        'reason' => 'Probation requires escalation because the concern remains unresolved.',
        'starts_at' => now()->subDays(100),
        'eligible_for_review_at' => now()->subDays(10),
        'restoration_requirements' => 'Complete the established corrective requirements.',
        'review_requested_at' => now()->subDay(),
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
        ->toBeFalse();

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
