<?php

use App\Models\BanIdentifier;
use App\Models\MembershipEnforcement;
use App\Models\User;
use App\Notifications\MembershipBannedNotification;
use App\Notifications\MembershipProbationNotification;
use App\Notifications\MembershipRestoredNotification;
use App\Notifications\MembershipSuspendedNotification;
use App\Notifications\MembershipSuspensionContinuedNotification;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Livewire\Livewire;

uses(RefreshDatabase::class);

function suspensionUser(array $attributes = []): User
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

test('administrator can suspend an active membership', function () {
    Notification::fake();
    $admin = suspensionUser(['is_admin' => true]);
    $member = suspensionUser();

    $this->actingAs($admin);

    Livewire::test('admin.members.show', ['user' => $member])
        ->set(
            'suspensionReason',
            'A documented membership concern requires a temporary membership suspension.'
        )
        ->set('suspensionReviewPeriod', '90')
        ->call('suspendMembership')
        ->assertHasNoErrors();

    expect($member->fresh()->membership_status)->toBe('suspended');

    $this->assertDatabaseHas('membership_enforcements', [
        'user_id' => $member->id,
        'admin_id' => $admin->id,
        'action' => 'suspended',
        'status' => 'active',
    ]);

    Notification::assertSentTo(
        $member,
        MembershipSuspendedNotification::class,
    );
});

test('ordinary suspension requires a valid reason', function () {
    $admin = suspensionUser(['is_admin' => true]);
    $member = suspensionUser();

    $this->actingAs($admin);

    Livewire::test('admin.members.show', ['user' => $member])
        ->set('suspensionReason', 'Too short')
        ->set('suspensionReviewPeriod', '90')
        ->call('suspendMembership')
        ->assertHasErrors(['suspensionReason']);

    expect($member->fresh()->membership_status)->toBe('active');
});

test('ordinary suspension requires a valid review period', function () {
    $admin = suspensionUser(['is_admin' => true]);
    $member = suspensionUser();

    $this->actingAs($admin);

    Livewire::test('admin.members.show', ['user' => $member])
        ->set(
            'suspensionReason',
            'A documented membership concern requires a temporary membership suspension.'
        )
        ->set('suspensionReviewPeriod', '45')
        ->call('suspendMembership')
        ->assertHasErrors(['suspensionReviewPeriod']);

    expect($member->fresh()->membership_status)->toBe('active');
});

test('ordinary suspension stores the selected formal review date', function (string $period, int $days) {
    $admin = suspensionUser(['is_admin' => true]);
    $member = suspensionUser();

    $this->actingAs($admin);

    $this->freezeTime();

    Livewire::test('admin.members.show', ['user' => $member])
        ->set(
            'suspensionReason',
            'A documented membership concern requires a temporary membership suspension.'
        )
        ->set('suspensionReviewPeriod', $period)
        ->call('suspendMembership')
        ->assertHasNoErrors();

    $suspension = MembershipEnforcement::query()
        ->where('user_id', $member->id)
        ->where('action', 'suspended')
        ->latest('id')
        ->firstOrFail();

    expect($suspension->status)->toBe('active')
        ->and($suspension->starts_at)->not->toBeNull()
        ->and($suspension->eligible_for_review_at)->not->toBeNull()
        ->and(
            $suspension->eligible_for_review_at->toDateTimeString()
        )->toBe(
            now()->addDays($days)->toDateTimeString()
        );
})->with([
    '30 days' => ['30', 30],
    '90 days' => ['90', 90],
    '180 days' => ['180', 180],
]);

test('ordinary indefinite suspension has no formal review date', function () {
    $admin = suspensionUser(['is_admin' => true]);
    $member = suspensionUser();

    $this->actingAs($admin);

    Livewire::test('admin.members.show', ['user' => $member])
        ->set(
            'suspensionReason',
            'A documented membership concern requires an indefinite membership suspension.'
        )
        ->set('suspensionReviewPeriod', 'indefinite')
        ->call('suspendMembership')
        ->assertHasNoErrors();

    $suspension = MembershipEnforcement::query()
        ->where('user_id', $member->id)
        ->where('action', 'suspended')
        ->latest('id')
        ->firstOrFail();

    expect($suspension->status)->toBe('active')
        ->and($suspension->starts_at)->not->toBeNull()
        ->and($suspension->eligible_for_review_at)->toBeNull();
});

