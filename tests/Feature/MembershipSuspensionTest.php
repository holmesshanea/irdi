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
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Schema;
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

test('super administrator can resolve administrative review with a ban', function () {
    Notification::fake();

    $superAdmin = suspensionUser([
        'is_super_admin' => true,
        'email' => 'super-admin@example.com',
    ]);

    $member = suspensionUser([
        'membership_status' => 'active',
        'email' => 'administrative-review-member@example.com',
        'registration_ip' => '8.8.8.8',
        'last_login_ip' => '1.1.1.1',
    ]);

    $hold = MembershipEnforcement::create([
        'user_id' => $member->id,
        'admin_id' => $superAdmin->id,
        'action' => 'administrative_hold',
        'status' => 'active',
        'reason' => 'Serious concerns require formal administrative review before a final membership decision.',
        'starts_at' => now()->subDays(10),
    ]);

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

    expect($member->membership_status)->toBe('banned')
        ->and($hold->status)->toBe('resolved')
        ->and($hold->resolved_at)->not->toBeNull()
        ->and($hold->resolved_by)->toBe($superAdmin->id);

    $this->assertDatabaseHas('membership_enforcements', [
        'user_id' => $member->id,
        'action' => 'banned',
        'status' => 'active',
        'reason' => 'The completed administrative review supports permanent removal from IRDI.',
    ]);

    $this->assertDatabaseHas('staff_activity_logs', [
        'target_user_id' => $member->id,
        'action' => 'membership_administrative_hold_resolved_ban',
        'subject_type' => MembershipEnforcement::class,
        'subject_id' => $hold->id,
    ]);
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

test('stale component state cannot suspend a member whose membership is no longer active', function () {
    Notification::fake();

    $admin = suspensionUser([
        'is_admin' => true,
        'email' => 'admin@example.com',
    ]);

    $member = suspensionUser([
        'membership_status' => 'active',
        'email' => 'member@example.com',
    ]);

    $this->actingAs($admin);

    $component = Livewire::test('admin.members.show', [
        'user' => $member,
    ]);

    // Simulate another request changing the authoritative membership
    // state after this Livewire component was mounted.
    $member->newQuery()
        ->whereKey($member->id)
        ->update([
            'membership_status' => 'banned',
        ]);

    // The component still holds the stale "active" User instance.
    expect($member->membership_status)->toBe('active');

    $component
        ->set('suspensionReason', 'This stale request must not overwrite the newer membership state.')
        ->set('suspensionReviewPeriod', '90')
        ->call('suspendMembership');

    $member->refresh();

    expect($member->membership_status)->toBe('banned');

    $this->assertDatabaseMissing('membership_enforcements', [
        'user_id' => $member->id,
        'action' => 'suspended',
        'status' => 'active',
    ]);

    Notification::assertNothingSent();
});

test('stale component state cannot restore a suspended member whose membership is now banned', function () {
    Notification::fake();

    $admin = suspensionUser([
        'is_admin' => true,
        'email' => 'admin@example.com',
    ]);

    $member = suspensionUser([
        'membership_status' => 'suspended',
        'email' => 'member@example.com',
    ]);

    $suspension = MembershipEnforcement::create([
        'user_id' => $member->id,
        'admin_id' => $admin->id,
        'action' => 'suspended',
        'status' => 'active',
        'reason' => 'Original suspension reason.',
        'starts_at' => now()->subDays(30),
        'eligible_for_review_at' => now()->addDays(60),
    ]);

    $this->actingAs($admin);

    $component = Livewire::test('admin.members.show', [
        'user' => $member,
    ]);

    // Simulate another disciplinary request winning after this
    // Livewire component was mounted.
    $member->newQuery()
        ->whereKey($member->id)
        ->update([
            'membership_status' => 'banned',
        ]);

    // Prove the component-side model is stale.
    expect($member->membership_status)->toBe('suspended');

    $component->call('restoreMembership');

    $member->refresh();
    $suspension->refresh();

    expect($member->membership_status)->toBe('banned')
        ->and($suspension->status)->toBe('active')
        ->and($suspension->resolved_at)->toBeNull()
        ->and($suspension->resolved_by)->toBeNull();

    $this->assertDatabaseMissing('membership_enforcements', [
        'user_id' => $member->id,
        'action' => 'restored',
    ]);

    Notification::assertNothingSent();
});

test('stale component state cannot restore an already resolved suspension', function () {
    Notification::fake();

    $admin = suspensionUser([
        'is_admin' => true,
        'email' => 'admin@example.com',
    ]);

    $member = suspensionUser([
        'membership_status' => 'suspended',
        'email' => 'member@example.com',
    ]);

    $suspension = MembershipEnforcement::create([
        'user_id' => $member->id,
        'admin_id' => $admin->id,
        'action' => 'suspended',
        'status' => 'active',
        'reason' => 'Original suspension reason.',
        'starts_at' => now()->subDays(30),
        'eligible_for_review_at' => now()->addDays(60),
    ]);

    $this->actingAs($admin);

    $component = Livewire::test('admin.members.show', [
        'user' => $member,
    ]);

    // Simulate another request resolving the authoritative suspension
    // after this Livewire component was mounted.
    $suspension->newQuery()
        ->whereKey($suspension->id)
        ->update([
            'status' => 'resolved',
            'resolved_at' => now(),
            'resolved_by' => $admin->id,
        ]);

    // Deliberately leave the User suspended so that membership_status
    // alone cannot protect this request.
    expect($member->membership_status)->toBe('suspended');

    $component->call('restoreMembership');

    $member->refresh();
    $suspension->refresh();

    expect($member->membership_status)->toBe('suspended')
        ->and($suspension->status)->toBe('resolved')
        ->and($suspension->resolved_at)->not->toBeNull()
        ->and($suspension->resolved_by)->toBe($admin->id);

    $this->assertDatabaseMissing('membership_enforcements', [
        'user_id' => $member->id,
        'action' => 'restored',
    ]);

    Notification::assertNothingSent();
});

test('restoring membership preserves the original suspension history', function () {
    Notification::fake();

    $suspendingAdmin = suspensionUser([
        'is_admin' => true,
        'email' => 'suspending-admin@example.com',
    ]);

    $restoringAdmin = suspensionUser([
        'is_admin' => true,
        'email' => 'restoring-admin@example.com',
    ]);

    $member = suspensionUser([
        'membership_status' => 'suspended',
        'email' => 'member@example.com',
    ]);

    $startsAt = now()->subDays(45)->startOfSecond();
    $eligibleForReviewAt = now()->addDays(45)->startOfSecond();

    $suspension = MembershipEnforcement::create([
        'user_id' => $member->id,
        'admin_id' => $suspendingAdmin->id,
        'action' => 'suspended',
        'status' => 'active',
        'reason' => 'Original disciplinary reason that must remain intact.',
        'starts_at' => $startsAt,
        'eligible_for_review_at' => $eligibleForReviewAt,
    ]);

    $this->actingAs($restoringAdmin);

    Livewire::test('admin.members.show', [
        'user' => $member,
    ])
        ->set(
            'membershipRestoreReason',
            'The suspension has been reviewed and the membership may now be restored.'
        )
        ->call('restoreMembership')
        ->assertHasNoErrors();

    $member->refresh();
    $suspension->refresh();

    expect($member->membership_status)->toBe('active')
        ->and($suspension->action)->toBe('suspended')
        ->and($suspension->status)->toBe('resolved')
        ->and($suspension->reason)->toBe('Original disciplinary reason that must remain intact.')
        ->and($suspension->admin_id)->toBe($suspendingAdmin->id)
        ->and($suspension->starts_at->equalTo($startsAt))->toBeTrue()
        ->and($suspension->eligible_for_review_at->equalTo($eligibleForReviewAt))->toBeTrue()
        ->and($suspension->resolved_at)->not->toBeNull()
        ->and($suspension->resolved_by)->toBe($restoringAdmin->id);

    $this->assertDatabaseHas('membership_enforcements', [
        'user_id' => $member->id,
        'admin_id' => $restoringAdmin->id,
        'action' => 'restored',
    ]);
});

test('resolving suspension with a ban preserves the original suspension history', function () {
    Notification::fake();

    $suspendingAdmin = suspensionUser([
        'is_admin' => true,
        'email' => 'suspending-admin@example.com',
    ]);

    $superAdmin = suspensionUser([
        'is_super_admin' => true,
        'email' => 'super-admin@example.com',
    ]);

    $member = suspensionUser([
        'membership_status' => 'suspended',
        'email' => 'member@example.com',
        'registration_ip' => '8.8.8.8',
        'last_login_ip' => '1.1.1.1',
    ]);

    $startsAt = now()->subDays(180)->startOfSecond();
    $eligibleForReviewAt = now()->subDay()->startOfSecond();

    $suspension = MembershipEnforcement::create([
        'user_id' => $member->id,
        'admin_id' => $suspendingAdmin->id,
        'action' => 'suspended',
        'status' => 'active',
        'reason' => 'Original suspension reason that must remain intact.',
        'starts_at' => $startsAt,
        'eligible_for_review_at' => $eligibleForReviewAt,
    ]);

    $this->actingAs($superAdmin);

    Livewire::test('admin.members.show', [
        'user' => $member,
    ])
        ->set(
            'banReason',
            'The completed suspension review supports an indefinite termination of IRDI membership.'
        )
        ->call('resolveSuspensionWithBan', $suspension->id)
        ->assertHasNoErrors();

    $member->refresh();
    $suspension->refresh();

    expect($member->membership_status)->toBe('banned')
        ->and($suspension->action)->toBe('suspended')
        ->and($suspension->status)->toBe('resolved')
        ->and($suspension->reason)->toBe('Original suspension reason that must remain intact.')
        ->and($suspension->admin_id)->toBe($suspendingAdmin->id)
        ->and($suspension->starts_at->equalTo($startsAt))->toBeTrue()
        ->and($suspension->eligible_for_review_at->equalTo($eligibleForReviewAt))->toBeTrue()
        ->and($suspension->resolved_at)->not->toBeNull()
        ->and($suspension->resolved_by)->toBe($superAdmin->id);

    $this->assertDatabaseHas('membership_enforcements', [
        'user_id' => $member->id,
        'admin_id' => $superAdmin->id,
        'action' => 'banned',
        'status' => 'active',
        'reason' => 'The completed suspension review supports an indefinite termination of IRDI membership.',
    ]);
});

test('resolving suspension to probation preserves the original suspension history', function () {
    Notification::fake();

    $suspendingAdmin = suspensionUser([
        'is_admin' => true,
        'email' => 'suspending-admin@example.com',
    ]);

    $resolvingAdmin = suspensionUser([
        'is_admin' => true,
        'email' => 'resolving-admin@example.com',
    ]);

    $member = suspensionUser([
        'membership_status' => 'suspended',
        'email' => 'member@example.com',
    ]);

    $startsAt = now()->subDays(120)->startOfSecond();
    $eligibleForReviewAt = now()->subDay()->startOfSecond();

    $suspension = MembershipEnforcement::create([
        'user_id' => $member->id,
        'admin_id' => $suspendingAdmin->id,
        'action' => 'suspended',
        'status' => 'active',
        'reason' => 'Original suspension reason that must remain intact.',
        'starts_at' => $startsAt,
        'eligible_for_review_at' => $eligibleForReviewAt,
    ]);

    $this->actingAs($resolvingAdmin);

    Livewire::test('admin.members.show', [
        'user' => $member,
    ])
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

    expect($member->membership_status)->toBe('active')
        ->and($suspension->action)->toBe('suspended')
        ->and($suspension->status)->toBe('resolved')
        ->and($suspension->reason)->toBe('Original suspension reason that must remain intact.')
        ->and($suspension->admin_id)->toBe($suspendingAdmin->id)
        ->and($suspension->starts_at->equalTo($startsAt))->toBeTrue()
        ->and($suspension->eligible_for_review_at->equalTo($eligibleForReviewAt))->toBeTrue()
        ->and($suspension->resolved_at)->not->toBeNull()
        ->and($suspension->resolved_by)->toBe($resolvingAdmin->id);

    $this->assertDatabaseHas('membership_enforcements', [
        'user_id' => $member->id,
        'admin_id' => $resolvingAdmin->id,
        'action' => 'probation',
        'status' => 'active',
        'reason' => 'The member may return to active membership under a formal probationary period.',
    ]);
});

test('failed suspension staff activity rolls back the entire suspension', function () {
    Notification::fake();

    $admin = suspensionUser([
        'is_admin' => true,
        'email' => 'admin@example.com',
    ]);

    $member = suspensionUser([
        'membership_status' => 'active',
        'email' => 'member@example.com',
    ]);

    $this->actingAs($admin);

    Schema::drop('staff_activity_logs');

    try {
        Livewire::test('admin.members.show', ['user' => $member])
            ->set(
                'suspensionReason',
                'A documented membership concern requires a temporary membership suspension.'
            )
            ->set('suspensionReviewPeriod', '90')
            ->call('suspendMembership');
    } catch (Throwable) {
        // Expected: StaffActivity::record() cannot insert because
        // the staff_activity_logs table is unavailable.
    }

    expect($member->fresh()->membership_status)->toBe('active');

    $this->assertDatabaseMissing('membership_enforcements', [
        'user_id' => $member->id,
        'action' => 'suspended',
    ]);

    Notification::assertNothingSent();
});

test('failed restoration staff activity rolls back the entire restoration', function () {
    Notification::fake();

    $admin = suspensionUser([
        'is_admin' => true,
        'email' => 'admin@example.com',
    ]);

    $member = suspensionUser([
        'membership_status' => 'suspended',
        'email' => 'member@example.com',
    ]);

    $suspension = MembershipEnforcement::create([
        'user_id' => $member->id,
        'admin_id' => $admin->id,
        'action' => 'suspended',
        'status' => 'active',
        'reason' => 'The member remains under an active suspension.',
        'starts_at' => now()->subDays(100),
        'eligible_for_review_at' => now()->subDays(10),
    ]);

    $this->actingAs($admin);

    Schema::drop('staff_activity_logs');

    try {
        Livewire::test('admin.members.show', ['user' => $member])
            ->set(
                'membershipRestoreReason',
                'The suspension has been reviewed and the membership may now be restored.'
            )
            ->call('restoreMembership');
    } catch (Throwable) {
        // Expected: the staff activity insert fails inside the
        // restoration transaction.
    }

    $member->refresh();
    $suspension->refresh();

    expect($member->membership_status)->toBe('suspended')
        ->and($suspension->status)->toBe('active')
        ->and($suspension->resolved_at)->toBeNull()
        ->and($suspension->resolved_by)->toBeNull();

    $this->assertDatabaseMissing('membership_enforcements', [
        'user_id' => $member->id,
        'action' => 'restored',
    ]);

    Notification::assertNothingSent();
});

test('failed probation resolution staff activity rolls back the entire suspension resolution', function () {
    Notification::fake();

    $admin = suspensionUser([
        'is_admin' => true,
        'email' => 'admin@example.com',
    ]);

    $member = suspensionUser([
        'membership_status' => 'suspended',
        'email' => 'member@example.com',
    ]);

    $suspension = MembershipEnforcement::create([
        'user_id' => $member->id,
        'admin_id' => $admin->id,
        'action' => 'suspended',
        'status' => 'active',
        'reason' => 'The member remains under an active suspension.',
        'starts_at' => now()->subDays(120),
        'eligible_for_review_at' => now()->subDay(),
    ]);

    $this->actingAs($admin);

    Schema::drop('staff_activity_logs');

    try {
        Livewire::test('admin.members.show', ['user' => $member])
            ->set(
                'probationReason',
                'The suspension review supports a controlled return under probation.'
            )
            ->set(
                'probationDetails',
                'The member may return while remaining subject to formal review.'
            )
            ->set(
                'probationRequirements',
                'Complete all corrective requirements and maintain compliance during probation.'
            )
            ->set('probationReviewDays', '90')
            ->call('resolveSuspensionWithProbation', $suspension->id);
    } catch (Throwable) {
        // Expected: staff activity cannot be written, so the
        // suspension-to-probation transaction must roll back.
    }

    $member->refresh();
    $suspension->refresh();

    expect($member->membership_status)->toBe('suspended')
        ->and($suspension->status)->toBe('active')
        ->and($suspension->resolved_at)->toBeNull()
        ->and($suspension->resolved_by)->toBeNull();

    $this->assertDatabaseMissing('membership_enforcements', [
        'user_id' => $member->id,
        'action' => 'probation',
    ]);

    Notification::assertNothingSent();
});

test('failed ban resolution staff activity rolls back the entire suspension resolution', function () {
    Notification::fake();

    $superAdmin = suspensionUser([
        'is_super_admin' => true,
        'email' => 'super-admin@example.com',
    ]);

    $member = suspensionUser([
        'membership_status' => 'suspended',
        'email' => 'member@example.com',
        'registration_ip' => '8.8.8.8',
        'last_login_ip' => '1.1.1.1',
    ]);

    $suspension = MembershipEnforcement::create([
        'user_id' => $member->id,
        'admin_id' => $superAdmin->id,
        'action' => 'suspended',
        'status' => 'active',
        'reason' => 'The member remains under an active suspension.',
        'starts_at' => now()->subDays(180),
        'eligible_for_review_at' => now()->subDay(),
    ]);

    $this->actingAs($superAdmin);

    Schema::drop('staff_activity_logs');

    try {
        Livewire::test('admin.members.show', ['user' => $member])
            ->set(
                'banReason',
                'The completed suspension review supports an indefinite termination of IRDI membership.'
            )
            ->call('resolveSuspensionWithBan', $suspension->id);
    } catch (Throwable) {
        // Expected: staff activity cannot be written, so the
        // suspension-to-ban transaction must roll back.
    }

    $member->refresh();
    $suspension->refresh();

    expect($member->membership_status)->toBe('suspended')
        ->and($suspension->status)->toBe('active')
        ->and($suspension->resolved_at)->toBeNull()
        ->and($suspension->resolved_by)->toBeNull();

    $this->assertDatabaseMissing('membership_enforcements', [
        'user_id' => $member->id,
        'action' => 'banned',
    ]);

    $this->assertDatabaseMissing('ban_identifiers', [
        'user_id' => $member->id,
    ]);

    Notification::assertNothingSent();
});

test('late ban failure rolls back ban enforcement identifiers and suspension resolution', function () {
    Notification::fake();

    $superAdmin = suspensionUser([
        'is_super_admin' => true,
        'email' => 'super-admin@example.com',
    ]);

    $member = suspensionUser([
        'membership_status' => 'suspended',
        'email' => 'member@example.com',
        'registration_ip' => '8.8.8.8',
        'last_login_ip' => '1.1.1.1',
    ]);

    $suspension = MembershipEnforcement::create([
        'user_id' => $member->id,
        'admin_id' => $superAdmin->id,
        'action' => 'suspended',
        'status' => 'active',
        'reason' => 'The member remains under an active suspension.',
        'starts_at' => now()->subDays(180),
        'eligible_for_review_at' => now()->subDay(),
    ]);

    $this->actingAs($superAdmin);

    DB::statement("
        CREATE TRIGGER fail_member_banned_activity
        BEFORE INSERT ON staff_activity_logs
        WHEN NEW.action = 'member_banned'
        BEGIN
            SELECT RAISE(ABORT, 'forced late ban failure');
        END
    ");

    try {
        Livewire::test('admin.members.show', ['user' => $member])
            ->set(
                'banReason',
                'The completed suspension review supports an indefinite termination of IRDI membership.'
            )
            ->call('resolveSuspensionWithBan', $suspension->id);
    } catch (Throwable) {
        // Expected: the final member_banned staff activity fails
        // after the earlier ban artifacts have been written.
    } finally {
        DB::statement('DROP TRIGGER IF EXISTS fail_member_banned_activity');
    }

    $member->refresh();
    $suspension->refresh();

    expect($member->membership_status)->toBe('suspended')
        ->and($suspension->status)->toBe('active')
        ->and($suspension->resolved_at)->toBeNull()
        ->and($suspension->resolved_by)->toBeNull();

    $this->assertDatabaseMissing('membership_enforcements', [
        'user_id' => $member->id,
        'action' => 'banned',
    ]);

    $this->assertDatabaseMissing('ban_identifiers', [
        'user_id' => $member->id,
    ]);

    $this->assertDatabaseMissing('staff_activity_logs', [
        'target_user_id' => $member->id,
        'action' => 'membership_suspension_resolved_ban',
    ]);

    $this->assertDatabaseMissing('staff_activity_logs', [
        'target_user_id' => $member->id,
        'action' => 'member_banned',
    ]);

    Notification::assertNothingSent();
});

test('stale ban resolution cannot override a suspension already resolved by restoration', function () {
    Notification::fake();

    $restoringAdmin = suspensionUser([
        'is_admin' => true,
        'email' => 'restoring-admin@example.com',
    ]);

    $superAdmin = suspensionUser([
        'is_super_admin' => true,
        'email' => 'super-admin@example.com',
    ]);

    $member = suspensionUser([
        'membership_status' => 'suspended',
        'email' => 'member@example.com',
        'registration_ip' => '8.8.8.8',
        'last_login_ip' => '1.1.1.1',
    ]);

    $suspension = MembershipEnforcement::create([
        'user_id' => $member->id,
        'admin_id' => $restoringAdmin->id,
        'action' => 'suspended',
        'status' => 'active',
        'reason' => 'The member remains under an active suspension.',
        'starts_at' => now()->subDays(180),
        'eligible_for_review_at' => now()->subDay(),
    ]);

    $this->actingAs($superAdmin);

    $staleBanComponent = Livewire::test('admin.members.show', [
        'user' => $member,
    ]);

    // A different administrator resolves the suspension through restoration.
    $this->actingAs($restoringAdmin);

    Livewire::test('admin.members.show', [
        'user' => $member->fresh(),
    ])
        ->set(
            'membershipRestoreReason',
            'The suspension review supports restoration to active membership.'
        )
        ->call('restoreMembership')
        ->assertHasNoErrors();

    $member->refresh();
    $suspension->refresh();

    expect($member->membership_status)->toBe('active')
        ->and($suspension->status)->toBe('resolved');

    Notification::fake();

    // Return to the administrator whose component was mounted before
    // the restoration occurred.
    $this->actingAs($superAdmin);

    try {
        $staleBanComponent
            ->set(
                'banReason',
                'A stale suspension decision must not override the completed restoration.'
            )
            ->call('resolveSuspensionWithBan', $suspension->id);
    } catch (Throwable) {
        // An already-resolved suspension may legitimately be rejected
        // through firstOrFail().
    }

    $member->refresh();
    $suspension->refresh();

    expect($member->membership_status)->toBe('active')
        ->and($suspension->status)->toBe('resolved')
        ->and($suspension->resolved_at)->not->toBeNull()
        ->and($suspension->resolved_by)->toBe($restoringAdmin->id);

    $this->assertDatabaseMissing('membership_enforcements', [
        'user_id' => $member->id,
        'action' => 'banned',
    ]);

    $this->assertDatabaseMissing('ban_identifiers', [
        'user_id' => $member->id,
    ]);

    Notification::assertNothingSent();
});

test('stale restoration cannot override a suspension already resolved by ban', function () {
    Notification::fake();

    $restoringAdmin = suspensionUser([
        'is_admin' => true,
        'email' => 'restoring-admin@example.com',
    ]);

    $superAdmin = suspensionUser([
        'is_super_admin' => true,
        'email' => 'super-admin@example.com',
    ]);

    $member = suspensionUser([
        'membership_status' => 'suspended',
        'email' => 'member@example.com',
        'registration_ip' => '8.8.8.8',
        'last_login_ip' => '1.1.1.1',
    ]);

    $suspension = MembershipEnforcement::create([
        'user_id' => $member->id,
        'admin_id' => $restoringAdmin->id,
        'action' => 'suspended',
        'status' => 'active',
        'reason' => 'The member remains under an active suspension.',
        'starts_at' => now()->subDays(180),
        'eligible_for_review_at' => now()->subDay(),
    ]);

    $this->actingAs($restoringAdmin);

    $staleRestoreComponent = Livewire::test('admin.members.show', [
        'user' => $member,
    ]);

    // A Super Administrator resolves the suspension with a Ban.
    $this->actingAs($superAdmin);

    Livewire::test('admin.members.show', [
        'user' => $member->fresh(),
    ])
        ->set(
            'banReason',
            'The completed suspension review supports an indefinite termination of IRDI membership.'
        )
        ->call('resolveSuspensionWithBan', $suspension->id)
        ->assertHasNoErrors();

    $member->refresh();
    $suspension->refresh();

    $ban = MembershipEnforcement::query()
        ->where('user_id', $member->id)
        ->where('action', 'banned')
        ->where('status', 'active')
        ->firstOrFail();

    expect($member->membership_status)->toBe('banned')
        ->and($suspension->status)->toBe('resolved')
        ->and($ban->status)->toBe('active');

    $banIdentifierCount = BanIdentifier::query()
        ->where('user_id', $member->id)
        ->count();

    expect($banIdentifierCount)->toBeGreaterThan(0);

    // Ignore the legitimate Ban notification. From here forward we only
    // care whether the stale restoration sends anything.
    Notification::fake();

    $this->actingAs($restoringAdmin);

    $staleRestoreComponent
        ->set(
            'membershipRestoreReason',
            'This stale restoration must not override the completed Ban.'
        )
        ->call('restoreMembership');

    $member->refresh();
    $suspension->refresh();
    $ban->refresh();

    expect($member->membership_status)->toBe('banned')
        ->and($suspension->status)->toBe('resolved')
        ->and($suspension->resolved_at)->not->toBeNull()
        ->and($suspension->resolved_by)->toBe($superAdmin->id)
        ->and($ban->status)->toBe('active');

    $this->assertDatabaseMissing('membership_enforcements', [
        'user_id' => $member->id,
        'action' => 'restored',
    ]);

    expect(
        BanIdentifier::query()
            ->where('user_id', $member->id)
            ->count()
    )->toBe($banIdentifierCount);

    Notification::assertNothingSent();
});

test('stale ban resolution cannot override a suspension already resolved by probation', function () {
    Notification::fake();

    $probationAdmin = suspensionUser([
        'is_admin' => true,
        'email' => 'probation-admin@example.com',
    ]);

    $superAdmin = suspensionUser([
        'is_super_admin' => true,
        'email' => 'super-admin@example.com',
    ]);

    $member = suspensionUser([
        'membership_status' => 'suspended',
        'email' => 'member@example.com',
        'registration_ip' => '8.8.8.8',
        'last_login_ip' => '1.1.1.1',
    ]);

    $suspension = MembershipEnforcement::create([
        'user_id' => $member->id,
        'admin_id' => $probationAdmin->id,
        'action' => 'suspended',
        'status' => 'active',
        'reason' => 'The member remains under an active suspension.',
        'starts_at' => now()->subDays(180),
        'eligible_for_review_at' => now()->subDay(),
    ]);

    $this->actingAs($superAdmin);

    $staleBanComponent = Livewire::test('admin.members.show', [
        'user' => $member,
    ]);

    // A different administrator resolves the suspension through probation.
    $this->actingAs($probationAdmin);

    Livewire::test('admin.members.show', [
        'user' => $member->fresh(),
    ])
        ->set(
            'probationReason',
            'The suspension review supports a controlled return under probation.'
        )
        ->set(
            'probationDetails',
            'The member may return while remaining subject to formal review.'
        )
        ->set(
            'probationRequirements',
            'Complete all corrective requirements and maintain compliance during probation.'
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
        ->firstOrFail();

    expect($member->membership_status)->toBe('active')
        ->and($suspension->status)->toBe('resolved')
        ->and($suspension->resolved_by)->toBe($probationAdmin->id)
        ->and($probation->status)->toBe('active');

    // Ignore the legitimate probation notification. From here forward,
    // only the stale Ban attempt is under test.
    Notification::fake();

    $this->actingAs($superAdmin);

    try {
        $staleBanComponent
            ->set(
                'banReason',
                'This stale Ban decision must not override the completed probation resolution.'
            )
            ->call('resolveSuspensionWithBan', $suspension->id);
    } catch (Throwable) {
        // An already-resolved suspension may legitimately be rejected
        // through firstOrFail().
    }

    $member->refresh();
    $suspension->refresh();
    $probation->refresh();

    expect($member->membership_status)->toBe('active')
        ->and($suspension->status)->toBe('resolved')
        ->and($suspension->resolved_at)->not->toBeNull()
        ->and($suspension->resolved_by)->toBe($probationAdmin->id)
        ->and($probation->status)->toBe('active');

    $this->assertDatabaseMissing('membership_enforcements', [
        'user_id' => $member->id,
        'action' => 'banned',
    ]);

    $this->assertDatabaseMissing('ban_identifiers', [
        'user_id' => $member->id,
    ]);

    Notification::assertNothingSent();
});

test('stale probation resolution cannot override a suspension already resolved by ban', function () {
    Notification::fake();

    $probationAdmin = suspensionUser([
        'is_admin' => true,
        'email' => 'probation-admin@example.com',
    ]);

    $superAdmin = suspensionUser([
        'is_super_admin' => true,
        'email' => 'super-admin@example.com',
    ]);

    $member = suspensionUser([
        'membership_status' => 'suspended',
        'email' => 'member@example.com',
        'registration_ip' => '8.8.8.8',
        'last_login_ip' => '1.1.1.1',
    ]);

    $suspension = MembershipEnforcement::create([
        'user_id' => $member->id,
        'admin_id' => $probationAdmin->id,
        'action' => 'suspended',
        'status' => 'active',
        'reason' => 'The member remains under an active suspension.',
        'starts_at' => now()->subDays(180),
        'eligible_for_review_at' => now()->subDay(),
    ]);

    $this->actingAs($probationAdmin);

    $staleProbationComponent = Livewire::test('admin.members.show', [
        'user' => $member,
    ]);

    // A Super Administrator resolves the suspension with a Ban.
    $this->actingAs($superAdmin);

    Livewire::test('admin.members.show', [
        'user' => $member->fresh(),
    ])
        ->set(
            'banReason',
            'The completed suspension review supports an indefinite termination of IRDI membership.'
        )
        ->call('resolveSuspensionWithBan', $suspension->id)
        ->assertHasNoErrors();

    $member->refresh();
    $suspension->refresh();

    $ban = MembershipEnforcement::query()
        ->where('user_id', $member->id)
        ->where('action', 'banned')
        ->where('status', 'active')
        ->firstOrFail();

    $banIdentifierCount = BanIdentifier::query()
        ->where('user_id', $member->id)
        ->count();

    expect($member->membership_status)->toBe('banned')
        ->and($suspension->status)->toBe('resolved')
        ->and($suspension->resolved_by)->toBe($superAdmin->id)
        ->and($ban->status)->toBe('active')
        ->and($banIdentifierCount)->toBeGreaterThan(0);

    // Ignore the legitimate Ban notification. From here forward,
    // only the stale probation attempt is under test.
    Notification::fake();

    $this->actingAs($probationAdmin);

    try {
        $staleProbationComponent
            ->set(
                'probationReason',
                'This stale probation decision must not override the completed Ban.'
            )
            ->set(
                'probationDetails',
                'This stale request must not create a probation enforcement.'
            )
            ->set(
                'probationRequirements',
                'These requirements must never become an active probation.'
            )
            ->set('probationReviewDays', '90')
            ->call('resolveSuspensionWithProbation', $suspension->id);
    } catch (Throwable) {
        // An already-resolved suspension may legitimately be rejected
        // through firstOrFail().
    }

    $member->refresh();
    $suspension->refresh();
    $ban->refresh();

    expect($member->membership_status)->toBe('banned')
        ->and($suspension->status)->toBe('resolved')
        ->and($suspension->resolved_at)->not->toBeNull()
        ->and($suspension->resolved_by)->toBe($superAdmin->id)
        ->and($ban->status)->toBe('active');

    $this->assertDatabaseMissing('membership_enforcements', [
        'user_id' => $member->id,
        'action' => 'probation',
    ]);

    expect(
        BanIdentifier::query()
            ->where('user_id', $member->id)
            ->count()
    )->toBe($banIdentifierCount);

    Notification::assertNothingSent();
});

test('stale component cannot continue an already resolved suspension', function () {
    Notification::fake();

    $admin = suspensionUser([
        'is_admin' => true,
        'email' => 'admin@example.com',
    ]);

    $member = suspensionUser([
        'membership_status' => 'suspended',
        'email' => 'member@example.com',
    ]);

    $originalReviewAt = now()->addDays(30)->startOfSecond();

    $suspension = MembershipEnforcement::create([
        'user_id' => $member->id,
        'admin_id' => $admin->id,
        'action' => 'suspended',
        'status' => 'active',
        'reason' => 'The original suspension remains under review.',
        'starts_at' => now()->subDays(90),
        'eligible_for_review_at' => $originalReviewAt,
    ]);

    $this->actingAs($admin);

    $component = Livewire::test('admin.members.show', [
        'user' => $member,
    ]);

    // Simulate another request resolving the authoritative suspension
    // after this component was mounted.
    $suspension->newQuery()
        ->whereKey($suspension->id)
        ->update([
            'status' => 'resolved',
            'resolved_at' => now(),
            'resolved_by' => $admin->id,
        ]);

    // Deliberately leave the User suspended so membership_status
    // cannot be the guard that protects this request.
    expect($member->membership_status)->toBe('suspended');

    try {
        $component
            ->set(
                'suspensionContinueReason',
                'This stale continuation must not alter an already resolved suspension.'
            )
            ->set('suspensionContinuePeriod', '180')
            ->call('continueSuspension', $suspension->id);
    } catch (Throwable) {
        // An already-resolved suspension may legitimately be rejected
        // through firstOrFail().
    }

    $suspension->refresh();

    expect($member->fresh()->membership_status)->toBe('suspended')
        ->and($suspension->status)->toBe('resolved')
        ->and($suspension->resolved_at)->not->toBeNull()
        ->and($suspension->resolved_by)->toBe($admin->id)
        ->and($suspension->eligible_for_review_at?->equalTo($originalReviewAt))->toBeTrue();

    $this->assertDatabaseMissing('staff_activity_logs', [
        'target_user_id' => $member->id,
        'action' => 'membership_suspension_continued',
        'subject_id' => $suspension->id,
    ]);

    Notification::assertNothingSent();
});

test('failed continuation staff activity rolls back suspension review changes', function () {
    Notification::fake();

    $admin = suspensionUser([
        'is_admin' => true,
        'email' => 'admin@example.com',
    ]);

    $member = suspensionUser([
        'membership_status' => 'suspended',
        'email' => 'member@example.com',
    ]);

    $originalReviewAt = now()->addDays(30)->startOfSecond();
    $originalReviewRequestedAt = now()->subDay()->startOfSecond();

    $suspension = MembershipEnforcement::create([
        'user_id' => $member->id,
        'admin_id' => $admin->id,
        'action' => 'suspended',
        'status' => 'active',
        'reason' => 'The original suspension remains under formal review.',
        'starts_at' => now()->subDays(90),
        'eligible_for_review_at' => $originalReviewAt,
        'review_requested_at' => $originalReviewRequestedAt,
    ]);

    $this->actingAs($admin);

    Schema::drop('staff_activity_logs');

    try {
        Livewire::test('admin.members.show', [
            'user' => $member,
        ])
            ->set(
                'suspensionContinueReason',
                'The underlying concerns remain unresolved and additional suspension time is required.'
            )
            ->set('suspensionContinuePeriod', '180')
            ->call('continueSuspension', $suspension->id);
    } catch (Throwable) {
        // Expected: the continuation update occurs first, then
        // StaffActivity::record() fails inside the same transaction.
    }

    $member->refresh();
    $suspension->refresh();

    expect($member->membership_status)->toBe('suspended')
        ->and($suspension->status)->toBe('active')
        ->and($suspension->resolved_at)->toBeNull()
        ->and($suspension->eligible_for_review_at?->equalTo($originalReviewAt))->toBeTrue()
        ->and($suspension->review_requested_at?->equalTo($originalReviewRequestedAt))->toBeTrue();

    Notification::assertNothingSent();
});

test('continuing suspension preserves the original suspension history', function () {
    Notification::fake();

    $suspendingAdmin = suspensionUser([
        'is_admin' => true,
        'email' => 'suspending-admin@example.com',
    ]);

    $continuingAdmin = suspensionUser([
        'is_admin' => true,
        'email' => 'continuing-admin@example.com',
    ]);

    $member = suspensionUser([
        'membership_status' => 'suspended',
        'email' => 'member@example.com',
    ]);

    $this->freezeTime();

    $startsAt = now()->subDays(120)->startOfSecond();
    $originalReviewAt = now()->startOfSecond();
    $reviewRequestedAt = now()->subDay()->startOfSecond();

    $suspension = MembershipEnforcement::create([
        'user_id' => $member->id,
        'admin_id' => $suspendingAdmin->id,
        'action' => 'suspended',
        'status' => 'active',
        'reason' => 'Original suspension reason that must remain intact.',
        'starts_at' => $startsAt,
        'eligible_for_review_at' => $originalReviewAt,
        'review_requested_at' => $reviewRequestedAt,
    ]);

    $this->actingAs($continuingAdmin);

    Livewire::test('admin.members.show', [
        'user' => $member,
    ])
        ->set(
            'suspensionContinueReason',
            'The underlying concerns remain unresolved and additional suspension time is required.'
        )
        ->set('suspensionContinuePeriod', '180')
        ->call('continueSuspension', $suspension->id)
        ->assertHasNoErrors();

    $member->refresh();
    $suspension->refresh();

    expect($member->membership_status)->toBe('suspended')
        ->and($suspension->action)->toBe('suspended')
        ->and($suspension->status)->toBe('active')
        ->and($suspension->resolved_at)->toBeNull()
        ->and($suspension->reason)->toBe('Original suspension reason that must remain intact.')
        ->and($suspension->admin_id)->toBe($suspendingAdmin->id)
        ->and($suspension->starts_at->equalTo($startsAt))->toBeTrue()
        ->and($suspension->eligible_for_review_at?->toDateTimeString())
        ->toBe(now()->addDays(180)->toDateTimeString())
        ->and($suspension->review_requested_at)->toBeNull();

    $this->assertDatabaseHas('staff_activity_logs', [
        'target_user_id' => $member->id,
        'action' => 'membership_suspension_continued',
        'subject_type' => MembershipEnforcement::class,
        'subject_id' => $suspension->id,
    ]);

    Notification::assertSentTo(
        $member,
        MembershipSuspensionContinuedNotification::class,
    );
});

test('restored member cannot submit a response to a previously active suspension', function () {
    $administrator = suspensionUser([
        'is_admin' => true,
    ]);

    $member = suspensionUser([
        'membership_status' => 'suspended',
    ]);

    $suspension = MembershipEnforcement::create([
        'user_id' => $member->id,
        'admin_id' => $administrator->id,
        'action' => 'suspended',
        'status' => 'active',
        'reason' => 'Suspension under review.',
        'starts_at' => now()->subDay(),
        'eligible_for_review_at' => now()->addDays(29),
    ]);

    $this->actingAs($member);

    $component = Livewire::test('account.membership-suspension');

    // Simulate the membership being restored elsewhere after this
    // suspension interface became available.
    $member->forceFill([
        'membership_status' => 'active',
    ])->save();

    $component
        ->set(
            "responses.{$suspension->id}",
            'This response must not be accepted after my membership has been restored.'
        )
        ->call('submitResponse', $suspension->id)
        ->assertForbidden();

    $suspension->refresh();

    expect($member->fresh()->membership_status)->toBe('active')
        ->and($suspension->member_response)->toBeNull()
        ->and($suspension->member_responded_at)->toBeNull();
});

test('restored member cannot request review of a previously active suspension', function () {
    $administrator = suspensionUser([
        'is_admin' => true,
    ]);

    $member = suspensionUser([
        'membership_status' => 'suspended',
    ]);

    $suspension = MembershipEnforcement::create([
        'user_id' => $member->id,
        'admin_id' => $administrator->id,
        'action' => 'suspended',
        'status' => 'active',
        'reason' => 'Suspension under review.',
        'starts_at' => now()->subDays(31),
        'eligible_for_review_at' => now()->subDay(),
    ]);

    $this->actingAs($member);

    $component = Livewire::test('account.membership-suspension');

    // Simulate the membership being restored elsewhere after this
    // suspension interface became available.
    $member->forceFill([
        'membership_status' => 'active',
    ])->save();

    $component
        ->call('requestReview', $suspension->id)
        ->assertForbidden();

    $suspension->refresh();

    expect($member->fresh()->membership_status)->toBe('active')
        ->and($suspension->review_requested_at)->toBeNull();
});
