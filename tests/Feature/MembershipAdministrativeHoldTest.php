<?php

use App\Models\BanIdentifier;
use App\Models\MembershipEnforcement;
use App\Models\User;
use App\Notifications\MembershipAdministrativeReviewClosedNotification;
use App\Notifications\MembershipAdministrativeReviewStartedNotification;
use App\Notifications\MembershipBannedNotification;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\Notification;
use Livewire\Livewire;

function administrativeHoldUser(array $attributes = []): User
{
    return User::factory()->create(array_merge([
        'membership_status' => 'active',
        'member_since' => now()->subYear(),
        'ethics_agreed_at' => now()->subYear(),
        'best_practices_agreed_at' => now()->subYear(),
    ], $attributes));
}

test('administrator can place ordinary member under administrative review', function () {

    Notification::fake();

    $admin = administrativeHoldUser([
        'is_admin' => true,
    ]);

    $member = administrativeHoldUser();

    $this->actingAs($admin);

    Livewire::test('admin.members.show', ['user' => $member])
        ->set(
            'administrativeHoldReason',
            'A serious concern requires investigation before any disciplinary decision is made.'
        )
        ->set(
            'administrativeHoldDetails',
            'Staff should review the available reports and supporting information.'
        )
        ->call('placeOnAdministrativeHold')
        ->assertHasNoErrors();

    $hold = MembershipEnforcement::query()
        ->where('user_id', $member->id)
        ->where('action', 'administrative_hold')
        ->firstOrFail();

    $member->refresh();

    expect($hold->status)->toBe('active')
        ->and($hold->starts_at)->not->toBeNull()
        ->and($hold->resolved_at)->toBeNull()
        ->and($hold->reason)->toBe(
            'A serious concern requires investigation before any disciplinary decision is made.'
        )
        ->and($hold->details)->toBe(
            'Staff should review the available reports and supporting information.'
        )
        ->and($member->membership_status)->toBe('active')
        ->and($member->isUnderAdministrativeReview())->toBeTrue()
        ->and($member->isInGoodStanding())->toBeFalse();

    Notification::assertSentTo(
        $member,
        MembershipAdministrativeReviewStartedNotification::class,
    );
});

test('moderator cannot place member under administrative review', function () {
    $moderator = administrativeHoldUser([
        'is_moderator' => true,
    ]);

    $member = administrativeHoldUser();

    $this->actingAs($moderator);

    Livewire::test('admin.members.show', ['user' => $member])
        ->set(
            'administrativeHoldReason',
            'This concern requires formal administrative review by authorized staff.'
        )
        ->call('placeOnAdministrativeHold')
        ->assertForbidden();

    expect($member->fresh()->isUnderAdministrativeReview())->toBeFalse();

    $this->assertDatabaseMissing('membership_enforcements', [
        'user_id' => $member->id,
        'action' => 'administrative_hold',
    ]);
});

test('administrator cannot place another administrator under administrative review', function () {
    $admin = administrativeHoldUser([
        'is_admin' => true,
    ]);

    $otherAdmin = administrativeHoldUser([
        'is_admin' => true,
    ]);

    $this->actingAs($admin);

    Livewire::test('admin.members.show', ['user' => $otherAdmin])
        ->set(
            'administrativeHoldReason',
            'This concern requires a formal administrative investigation.'
        )
        ->call('placeOnAdministrativeHold')
        ->assertForbidden();

    expect($otherAdmin->fresh()->isUnderAdministrativeReview())->toBeFalse();
});

test('super administrator can place administrator under administrative review', function () {
    $superAdmin = administrativeHoldUser([
        'is_super_admin' => true,
    ]);

    $admin = administrativeHoldUser([
        'is_admin' => true,
    ]);

    $this->actingAs($superAdmin);

    Livewire::test('admin.members.show', ['user' => $admin])
        ->set(
            'administrativeHoldReason',
            'A serious concern requires formal investigation before a decision is made.'
        )
        ->call('placeOnAdministrativeHold')
        ->assertHasNoErrors();

    expect($admin->fresh()->isUnderAdministrativeReview())->toBeTrue();
});

test('member cannot receive duplicate active administrative hold', function () {
    $admin = administrativeHoldUser([
        'is_admin' => true,
    ]);

    $member = administrativeHoldUser();

    MembershipEnforcement::create([
        'user_id' => $member->id,
        'admin_id' => $admin->id,
        'action' => 'administrative_hold',
        'status' => 'active',
        'reason' => 'An existing serious concern is already under administrative review.',
        'starts_at' => now(),
    ]);

    $this->actingAs($admin);

    Livewire::test('admin.members.show', ['user' => $member])
        ->set(
            'administrativeHoldReason',
            'A second administrative hold should not be permitted while one is active.'
        )
        ->call('placeOnAdministrativeHold')
        ->assertHasErrors(['administrativeHoldReason']);

    expect(
        MembershipEnforcement::query()
            ->where('user_id', $member->id)
            ->where('action', 'administrative_hold')
            ->where('status', 'active')
            ->count()
    )->toBe(1);
});