test('member cannot receive duplicate active suspension', function () {
    $admin = suspensionUser(['is_admin' => true]);
    $member = suspensionUser();

    MembershipEnforcement::create([
        'user_id' => $member->id,
        'admin_id' => $admin->id,
        'action' => 'suspended',
        'status' => 'active',
        'reason' => 'An existing suspension remains active for this member.',
        'starts_at' => now()->subDay(),
        'eligible_for_review_at' => now()->addDays(89),
    ]);

    $this->actingAs($admin);

    Livewire::test('admin.members.show', ['user' => $member])
        ->set(
            'suspensionReason',
            'Attempting to create another active suspension record.'
        )
        ->set('suspensionReviewPeriod', '90')
        ->call('suspendMembership')
        ->assertHasErrors(['suspensionReason']);

    expect(
        MembershipEnforcement::query()
            ->where('user_id', $member->id)
            ->where('action', 'suspended')
            ->where('status', 'active')
            ->whereNull('resolved_at')
            ->count()
    )->toBe(1);
});

test('moderator cannot suspend a membership', function () {
    $moderator = suspensionUser(['is_moderator' => true]);
    $member = suspensionUser();

    $this->actingAs($moderator);

    Livewire::test('admin.members.show', ['user' => $member])
        ->set(
            'suspensionReason',
            'A moderator must not be permitted to issue a formal membership suspension.'
        )
        ->set('suspensionReviewPeriod', '90')
        ->call('suspendMembership')
        ->assertStatus(403);

    expect($member->fresh()->membership_status)->toBe('active');
});

test('administrator can restore a suspended membership', function () {
    Notification::fake();
    $admin = suspensionUser(['is_admin' => true]);
    $member = suspensionUser(['membership_status' => 'suspended']);

    $suspension = MembershipEnforcement::create([
        'user_id' => $member->id,
        'admin_id' => $admin->id,
        'action' => 'suspended',
        'status' => 'active',
        'reason' => 'The member was suspended while corrective requirements were addressed.',
        'starts_at' => now()->subDays(100),
        'eligible_for_review_at' => now()->subDays(10),
    ]);

    $this->actingAs($admin);

    Livewire::test('admin.members.show', ['user' => $member])
        ->set(
            'membershipRestoreReason',
            'The suspension has been reviewed and the membership may now be restored.'
        )
        ->call('restoreMembership')
        ->assertHasNoErrors();

    $suspension->refresh();

    expect($member->fresh()->membership_status)->toBe('active')
        ->and($suspension->status)->toBe('resolved')
        ->and($suspension->resolved_at)->not->toBeNull()
        ->and($suspension->resolved_by)->toBe($admin->id);

    $this->assertDatabaseHas('membership_enforcements', [
        'id' => $suspension->id,
        'action' => 'suspended',
        'status' => 'resolved',
        'resolved_by' => $admin->id,
    ]);

    $this->assertDatabaseHas('membership_enforcements', [
        'user_id' => $member->id,
        'admin_id' => $admin->id,
        'action' => 'restored',
    ]);

    Notification::assertSentTo(
        $member,
        MembershipRestoredNotification::class,
    );
});

test('restoring membership resolves all active suspension records for the member', function () {
    $admin = suspensionUser(['is_admin' => true]);
    $member = suspensionUser(['membership_status' => 'suspended']);

    $firstSuspension = MembershipEnforcement::create([
        'user_id' => $member->id,
        'admin_id' => $admin->id,
        'action' => 'suspended',
        'status' => 'active',
        'reason' => 'The original suspension remains active.',
        'starts_at' => now()->subDays(120),
        'eligible_for_review_at' => now()->subDays(30),
    ]);

    $secondSuspension = MembershipEnforcement::create([
        'user_id' => $member->id,
        'admin_id' => $admin->id,
        'action' => 'suspended',
        'status' => 'active',
        'reason' => 'A duplicate historical suspension record exists.',
        'starts_at' => now()->subDays(90),
        'eligible_for_review_at' => now()->subDay(),
    ]);

    $this->actingAs($admin);

    Livewire::test('admin.members.show', ['user' => $member])
        ->set(
            'membershipRestoreReason',
            'The suspension review is complete and membership may be restored.'
        )
        ->call('restoreMembership')
        ->assertHasNoErrors();

    $member->refresh();
    $firstSuspension->refresh();
    $secondSuspension->refresh();

    expect($member->membership_status)->toBe('active')
        ->and($firstSuspension->status)->toBe('resolved')
        ->and($firstSuspension->resolved_at)->not->toBeNull()
        ->and($firstSuspension->resolved_by)->toBe($admin->id)
        ->and($secondSuspension->status)->toBe('resolved')
        ->and($secondSuspension->resolved_at)->not->toBeNull()
        ->and($secondSuspension->resolved_by)->toBe($admin->id);

    expect(
        MembershipEnforcement::query()
            ->where('user_id', $member->id)
            ->where('action', 'suspended')
            ->where('status', 'active')
            ->whereNull('resolved_at')
            ->count()
    )->toBe(0);
});

