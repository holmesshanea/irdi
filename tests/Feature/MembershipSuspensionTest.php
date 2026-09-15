<?php

use App\Models\BanIdentifier;
use App\Models\MembershipEnforcement;
use App\Models\User;
use App\Notifications\MembershipSuspensionContinuedNotification;
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
});

test('super administrator can resolve suspension with a ban', function () {
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
        ->call('banMember')
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
        'action' => 'membership_suspension_escalated',
        'subject_type' => MembershipEnforcement::class,
        'subject_id' => $suspension->id,
    ]);

    $this->assertDatabaseHas('staff_activity_logs', [
        'target_user_id' => $member->id,
        'action' => 'member_banned',
        'subject_type' => MembershipEnforcement::class,
        'subject_id' => $ban->id,
    ]);
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