test('administrator can close administrative review with no action', function () {
    Notification::fake();
    $admin = administrativeHoldUser([
        'is_admin' => true,
    ]);

    $member = administrativeHoldUser();

    $hold = MembershipEnforcement::create([
        'user_id' => $member->id,
        'admin_id' => $admin->id,
        'action' => 'administrative_hold',
        'status' => 'active',
        'reason' => 'A serious concern requires investigation before reaching a conclusion.',
        'starts_at' => now()->subDays(7),
    ]);

    expect($member->isUnderAdministrativeReview())->toBeTrue()
        ->and($member->isInGoodStanding())->toBeFalse();

    $this->actingAs($admin);

    Livewire::test('admin.members.show', ['user' => $member])
        ->set(
            'administrativeHoldResolutionReason',
            'The investigation did not establish a basis for further membership action.'
        )
        ->call('resolveAdministrativeHoldNoAction', $hold->id)
        ->assertHasNoErrors();

    $hold->refresh();
    $member->refresh();

    expect($hold->status)->toBe('resolved')
        ->and($hold->resolved_at)->not->toBeNull()
        ->and($hold->resolved_by)->toBe($admin->id)
        ->and($member->isUnderAdministrativeReview())->toBeFalse()
        ->and($member->isInGoodStanding())->toBeTrue();

    $this->assertDatabaseHas('staff_activity_logs', [
        'actor_id' => $admin->id,
        'target_user_id' => $member->id,
        'action' => 'membership_administrative_hold_resolved_no_action',
        'subject_type' => MembershipEnforcement::class,
        'subject_id' => $hold->id,
    ]);
    Notification::assertSentTo(
        $member,
        MembershipAdministrativeReviewClosedNotification::class,
    );
});

test('closing administrative review with no action requires documented reason', function () {
    $admin = administrativeHoldUser([
        'is_admin' => true,
    ]);

    $member = administrativeHoldUser();

    $hold = MembershipEnforcement::create([
        'user_id' => $member->id,
        'admin_id' => $admin->id,
        'action' => 'administrative_hold',
        'status' => 'active',
        'reason' => 'A serious concern remains under investigation.',
        'starts_at' => now()->subDays(7),
    ]);

    $this->actingAs($admin);

    Livewire::test('admin.members.show', ['user' => $member])
        ->set('administrativeHoldResolutionReason', 'Too short')
        ->call('resolveAdministrativeHoldNoAction', $hold->id)
        ->assertHasErrors(['administrativeHoldResolutionReason']);

    $hold->refresh();

    expect($hold->status)->toBe('active')
        ->and($hold->resolved_at)->toBeNull();
});

test('administrative hold cannot be resolved through a different member component', function () {
    $admin = administrativeHoldUser([
        'is_admin' => true,
    ]);

    $member = administrativeHoldUser();
    $otherMember = administrativeHoldUser();

    $hold = MembershipEnforcement::create([
        'user_id' => $member->id,
        'admin_id' => $admin->id,
        'action' => 'administrative_hold',
        'status' => 'active',
        'reason' => 'This administrative hold belongs to another member.',
        'starts_at' => now()->subDays(7),
    ]);

    $this->actingAs($admin);

    expect(fn () => Livewire::test('admin.members.show', [
        'user' => $otherMember,
    ])
        ->set(
            'administrativeHoldResolutionReason',
            'Attempting to resolve an administrative hold belonging to another member.'
        )
        ->call('resolveAdministrativeHoldNoAction', $hold->id))
        ->toThrow(ModelNotFoundException::class);

    $hold->refresh();

    expect($hold->status)->toBe('active')
        ->and($hold->resolved_at)->toBeNull();
});