test('suspension cannot be restored through a different member component', function () {
    $admin = suspensionUser(['is_admin' => true]);
    $member = suspensionUser(['membership_status' => 'suspended']);
    $otherMember = suspensionUser(['membership_status' => 'suspended']);

    $suspension = MembershipEnforcement::create([
        'user_id' => $member->id,
        'admin_id' => $admin->id,
        'action' => 'suspended',
        'status' => 'active',
        'reason' => 'This suspension belongs to a different member.',
        'starts_at' => now()->subDays(90),
        'eligible_for_review_at' => now()->subDay(),
    ]);

    $this->actingAs($admin);

    Livewire::test('admin.members.show', ['user' => $otherMember])
        ->set(
            'membershipRestoreReason',
            'Attempting to restore membership through the wrong member record.'
        )
        ->call('restoreMembership')
        ->assertHasNoErrors();

    $suspension->refresh();

    expect($member->fresh()->membership_status)->toBe('suspended')
        ->and($suspension->status)->toBe('active')
        ->and($suspension->resolved_at)->toBeNull();
});

test('administrator cannot restore membership without a valid reason', function () {
    $admin = suspensionUser(['is_admin' => true]);
    $member = suspensionUser(['membership_status' => 'suspended']);

    MembershipEnforcement::create([
        'user_id' => $member->id,
        'admin_id' => $admin->id,
        'action' => 'suspended',
        'status' => 'active',
        'reason' => 'This suspension is still active.',
        'starts_at' => now()->subDays(30),
        'eligible_for_review_at' => now(),
    ]);

    $this->actingAs($admin);

    Livewire::test('admin.members.show', ['user' => $member])
        ->set('membershipRestoreReason', 'Too short')
        ->call('restoreMembership')
        ->assertHasErrors(['membershipRestoreReason']);

    expect($member->fresh()->membership_status)->toBe('suspended');
});

test('moderator cannot restore a suspended membership', function () {
    $admin = suspensionUser(['is_admin' => true]);
    $moderator = suspensionUser(['is_moderator' => true]);
    $member = suspensionUser(['membership_status' => 'suspended']);

    MembershipEnforcement::create([
        'user_id' => $member->id,
        'admin_id' => $admin->id,
        'action' => 'suspended',
        'status' => 'active',
        'reason' => 'This active suspension must only be resolved by authorized staff.',
        'starts_at' => now()->subDays(30),
        'eligible_for_review_at' => now(),
    ]);

    $this->actingAs($moderator);

    Livewire::test('admin.members.show', ['user' => $member])
        ->set(
            'membershipRestoreReason',
            'A moderator must not be permitted to restore formal membership suspension.'
        )
        ->call('restoreMembership')
        ->assertStatus(403);

    expect($member->fresh()->membership_status)->toBe('suspended');
});

test('passing a suspension review date does not automatically restore membership', function () {
    $admin = suspensionUser(['is_admin' => true]);
    $member = suspensionUser(['membership_status' => 'suspended']);

    $suspension = MembershipEnforcement::create([
        'user_id' => $member->id,
        'admin_id' => $admin->id,
        'action' => 'suspended',
        'status' => 'active',
        'reason' => 'This suspension requires a human review before any membership restoration.',
        'starts_at' => now()->subDays(120),
        'eligible_for_review_at' => now()->subDays(30),
    ]);

    expect($member->fresh()->membership_status)->toBe('suspended')
        ->and($suspension->fresh()->status)->toBe('active')
        ->and($suspension->fresh()->resolved_at)->toBeNull();
});

test('administrator can continue an active suspension with a new formal review date', function () {
    Notification::fake();
    $admin = suspensionUser(['is_admin' => true]);
    $member = suspensionUser(['membership_status' => 'suspended']);

    $this->freezeTime();

    $suspension = MembershipEnforcement::create([
        'user_id' => $member->id,
        'admin_id' => $admin->id,
        'action' => 'suspended',
        'status' => 'active',
        'reason' => 'The member remains suspended pending a later formal membership review.',
        'starts_at' => now()->subDays(90),
        'eligible_for_review_at' => now(),
        'review_requested_at' => now()->subDay(),
    ]);

    $this->actingAs($admin);

    Livewire::test('admin.members.show', ['user' => $member])
        ->set(
            'suspensionContinueReason',
            'The underlying concerns remain unresolved and additional suspension time is required.'
        )
        ->set('suspensionContinuePeriod', '180')
        ->call('continueSuspension', $suspension->id)
        ->assertHasNoErrors();

    $suspension->refresh();

    expect($member->fresh()->membership_status)->toBe('suspended')
        ->and($suspension->status)->toBe('active')
        ->and($suspension->resolved_at)->toBeNull()
        ->and($suspension->review_requested_at)->toBeNull()
        ->and($suspension->eligible_for_review_at?->toDateTimeString())
        ->toBe(now()->addDays(180)->toDateTimeString());

    $this->assertDatabaseHas('staff_activity_logs', [
        'target_user_id' => $member->id,
        'action' => 'membership_suspension_continued',
        'subject_type' => MembershipEnforcement::class,
        'subject_id' => $suspension->id,
    ]);

    Notification::assertSentTo(
        $member,
        MembershipSuspensionContinuedNotification::class,
        function (MembershipSuspensionContinuedNotification $notification) {
            return $notification->reason ===
                'The underlying concerns remain unresolved and additional suspension time is required.'
                && $notification->eligibleForReviewAt?->toDateTimeString() ===
                now()->addDays(180)->toDateTimeString();
        }
    );
});

test('suspension cannot be continued through a different member component', function () {
    $admin = suspensionUser(['is_admin' => true]);
    $member = suspensionUser(['membership_status' => 'suspended']);
    $otherMember = suspensionUser(['membership_status' => 'suspended']);

    $suspension = MembershipEnforcement::create([
        'user_id' => $member->id,
        'admin_id' => $admin->id,
        'action' => 'suspended',
        'status' => 'active',
        'reason' => 'This suspension belongs to a different member.',
        'starts_at' => now()->subDays(90),
        'eligible_for_review_at' => now()->subDay(),
    ]);

    $this->actingAs($admin);

    expect(fn () => Livewire::test('admin.members.show', ['user' => $otherMember])
        ->set(
            'suspensionContinueReason',
            'Attempting to continue another member suspension record.'
        )
        ->set('suspensionContinuePeriod', '90')
        ->call('continueSuspension', $suspension->id))
        ->toThrow(ModelNotFoundException::class);

    $suspension->refresh();

    expect($suspension->status)->toBe('active')
        ->and($suspension->resolved_at)->toBeNull()
        ->and($suspension->eligible_for_review_at?->isPast())->toBeTrue();
});

test('administrator can continue a suspension indefinitely', function () {
    Notification::fake();
    $admin = suspensionUser(['is_admin' => true]);
    $member = suspensionUser(['membership_status' => 'suspended']);

    $suspension = MembershipEnforcement::create([
        'user_id' => $member->id,
        'admin_id' => $admin->id,
        'action' => 'suspended',
        'status' => 'active',
        'reason' => 'The suspension is currently awaiting a formal administrative decision.',
        'starts_at' => now()->subDays(90),
        'eligible_for_review_at' => now(),
        'review_requested_at' => now()->subDay(),
    ]);

    $this->actingAs($admin);

    Livewire::test('admin.members.show', ['user' => $member])
        ->set(
            'suspensionContinueReason',
            'The suspension must remain in effect without a scheduled review date at this time.'
        )
        ->set('suspensionContinuePeriod', 'indefinite')
        ->call('continueSuspension', $suspension->id)
        ->assertHasNoErrors();

    $suspension->refresh();

    expect($member->fresh()->membership_status)->toBe('suspended')
        ->and($suspension->status)->toBe('active')
        ->and($suspension->eligible_for_review_at)->toBeNull()
        ->and($suspension->review_requested_at)->toBeNull();

    Notification::assertSentTo(
        $member,
        MembershipSuspensionContinuedNotification::class,
        function (MembershipSuspensionContinuedNotification $notification) {
            return $notification->reason ===
                'The suspension must remain in effect without a scheduled review date at this time.'
                && $notification->eligibleForReviewAt === null;
        }
    );
});