test('resolved administrative hold cannot be resolved again', function () {
    $admin = administrativeHoldUser([
        'is_admin' => true,
    ]);

    $member = administrativeHoldUser();

    $hold = MembershipEnforcement::create([
        'user_id' => $member->id,
        'admin_id' => $admin->id,
        'action' => 'administrative_hold',
        'status' => 'resolved',
        'reason' => 'This administrative review has already been completed.',
        'starts_at' => now()->subDays(14),
        'resolved_at' => now()->subDay(),
        'resolved_by' => $admin->id,
    ]);

    $this->actingAs($admin);

    expect(fn () => Livewire::test('admin.members.show', [
        'user' => $member,
    ])
        ->set(
            'administrativeHoldResolutionReason',
            'Attempting to resolve an administrative review that is already closed.'
        )
        ->call('resolveAdministrativeHoldNoAction', $hold->id))
        ->toThrow(ModelNotFoundException::class);

    expect($hold->fresh()->status)->toBe('resolved');
});

test('administrator can resolve administrative hold with formal warning', function () {
    $admin = administrativeHoldUser([
        'is_admin' => true,
    ]);

    $member = administrativeHoldUser();

    $hold = MembershipEnforcement::create([
        'user_id' => $member->id,
        'admin_id' => $admin->id,
        'action' => 'administrative_hold',
        'status' => 'active',
        'reason' => 'A serious concern requires investigation before determining the appropriate outcome.',
        'starts_at' => now()->subDays(7),
    ]);

    expect($member->isUnderAdministrativeReview())->toBeTrue()
        ->and($member->isInGoodStanding())->toBeFalse();

    $this->actingAs($admin);

    Livewire::test('admin.members.show', ['user' => $member])
        ->set(
            'warningReason',
            'The investigation supports a formal corrective warning to the member.'
        )
        ->set(
            'warningDetails',
            'The investigation was completed and did not warrant probation or suspension.'
        )
        ->set(
            'warningRequirements',
            'Review the applicable IRDI standards and avoid repeating the documented conduct.'
        )
        ->call('resolveAdministrativeHoldWithWarning', $hold->id)
        ->assertHasNoErrors();

    $hold->refresh();
    $member->refresh();

    $warning = MembershipEnforcement::query()
        ->where('user_id', $member->id)
        ->where('action', 'warning')
        ->latest('id')
        ->firstOrFail();

    expect($hold->status)->toBe('resolved')
        ->and($hold->resolved_at)->not->toBeNull()
        ->and($hold->resolved_by)->toBe($admin->id)
        ->and($warning->status)->toBe('active')
        ->and($warning->reason)->toBe(
            'The investigation supports a formal corrective warning to the member.'
        )
        ->and($member->membership_status)->toBe('active')
        ->and($member->isUnderAdministrativeReview())->toBeFalse()
        ->and($member->hasActiveMembershipWarning())->toBeTrue()
        ->and($member->isInGoodStanding())->toBeTrue();

    $this->assertDatabaseHas('staff_activity_logs', [
        'actor_id' => $admin->id,
        'target_user_id' => $member->id,
        'action' => 'membership_administrative_hold_resolved_warning',
        'subject_type' => MembershipEnforcement::class,
        'subject_id' => $hold->id,
    ]);
});

test('administrative hold warning outcome requires valid warning reason', function () {
    $admin = administrativeHoldUser([
        'is_admin' => true,
    ]);

    $member = administrativeHoldUser();

    $hold = MembershipEnforcement::create([
        'user_id' => $member->id,
        'admin_id' => $admin->id,
        'action' => 'administrative_hold',
        'status' => 'active',
        'reason' => 'This serious concern is currently under administrative investigation.',
        'starts_at' => now()->subDays(7),
    ]);

    $this->actingAs($admin);

    Livewire::test('admin.members.show', ['user' => $member])
        ->set('warningReason', 'Too short')
        ->call('resolveAdministrativeHoldWithWarning', $hold->id)
        ->assertHasErrors(['warningReason']);

    $hold->refresh();

    expect($hold->status)->toBe('active')
        ->and($hold->resolved_at)->toBeNull();

    $this->assertDatabaseMissing('membership_enforcements', [
        'user_id' => $member->id,
        'action' => 'warning',
    ]);
});

test('administrative hold cannot be resolved with warning through a different member component', function () {
    $admin = administrativeHoldUser([
        'is_admin' => true,
    ]);

    $member = administrativeHoldUser();
    $otherMember = administrativeHoldUser();

    $hold = MembershipEnforcement::create([
        'user_id' => $member->id,
        'admin_id' => $admin->id,
        'action' => 'administrative_hold',
        'status' => 'active',
        'reason' => 'This administrative hold belongs to the original member.',
        'starts_at' => now()->subDays(7),
    ]);

    $this->actingAs($admin);

    expect(fn () => Livewire::test('admin.members.show', [
        'user' => $otherMember,
    ])
        ->set(
            'warningReason',
            'This warning should not be allowed against the wrong member record.'
        )
        ->call('resolveAdministrativeHoldWithWarning', $hold->id))
        ->toThrow(ModelNotFoundException::class);

    $hold->refresh();

    expect($hold->status)->toBe('active')
        ->and($hold->resolved_at)->toBeNull();

    $this->assertDatabaseMissing('membership_enforcements', [
        'user_id' => $otherMember->id,
        'action' => 'warning',
    ]);
});