test('administrator can resolve suspension to probation', function () {
    Notification::fake();
    $admin = suspensionUser(['is_admin' => true]);
    $member = suspensionUser(['membership_status' => 'suspended']);

    $this->freezeTime();

    $suspension = MembershipEnforcement::create([
        'user_id' => $member->id,
        'admin_id' => $admin->id,
        'action' => 'suspended',
        'status' => 'active',
        'reason' => 'The member was suspended while serious membership concerns were reviewed.',
        'starts_at' => now()->subDays(120),
        'eligible_for_review_at' => now()->subDay(),
    ]);

    $this->actingAs($admin);

    Livewire::test('admin.members.show', ['user' => $member])
        ->set(
            'probationReason',
            'The member may return to active membership under a formal probationary period.'
        )
        ->set(
            'probationDetails',
            'The suspension review supports a controlled return with continued accountability.'
        )
        ->set(
            'probationRequirements',
            'Complete the required corrective actions and maintain compliance throughout probation.'
        )
        ->set('probationReviewDays', '90')
        ->call('resolveSuspensionWithProbation', $suspension->id)
        ->assertHasNoErrors();

    $member->refresh();
    $suspension->refresh();

    $probation = MembershipEnforcement::query()
        ->where('user_id', $member->id)
        ->where('action', 'probation')
        ->where('status', 'active')
        ->latest('id')
        ->firstOrFail();

    expect($member->membership_status)->toBe('active')
        ->and($member->isOnProbation())->toBeTrue()
        ->and($member->isInGoodStanding())->toBeFalse()
        ->and($suspension->status)->toBe('resolved')
        ->and($suspension->resolved_at)->not->toBeNull()
        ->and($suspension->resolved_by)->toBe($admin->id)
        ->and($probation->eligible_for_review_at?->toDateTimeString())
        ->toBe(now()->addDays(90)->toDateTimeString());

    $this->assertDatabaseHas('staff_activity_logs', [
        'target_user_id' => $member->id,
        'action' => 'membership_suspension_resolved_probation',
        'subject_type' => MembershipEnforcement::class,
        'subject_id' => $suspension->id,
    ]);

    $this->assertDatabaseHas('staff_activity_logs', [
        'target_user_id' => $member->id,
        'action' => 'membership_probation_started',
        'subject_type' => MembershipEnforcement::class,
        'subject_id' => $probation->id,
    ]);

    Notification::assertSentTo(
        $member,
        MembershipProbationNotification::class,
    );
});

test('suspension cannot be resolved to probation through a different member component', function () {
    $admin = suspensionUser(['is_admin' => true]);
    $member = suspensionUser(['membership_status' => 'suspended']);
    $otherMember = suspensionUser(['membership_status' => 'suspended']);

    $suspension = MembershipEnforcement::create([
        'user_id' => $member->id,
        'admin_id' => $admin->id,
        'action' => 'suspended',
        'status' => 'active',
        'reason' => 'This suspension belongs to a different member.',
        'starts_at' => now()->subDays(90),
        'eligible_for_review_at' => now()->subDay(),
    ]);

    $this->actingAs($admin);

    expect(fn () => Livewire::test('admin.members.show', ['user' => $otherMember])
        ->set(
            'probationReason',
            'Attempting to resolve another member suspension to probation.'
        )
        ->set(
            'probationRequirements',
            'Complete all documented corrective requirements before restoration.'
        )
        ->set('probationReviewDays', '90')
        ->call('resolveSuspensionWithProbation', $suspension->id))
        ->toThrow(ModelNotFoundException::class);

    $member->refresh();
    $otherMember->refresh();
    $suspension->refresh();

    expect($member->membership_status)->toBe('suspended')
        ->and($otherMember->membership_status)->toBe('suspended')
        ->and($suspension->status)->toBe('active')
        ->and($suspension->resolved_at)->toBeNull();

    $this->assertDatabaseMissing('membership_enforcements', [
        'user_id' => $otherMember->id,
        'action' => 'probation',
        'status' => 'active',
    ]);
});

test('super administrator can resolve suspension with a ban', function () {
    Notification::fake();
    $superAdmin = suspensionUser([
        'is_super_admin' => true,
        'email' => 'super-admin@example.com',
    ]);

    $member = suspensionUser([
        'membership_status' => 'suspended',
        'email' => 'suspended-member@example.com',
        'registration_ip' => '8.8.8.8',
        'last_login_ip' => '1.1.1.1',
    ]);

    $suspension = MembershipEnforcement::create([
        'user_id' => $member->id,
        'admin_id' => $superAdmin->id,
        'action' => 'suspended',
        'status' => 'active',
        'reason' => 'The membership is suspended pending consideration of permanent removal.',
        'starts_at' => now()->subDays(180),
        'eligible_for_review_at' => now()->subDay(),
    ]);

    $this->actingAs($superAdmin);

    Livewire::test('admin.members.show', ['user' => $member])
        ->set(
            'banReason',
            'The completed suspension review supports an indefinite termination of IRDI membership.'
        )
        ->call('resolveSuspensionWithBan', $suspension->id)
        ->assertHasNoErrors();

    $member->refresh();
    $suspension->refresh();

    expect($member->membership_status)->toBe('banned')
        ->and($suspension->status)->toBe('resolved')
        ->and($suspension->resolved_at)->not->toBeNull()
        ->and($suspension->resolved_by)->toBe($superAdmin->id);

    $ban = MembershipEnforcement::query()
        ->where('user_id', $member->id)
        ->where('action', 'banned')
        ->latest('id')
        ->firstOrFail();

    $this->assertDatabaseHas('ban_identifiers', [
        'user_id' => $member->id,
        'membership_enforcement_id' => $ban->id,
        'created_by' => $superAdmin->id,
        'type' => 'email',
        'value' => 'suspended-member@example.com',
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

    $this->assertDatabaseHas('staff_activity_logs', [
        'target_user_id' => $member->id,
        'action' => 'membership_suspension_resolved_ban',
        'subject_type' => MembershipEnforcement::class,
        'subject_id' => $suspension->id,
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
    );
});

test('suspension cannot be resolved with a ban through a different member component', function () {
    $superAdmin = suspensionUser([
        'is_super_admin' => true,
        'email' => 'super-admin@example.com',
    ]);

    $member = suspensionUser([
        'membership_status' => 'suspended',
        'email' => 'suspended-member@example.com',
        'registration_ip' => '8.8.8.8',
        'last_login_ip' => '1.1.1.1',
    ]);

    $otherMember = suspensionUser([
        'membership_status' => 'suspended',
        'email' => 'other-suspended-member@example.com',
    ]);

    $suspension = MembershipEnforcement::create([
        'user_id' => $member->id,
        'admin_id' => $superAdmin->id,
        'action' => 'suspended',
        'status' => 'active',
        'reason' => 'This suspension belongs to a different member.',
        'starts_at' => now()->subDays(180),
        'eligible_for_review_at' => now()->subDay(),
    ]);

    $this->actingAs($superAdmin);

    expect(fn () => Livewire::test('admin.members.show', ['user' => $otherMember])
        ->set(
            'banReason',
            'Attempting to resolve another member suspension with an IRDI ban.'
        )
        ->call('resolveSuspensionWithBan', $suspension->id))
        ->toThrow(ModelNotFoundException::class);

    $member->refresh();
    $otherMember->refresh();
    $suspension->refresh();

    expect($member->membership_status)->toBe('suspended')
        ->and($otherMember->membership_status)->toBe('suspended')
        ->and($suspension->status)->toBe('active')
        ->and($suspension->resolved_at)->toBeNull();

    $this->assertDatabaseMissing('membership_enforcements', [
        'user_id' => $otherMember->id,
        'action' => 'banned',
    ]);

    expect(
        BanIdentifier::query()
            ->where('user_id', $member->id)
            ->orWhere('user_id', $otherMember->id)
            ->exists()
    )->toBeFalse();
});

test('administrator cannot resolve a suspension with a ban', function () {
    $admin = suspensionUser(['is_admin' => true]);
    $member = suspensionUser(['membership_status' => 'suspended']);

    $suspension = MembershipEnforcement::create([
        'user_id' => $member->id,
        'admin_id' => $admin->id,
        'action' => 'suspended',
        'status' => 'active',
        'reason' => 'The membership is suspended and remains subject to formal review.',
        'starts_at' => now()->subDays(90),
        'eligible_for_review_at' => now(),
    ]);

    $this->actingAs($admin);

    Livewire::test('admin.members.show', ['user' => $member])
        ->set(
            'banReason',
            'An ordinary Administrator must not be permitted to impose an IRDI membership ban.'
        )
        ->call('banMember')
        ->assertStatus(403);

    expect($member->fresh()->membership_status)->toBe('suspended')
        ->and($suspension->fresh()->status)->toBe('active');

    $this->assertDatabaseMissing('membership_enforcements', [
        'user_id' => $member->id,
        'action' => 'banned',
    ]);

    expect(
        BanIdentifier::query()
            ->where('user_id', $member->id)
            ->exists()
    )->toBeFalse();
});