test('moderator cannot resolve administrative hold with formal warning', function () {
    $admin = administrativeHoldUser([
        'is_admin' => true,
    ]);

    $moderator = administrativeHoldUser([
        'is_moderator' => true,
    ]);

    $member = administrativeHoldUser();

    $hold = MembershipEnforcement::create([
        'user_id' => $member->id,
        'admin_id' => $admin->id,
        'action' => 'administrative_hold',
        'status' => 'active',
        'reason' => 'A serious concern is currently under formal administrative investigation.',
        'starts_at' => now()->subDays(7),
    ]);

    $this->actingAs($moderator);

    Livewire::test('admin.members.show', ['user' => $member])
        ->set(
            'warningReason',
            'A moderator must not be able to issue a formal membership warning.'
        )
        ->call('resolveAdministrativeHoldWithWarning', $hold->id)
        ->assertForbidden();

    expect($hold->fresh()->status)->toBe('active');

    $this->assertDatabaseMissing('membership_enforcements', [
        'user_id' => $member->id,
        'action' => 'warning',
    ]);
});

test('administrator can resolve administrative hold with membership suspension', function () {
    $admin = administrativeHoldUser([
        'is_admin' => true,
    ]);

    $member = administrativeHoldUser();

    $hold = MembershipEnforcement::create([
        'user_id' => $member->id,
        'admin_id' => $admin->id,
        'action' => 'administrative_hold',
        'status' => 'active',
        'reason' => 'A serious concern requires investigation before determining whether suspension is necessary.',
        'starts_at' => now()->subDays(7),
    ]);

    $this->actingAs($admin);

    Livewire::test('admin.members.show', ['user' => $member])
        ->set(
            'suspensionReason',
            'The completed investigation supports temporary suspension of this membership.'
        )
        ->call('resolveAdministrativeHoldWithSuspension', $hold->id)
        ->assertHasNoErrors();

    $hold->refresh();
    $member->refresh();

    $suspension = MembershipEnforcement::query()
        ->where('user_id', $member->id)
        ->where('action', 'suspended')
        ->latest('id')
        ->firstOrFail();

    expect($hold->status)->toBe('resolved')
        ->and($hold->resolved_at)->not->toBeNull()
        ->and($hold->resolved_by)->toBe($admin->id)
        ->and($member->membership_status)->toBe('suspended')
        ->and($member->isUnderAdministrativeReview())->toBeFalse()
        ->and($member->isInGoodStanding())->toBeFalse()
        ->and($suspension->reason)->toBe(
            'The completed investigation supports temporary suspension of this membership.'
        );

    $this->assertDatabaseHas('staff_activity_logs', [
        'actor_id' => $admin->id,
        'target_user_id' => $member->id,
        'action' => 'membership_administrative_hold_resolved_suspension',
        'subject_type' => MembershipEnforcement::class,
        'subject_id' => $hold->id,
    ]);

    $this->assertDatabaseHas('staff_activity_logs', [
        'actor_id' => $admin->id,
        'target_user_id' => $member->id,
        'action' => 'membership_suspended',
        'subject_type' => MembershipEnforcement::class,
        'subject_id' => $suspension->id,
    ]);
});

test('administrative hold suspension outcome requires valid suspension reason', function () {
    $admin = administrativeHoldUser([
        'is_admin' => true,
    ]);

    $member = administrativeHoldUser();

    $hold = MembershipEnforcement::create([
        'user_id' => $member->id,
        'admin_id' => $admin->id,
        'action' => 'administrative_hold',
        'status' => 'active',
        'reason' => 'This serious concern remains under administrative investigation.',
        'starts_at' => now()->subDays(7),
    ]);

    $this->actingAs($admin);

    Livewire::test('admin.members.show', ['user' => $member])
        ->set('suspensionReason', 'Too short')
        ->call('resolveAdministrativeHoldWithSuspension', $hold->id)
        ->assertHasErrors(['suspensionReason']);

    $hold->refresh();
    $member->refresh();

    expect($hold->status)->toBe('active')
        ->and($hold->resolved_at)->toBeNull()
        ->and($member->membership_status)->toBe('active');

    $this->assertDatabaseMissing('membership_enforcements', [
        'user_id' => $member->id,
        'action' => 'suspended',
    ]);
});

test('administrative hold cannot be resolved with suspension through a different member component', function () {
    $admin = administrativeHoldUser([
        'is_admin' => true,
    ]);

    $member = administrativeHoldUser();
    $otherMember = administrativeHoldUser();

    $hold = MembershipEnforcement::create([
        'user_id' => $member->id,
        'admin_id' => $admin->id,
        'action' => 'administrative_hold',
        'status' => 'active',
        'reason' => 'This administrative hold belongs to the original member.',
        'starts_at' => now()->subDays(7),
    ]);

    $this->actingAs($admin);

    expect(fn () => Livewire::test('admin.members.show', [
        'user' => $otherMember,
    ])
        ->set(
            'suspensionReason',
            'This suspension must not be allowed against the wrong member record.'
        )
        ->call('resolveAdministrativeHoldWithSuspension', $hold->id))
        ->toThrow(ModelNotFoundException::class);

    $hold->refresh();

    expect($hold->status)->toBe('active')
        ->and($hold->resolved_at)->toBeNull()
        ->and($member->fresh()->membership_status)->toBe('active')
        ->and($otherMember->fresh()->membership_status)->toBe('active');

    $this->assertDatabaseMissing('membership_enforcements', [
        'user_id' => $otherMember->id,
        'action' => 'suspended',
    ]);
});

test('moderator cannot resolve administrative hold with membership suspension', function () {
    $admin = administrativeHoldUser([
        'is_admin' => true,
    ]);

    $moderator = administrativeHoldUser([
        'is_moderator' => true,
    ]);

    $member = administrativeHoldUser();

    $hold = MembershipEnforcement::create([
        'user_id' => $member->id,
        'admin_id' => $admin->id,
        'action' => 'administrative_hold',
        'status' => 'active',
        'reason' => 'A serious concern is currently under formal administrative investigation.',
        'starts_at' => now()->subDays(7),
    ]);

    $this->actingAs($moderator);

    Livewire::test('admin.members.show', ['user' => $member])
        ->set(
            'suspensionReason',
            'A moderator must not be able to suspend a membership through an administrative review.'
        )
        ->call('resolveAdministrativeHoldWithSuspension', $hold->id)
        ->assertForbidden();

    expect($hold->fresh()->status)->toBe('active')
        ->and($member->fresh()->membership_status)->toBe('active');

    $this->assertDatabaseMissing('membership_enforcements', [
        'user_id' => $member->id,
        'action' => 'suspended',
    ]);
});

test('administrative hold suspension closes an active probation if both states coexist', function () {
    $admin = administrativeHoldUser([
        'is_admin' => true,
    ]);

    $member = administrativeHoldUser();

    $hold = MembershipEnforcement::create([
        'user_id' => $member->id,
        'admin_id' => $admin->id,
        'action' => 'administrative_hold',
        'status' => 'active',
        'reason' => 'A serious concern requires administrative review before determining the final outcome.',
        'starts_at' => now()->subDays(7),
    ]);

    $probation = MembershipEnforcement::create([
        'user_id' => $member->id,
        'admin_id' => $admin->id,
        'action' => 'probation',
        'status' => 'active',
        'reason' => 'An existing probation remains active while the administrative review is completed.',
        'starts_at' => now()->subDays(30),
        'eligible_for_review_at' => now()->addDays(60),
        'restoration_requirements' => 'Complete the documented corrective requirements before restoration.',
    ]);

    expect($member->fresh()->isUnderAdministrativeReview())->toBeTrue()
        ->and($member->fresh()->isOnProbation())->toBeTrue();

    $this->actingAs($admin);

    Livewire::test('admin.members.show', ['user' => $member])
        ->set(
            'suspensionReason',
            'The completed investigation requires suspension and supersedes the existing probation.'
        )
        ->call('resolveAdministrativeHoldWithSuspension', $hold->id)
        ->assertHasNoErrors();

    $hold->refresh();
    $probation->refresh();
    $member->refresh();

    expect($hold->status)->toBe('resolved')
        ->and($hold->resolved_at)->not->toBeNull()
        ->and($probation->status)->toBe('resolved')
        ->and($probation->resolved_at)->not->toBeNull()
        ->and($probation->resolved_by)->toBe($admin->id)
        ->and($member->membership_status)->toBe('suspended')
        ->and($member->isUnderAdministrativeReview())->toBeFalse()
        ->and($member->isOnProbation())->toBeFalse()
        ->and($member->isInGoodStanding())->toBeFalse();

    $this->assertDatabaseHas('staff_activity_logs', [
        'actor_id' => $admin->id,
        'target_user_id' => $member->id,
        'action' => 'membership_probation_escalated',
        'subject_type' => MembershipEnforcement::class,
        'subject_id' => $probation->id,
    ]);
});

test('super administrator can resolve administrative hold with membership ban', function () {
    Notification::fake();
    $superAdmin = administrativeHoldUser([
        'is_super_admin' => true,
    ]);

    $member = administrativeHoldUser([
        'email' => 'ban-target@example.com',
        'registration_ip' => '203.0.113.10',
        'last_login_ip' => '198.51.100.25',
    ]);

    $hold = MembershipEnforcement::create([
        'user_id' => $member->id,
        'admin_id' => $superAdmin->id,
        'action' => 'administrative_hold',
        'status' => 'active',
        'reason' => 'A serious concern requires investigation before a final membership decision is made.',
        'starts_at' => now()->subDays(7),
    ]);

    $this->actingAs($superAdmin);

    Livewire::test('admin.members.show', ['user' => $member])
        ->set(
            'banReason',
            'The completed investigation supports an indefinite ban from IRDI membership.'
        )
        ->call('resolveAdministrativeHoldWithBan', $hold->id)
        ->assertHasNoErrors();

    $hold->refresh();
    $member->refresh();

    $ban = MembershipEnforcement::query()
        ->where('user_id', $member->id)
        ->where('action', 'banned')
        ->latest('id')
        ->firstOrFail();

    expect($hold->status)->toBe('resolved')
        ->and($hold->resolved_at)->not->toBeNull()
        ->and($hold->resolved_by)->toBe($superAdmin->id)
        ->and($member->membership_status)->toBe('banned')
        ->and($member->isUnderAdministrativeReview())->toBeFalse()
        ->and($member->isInGoodStanding())->toBeFalse()
        ->and($ban->reason)->toBe(
            'The completed investigation supports an indefinite ban from IRDI membership.'
        );

    $this->assertDatabaseHas('ban_identifiers', [
        'user_id' => $member->id,
        'membership_enforcement_id' => $ban->id,
        'created_by' => $superAdmin->id,
        'type' => 'email',
        'value' => 'ban-target@example.com',
    ]);

    $this->assertDatabaseHas('ban_identifiers', [
        'user_id' => $member->id,
        'membership_enforcement_id' => $ban->id,
        'created_by' => $superAdmin->id,
        'type' => 'ip',
        'value' => '203.0.113.10',
    ]);

    $this->assertDatabaseHas('ban_identifiers', [
        'user_id' => $member->id,
        'membership_enforcement_id' => $ban->id,
        'created_by' => $superAdmin->id,
        'type' => 'ip',
        'value' => '198.51.100.25',
    ]);

    $this->assertDatabaseHas('staff_activity_logs', [
        'actor_id' => $superAdmin->id,
        'target_user_id' => $member->id,
        'action' => 'membership_administrative_hold_resolved_ban',
        'subject_type' => MembershipEnforcement::class,
        'subject_id' => $hold->id,
    ]);

    $this->assertDatabaseHas('staff_activity_logs', [
        'actor_id' => $superAdmin->id,
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

test('administrative hold ban outcome requires valid ban reason', function () {
    $superAdmin = administrativeHoldUser([
        'is_super_admin' => true,
    ]);

    $member = administrativeHoldUser();

    $hold = MembershipEnforcement::create([
        'user_id' => $member->id,
        'admin_id' => $superAdmin->id,
        'action' => 'administrative_hold',
        'status' => 'active',
        'reason' => 'A serious concern remains under administrative investigation.',
        'starts_at' => now()->subDays(7),
    ]);

    $this->actingAs($superAdmin);

    Livewire::test('admin.members.show', ['user' => $member])
        ->set('banReason', 'Too short')
        ->call('resolveAdministrativeHoldWithBan', $hold->id)
        ->assertHasErrors(['banReason']);

    $hold->refresh();
    $member->refresh();

    expect($hold->status)->toBe('active')
        ->and($hold->resolved_at)->toBeNull()
        ->and($member->membership_status)->toBe('active');

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

test('administrator cannot resolve administrative hold with membership ban', function () {
    $admin = administrativeHoldUser([
        'is_admin' => true,
    ]);

    $member = administrativeHoldUser();

    $hold = MembershipEnforcement::create([
        'user_id' => $member->id,
        'admin_id' => $admin->id,
        'action' => 'administrative_hold',
        'status' => 'active',
        'reason' => 'A serious concern is currently under administrative investigation.',
        'starts_at' => now()->subDays(7),
    ]);

    $this->actingAs($admin);

    Livewire::test('admin.members.show', ['user' => $member])
        ->set(
            'banReason',
            'An administrator must not be able to impose a membership ban.'
        )
        ->call('resolveAdministrativeHoldWithBan', $hold->id)
        ->assertForbidden();

    expect($hold->fresh()->status)->toBe('active')
        ->and($member->fresh()->membership_status)->toBe('active');

    $this->assertDatabaseMissing('membership_enforcements', [
        'user_id' => $member->id,
        'action' => 'banned',
    ]);
});

test('moderator cannot resolve administrative hold with membership ban', function () {
    $admin = administrativeHoldUser([
        'is_admin' => true,
    ]);

    $moderator = administrativeHoldUser([
        'is_moderator' => true,
    ]);

    $member = administrativeHoldUser();

    $hold = MembershipEnforcement::create([
        'user_id' => $member->id,
        'admin_id' => $admin->id,
        'action' => 'administrative_hold',
        'status' => 'active',
        'reason' => 'A serious concern is currently under formal administrative investigation.',
        'starts_at' => now()->subDays(7),
    ]);

    $this->actingAs($moderator);

    Livewire::test('admin.members.show', ['user' => $member])
        ->set(
            'banReason',
            'A moderator must not be able to impose a membership ban.'
        )
        ->call('resolveAdministrativeHoldWithBan', $hold->id)
        ->assertForbidden();

    expect($hold->fresh()->status)->toBe('active')
        ->and($member->fresh()->membership_status)->toBe('active');

    $this->assertDatabaseMissing('membership_enforcements', [
        'user_id' => $member->id,
        'action' => 'banned',
    ]);
});

test('administrative hold cannot be resolved with ban through a different member component', function () {
    $superAdmin = administrativeHoldUser([
        'is_super_admin' => true,
    ]);

    $member = administrativeHoldUser();
    $otherMember = administrativeHoldUser();

    $hold = MembershipEnforcement::create([
        'user_id' => $member->id,
        'admin_id' => $superAdmin->id,
        'action' => 'administrative_hold',
        'status' => 'active',
        'reason' => 'This administrative hold belongs to the original member.',
        'starts_at' => now()->subDays(7),
    ]);

    $this->actingAs($superAdmin);

    expect(fn () => Livewire::test('admin.members.show', [
        'user' => $otherMember,
    ])
        ->set(
            'banReason',
            'This ban must not be allowed against the wrong member record.'
        )
        ->call('resolveAdministrativeHoldWithBan', $hold->id))
        ->toThrow(ModelNotFoundException::class);

    expect($hold->fresh()->status)->toBe('active')
        ->and($member->fresh()->membership_status)->toBe('active')
        ->and($otherMember->fresh()->membership_status)->toBe('active');

    $this->assertDatabaseMissing('membership_enforcements', [
        'user_id' => $otherMember->id,
        'action' => 'banned',
    ]);
});

test('administrative hold ban closes an active probation if both states coexist', function () {
    $superAdmin = administrativeHoldUser([
        'is_super_admin' => true,
    ]);

    $member = administrativeHoldUser();

    $hold = MembershipEnforcement::create([
        'user_id' => $member->id,
        'admin_id' => $superAdmin->id,
        'action' => 'administrative_hold',
        'status' => 'active',
        'reason' => 'A serious concern requires administrative review before determining the final outcome.',
        'starts_at' => now()->subDays(7),
    ]);

    $probation = MembershipEnforcement::create([
        'user_id' => $member->id,
        'admin_id' => $superAdmin->id,
        'action' => 'probation',
        'status' => 'active',
        'reason' => 'An existing probation remains active while the administrative review is completed.',
        'starts_at' => now()->subDays(30),
        'eligible_for_review_at' => now()->addDays(60),
        'restoration_requirements' => 'Complete the documented corrective requirements before restoration.',
    ]);

    $this->actingAs($superAdmin);

    Livewire::test('admin.members.show', ['user' => $member])
        ->set(
            'banReason',
            'The completed investigation requires a ban and supersedes the existing probation.'
        )
        ->call('resolveAdministrativeHoldWithBan', $hold->id)
        ->assertHasNoErrors();

    $hold->refresh();
    $probation->refresh();
    $member->refresh();

    expect($hold->status)->toBe('resolved')
        ->and($probation->status)->toBe('resolved')
        ->and($probation->resolved_at)->not->toBeNull()
        ->and($probation->resolved_by)->toBe($superAdmin->id)
        ->and($member->membership_status)->toBe('banned')
        ->and($member->isOnProbation())->toBeFalse()
        ->and($member->isUnderAdministrativeReview())->toBeFalse();

    $this->assertDatabaseHas('staff_activity_logs', [
        'actor_id' => $superAdmin->id,
        'target_user_id' => $member->id,
        'action' => 'membership_probation_escalated',
        'subject_type' => MembershipEnforcement::class,
        'subject_id' => $probation->id,
    ]);
});

test('starting administrative review resolves an active membership probation', function () {
    $superAdmin = administrativeHoldUser([
        'is_super_admin' => true,
        'email' => 'super-admin@example.com',
    ]);

    $member = administrativeHoldUser([
        'membership_status' => 'active',
        'email' => 'probation-member@example.com',
    ]);

    $probation = MembershipEnforcement::create([
        'user_id' => $member->id,
        'admin_id' => $superAdmin->id,
        'action' => 'probation',
        'status' => 'active',
        'reason' => 'The member is on formal probation before escalation.',
        'starts_at' => now()->subDays(30),
        'eligible_for_review_at' => now()->addDays(60),
    ]);

    $this->actingAs($superAdmin);

    Livewire::test('admin.members.show', ['user' => $member])
        ->set(
            'administrativeHoldReason',
            'The documented conduct requires escalation from probation to formal administrative review.'
        )
        ->call('placeOnAdministrativeHold')
        ->assertHasNoErrors();

    $probation->refresh();

    expect($probation->status)->toBe('resolved')
        ->and($probation->resolved_at)->not->toBeNull()
        ->and($probation->resolved_by)->toBe($superAdmin->id);

    $this->assertDatabaseHas('membership_enforcements', [
        'user_id' => $member->id,
        'action' => 'administrative_hold',
        'status' => 'active',
    ]);

    $this->assertDatabaseHas('staff_activity_logs', [
        'target_user_id' => $member->id,
        'action' => 'membership_probation_escalated',
        'subject_type' => MembershipEnforcement::class,
        'subject_id' => $probation->id,
    ]);
});

test('closing administrative review with no action restores the superseded probation', function () {
    $superAdmin = administrativeHoldUser([
        'is_super_admin' => true,
        'email' => 'super-admin@example.com',
    ]);

    $member = administrativeHoldUser([
        'membership_status' => 'active',
        'email' => 'probation-review-member@example.com',
    ]);

    $probation = MembershipEnforcement::create([
        'user_id' => $member->id,
        'admin_id' => $superAdmin->id,
        'action' => 'probation',
        'status' => 'active',
        'reason' => 'The member is serving an active probation before administrative review.',
        'starts_at' => now()->subDays(30),
        'eligible_for_review_at' => now()->addDays(60),
    ]);

    $this->actingAs($superAdmin);

    Livewire::test('admin.members.show', ['user' => $member])
        ->set(
            'administrativeHoldReason',
            'New information requires formal administrative review of this membership.'
        )
        ->call('placeOnAdministrativeHold')
        ->assertHasNoErrors();

    $probation->refresh();

    expect($probation->status)->toBe('resolved');

    $hold = MembershipEnforcement::query()
        ->where('user_id', $member->id)
        ->where('action', 'administrative_hold')
        ->where('status', 'active')
        ->latest('id')
        ->firstOrFail();

    expect($hold->superseded_enforcement_id)->toBe($probation->id)
        ->and($hold->superseded_probation_remaining_days)->toBe(60);

    Livewire::test('admin.members.show', ['user' => $member])
        ->set(
            'administrativeHoldResolutionReason',
            'The administrative review found no basis for additional action beyond the existing probation.'
        )
        ->call('resolveAdministrativeHoldNoAction', $hold->id)
        ->assertHasNoErrors();

    $hold->refresh();
    $probation->refresh();

    expect($hold->status)->toBe('resolved')
        ->and($hold->resolved_at)->not->toBeNull()
        ->and($hold->resolved_by)->toBe($superAdmin->id)
        ->and($probation->status)->toBe('active')
        ->and($probation->resolved_at)->toBeNull()
        ->and($probation->resolved_by)->toBeNull()
        ->and($probation->eligible_for_review_at?->toDateString())
        ->toBe(now()->addDays(60)->toDateString());

    $this->assertDatabaseHas('staff_activity_logs', [
        'target_user_id' => $member->id,
        'action' => 'membership_probation_restored',
        'subject_type' => MembershipEnforcement::class,
        'subject_id' => $probation->id,
    ]);

    $this->assertDatabaseHas('staff_activity_logs', [
        'target_user_id' => $member->id,
        'action' => 'membership_administrative_hold_resolved_no_action',
        'subject_type' => MembershipEnforcement::class,
        'subject_id' => $hold->id,
    ]);
});
