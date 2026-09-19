<?php

use App\Models\BanIdentifier;
use App\Models\MemberProfile;
use App\Models\MembershipEnforcement;
use App\Models\StaffActivityLog;
use App\Models\User;
use App\Notifications\MembershipBannedNotification;
use App\Notifications\MembershipProbationNotification;
use App\Notifications\MembershipReinstatedNotification;
use App\Notifications\MembershipReinstatementDeniedNotification;
use App\Notifications\MembershipReinstatementPetitionSubmittedNotification;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\Notification;
use Livewire\Livewire;

function banLifecycleUser(array $attributes = []): User
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

function activeBan(User $member, array $attributes = []): MembershipEnforcement
{
    return MembershipEnforcement::create(array_merge([
        'user_id' => $member->id,
        'admin_id' => null,
        'action' => 'banned',
        'status' => 'active',
        'reason' => 'Serious conduct resulted in termination of IRDI membership.',
        'starts_at' => now()->subYears(3)->subDay(),
        'reinstatement_eligible_at' => now()->subDay(),
        'permanently_ineligible' => false,
    ], $attributes));
}

test('ordinary ban establishes three year reinstatement eligibility', function () {
    $this->freezeTime();

    $superAdmin = banLifecycleUser(['is_super_admin' => true]);
    $member = banLifecycleUser();

    $this->actingAs($superAdmin);

    Livewire::test('admin.members.show', ['user' => $member])
        ->set('banReason', 'Serious documented conduct requires termination of IRDI membership.')
        ->call('banMember')
        ->assertHasNoErrors();

    $ban = MembershipEnforcement::query()
        ->where('user_id', $member->id)
        ->where('action', 'banned')
        ->sole();

    expect($member->fresh()->membership_status)->toBe('banned')
        ->and($ban->status)->toBe('active')
        ->and($ban->starts_at?->toDateTimeString())->toBe(now()->toDateTimeString())
        ->and($ban->reinstatement_eligible_at?->toDateTimeString())->toBe(now()->addYears(3)->toDateTimeString())
        ->and($ban->permanently_ineligible)->toBeFalse();
});

test('direct ban creates linked identifiers activity and notification', function () {
    Notification::fake();
    $this->freezeTime();

    $superAdmin = banLifecycleUser([
        'is_super_admin' => true,
        'email' => 'super-admin@example.com',
    ]);

    $member = banLifecycleUser([
        'email' => 'BAN-MEMBER@example.com',
        'registration_ip' => '8.8.8.8',
        'last_login_ip' => '1.1.1.1',
    ]);

    $reason = 'Serious documented conduct requires termination of IRDI membership.';

    $this->actingAs($superAdmin);

    Livewire::test('admin.members.show', ['user' => $member])
        ->set('banReason', $reason)
        ->call('banMember')
        ->assertHasNoErrors();

    $member->refresh();

    $ban = MembershipEnforcement::query()
        ->where('user_id', $member->id)
        ->where('action', 'banned')
        ->sole();

    expect($member->membership_status)->toBe('banned')
        ->and($ban->admin_id)->toBe($superAdmin->id)
        ->and($ban->status)->toBe('active')
        ->and($ban->reason)->toBe($reason)
        ->and($ban->starts_at?->toDateTimeString())->toBe(now()->toDateTimeString())
        ->and($ban->reinstatement_eligible_at?->toDateTimeString())
        ->toBe(now()->addYears(3)->toDateTimeString())
        ->and($ban->permanently_ineligible)->toBeFalse();

    $this->assertDatabaseHas('ban_identifiers', [
        'user_id' => $member->id,
        'membership_enforcement_id' => $ban->id,
        'created_by' => $superAdmin->id,
        'type' => 'email',
        'value' => 'ban-member@example.com',
        'reason' => $reason,
    ]);

    $this->assertDatabaseHas('ban_identifiers', [
        'user_id' => $member->id,
        'membership_enforcement_id' => $ban->id,
        'created_by' => $superAdmin->id,
        'type' => 'ip',
        'value' => '8.8.8.8',
        'reason' => $reason,
    ]);

    $this->assertDatabaseHas('ban_identifiers', [
        'user_id' => $member->id,
        'membership_enforcement_id' => $ban->id,
        'created_by' => $superAdmin->id,
        'type' => 'ip',
        'value' => '1.1.1.1',
        'reason' => $reason,
    ]);

    expect(
        BanIdentifier::query()
            ->where('user_id', $member->id)
            ->where('membership_enforcement_id', $ban->id)
            ->count()
    )->toBe(3);

    $this->assertDatabaseHas('staff_activity_logs', [
        'target_user_id' => $member->id,
        'action' => 'member_banned',
        'subject_type' => MembershipEnforcement::class,
        'subject_id' => $ban->id,
    ]);

    Notification::assertSentTo(
        $member,
        MembershipBannedNotification::class,
        function (MembershipBannedNotification $notification) use ($reason): bool {
            return $notification->reason === $reason
                && $notification->permanentlyIneligible === false
                && $notification->reinstatementEligibleAt === now()
                    ->addYears(3)
                    ->format('F j, Y');
        }
    );
});

test('super administrator can make a ban permanently ineligible', function () {
    $superAdmin = banLifecycleUser(['is_super_admin' => true]);
    $member = banLifecycleUser();

    $this->actingAs($superAdmin);

    Livewire::test('admin.members.show', ['user' => $member])
        ->set('banReason', 'Exceptional circumstances require permanent ineligibility for reinstatement.')
        ->set('banPermanentlyIneligible', true)
        ->call('banMember')
        ->assertHasNoErrors();

    $ban = MembershipEnforcement::query()
        ->where('user_id', $member->id)
        ->where('action', 'banned')
        ->sole();

    expect($ban->permanently_ineligible)->toBeTrue()
        ->and($ban->reinstatement_eligible_at)->toBeNull();
});

test('eligible banned member can submit one reinstatement petition', function () {
    Notification::fake();

    $member = banLifecycleUser([
        'membership_status' => 'banned',
    ]);

    $ban = activeBan($member);

    $this->actingAs($member);

    Livewire::test('account.membership-ban')
        ->set(
            'petitionStatement',
            'I am requesting reinstatement and have addressed the conduct that resulted in my ban.'
        )
        ->call('requestReinstatement', $ban->id)
        ->assertHasNoErrors();

    $ban->refresh();

    expect($ban->review_requested_at)->not->toBeNull()
        ->and($ban->member_response)->toContain('requesting reinstatement')
        ->and($ban->member_responded_at)->not->toBeNull();

    $originalResponse = $ban->member_response;
    $originalRespondedAt = $ban->member_responded_at;
    $originalReviewRequestedAt = $ban->review_requested_at;

    Notification::assertSentToTimes(
        $member,
        MembershipReinstatementPetitionSubmittedNotification::class,
        1
    );

    /*
     * A second submission against the same Ban must not overwrite
     * the original petition or its submission timestamps.
     */
    Livewire::test('account.membership-ban')
        ->set(
            'petitionStatement',
            'This replacement petition must never overwrite the original petition.'
        )
        ->call('requestReinstatement', $ban->id)
        ->assertHasErrors(['petitionStatement']);

    $ban->refresh();

    expect($ban->member_response)->toBe($originalResponse)
        ->and($ban->member_responded_at?->toDateTimeString())
        ->toBe($originalRespondedAt?->toDateTimeString())
        ->and($ban->review_requested_at?->toDateTimeString())
        ->toBe($originalReviewRequestedAt?->toDateTimeString());

    Notification::assertSentToTimes(
        $member,
        MembershipReinstatementPetitionSubmittedNotification::class,
        1
    );
});

test('banned member cannot petition before three year eligibility date', function () {
    $member = banLifecycleUser(['membership_status' => 'banned']);
    $ban = activeBan($member, [
        'reinstatement_eligible_at' => now()->addDay(),
    ]);

    $this->actingAs($member);

    Livewire::test('account.membership-ban')
        ->set('petitionStatement', 'I am attempting to request reinstatement before the eligibility date.')
        ->call('requestReinstatement', $ban->id)
        ->assertHasErrors(['petitionStatement']);

    expect($ban->fresh()->review_requested_at)->toBeNull();
});

test('permanently ineligible banned member cannot petition', function () {
    $member = banLifecycleUser(['membership_status' => 'banned']);
    $ban = activeBan($member, [
        'reinstatement_eligible_at' => null,
        'permanently_ineligible' => true,
    ]);

    $this->actingAs($member);

    Livewire::test('account.membership-ban')
        ->set('petitionStatement', 'This petition must not be accepted for a permanently ineligible ban.')
        ->call('requestReinstatement', $ban->id)
        ->assertHasErrors(['petitionStatement']);

    expect($ban->fresh()->review_requested_at)->toBeNull();
});

test('member cannot petition against another members ban', function () {
    $member = banLifecycleUser(['membership_status' => 'banned']);
    $other = banLifecycleUser(['membership_status' => 'banned']);
    $ban = activeBan($other);

    $this->actingAs($member);

    expect(fn () => Livewire::test('account.membership-ban')
        ->set('petitionStatement', 'This petition must never attach to another member ban.')
        ->call('requestReinstatement', $ban->id))
        ->toThrow(ModelNotFoundException::class);
});

test('super administrator can deny pending reinstatement petition', function () {
    Notification::fake();

    $superAdmin = banLifecycleUser(['is_super_admin' => true]);
    $member = banLifecycleUser(['membership_status' => 'banned']);

    $ban = activeBan($member, [
        'review_requested_at' => now()->subDay(),
        'member_response' => 'Please consider my reinstatement petition.',
        'member_responded_at' => now()->subDay(),
    ]);

    BanIdentifier::create([
        'user_id' => $member->id,
        'membership_enforcement_id' => $ban->id,
        'created_by' => $superAdmin->id,
        'type' => 'email',
        'value' => strtolower($member->email),
        'reason' => $ban->reason,
    ]);

    BanIdentifier::create([
        'user_id' => $member->id,
        'membership_enforcement_id' => $ban->id,
        'created_by' => $superAdmin->id,
        'type' => 'ip',
        'value' => '8.8.8.8',
        'reason' => $ban->reason,
    ]);

    $this->actingAs($superAdmin);

    Livewire::test('admin.members.show', ['user' => $member])
        ->set(
            'reinstatementDenialReason',
            'The current record does not support reinstatement at this time.'
        )
        ->call('denyReinstatement')
        ->assertHasNoErrors();

    $member->refresh();
    $ban->refresh();

    expect($member->membership_status)->toBe('banned')
        ->and($ban->status)->toBe('active');

    expect(
        BanIdentifier::query()
            ->where('user_id', $member->id)
            ->count()
    )->toBe(2);

    $this->assertDatabaseHas('ban_identifiers', [
        'user_id' => $member->id,
        'membership_enforcement_id' => $ban->id,
        'type' => 'email',
        'value' => strtolower($member->email),
    ]);

    $this->assertDatabaseHas('ban_identifiers', [
        'user_id' => $member->id,
        'membership_enforcement_id' => $ban->id,
        'type' => 'ip',
        'value' => '8.8.8.8',
    ]);

    $this->assertDatabaseHas('membership_enforcements', [
        'user_id' => $member->id,
        'action' => 'reinstatement_denied',
        'status' => 'resolved',
        'reason' => 'The current record does not support reinstatement at this time.',
    ]);

    $this->assertDatabaseMissing('membership_enforcements', [
        'user_id' => $member->id,
        'action' => 'probation',
        'status' => 'active',
    ]);

    $this->assertDatabaseMissing('membership_enforcements', [
        'user_id' => $member->id,
        'action' => 'reinstated_good_standing',
    ]);

    Notification::assertSentTo(
        $member,
        MembershipReinstatementDeniedNotification::class,
        function (MembershipReinstatementDeniedNotification $notification): bool {
            return $notification->reason === 'The current record does not support reinstatement at this time.';
        }
    );
});

test('super administrator can reinstate banned member on probation', function () {
    Notification::fake();
    $this->freezeTime();

    $superAdmin = banLifecycleUser(['is_super_admin' => true]);
    $member = banLifecycleUser(['membership_status' => 'banned']);
    $ban = activeBan($member, [
        'review_requested_at' => now()->subDay(),
        'member_response' => 'Please consider my reinstatement petition.',
        'member_responded_at' => now()->subDay(),
    ]);

    BanIdentifier::create([
        'user_id' => $member->id,
        'membership_enforcement_id' => $ban->id,
        'created_by' => $superAdmin->id,
        'type' => 'email',
        'value' => strtolower($member->email),
        'reason' => $ban->reason,
    ]);

    $this->actingAs($superAdmin);

    Livewire::test('admin.members.show', ['user' => $member])
        ->set('reinstatementProbationReason', 'The petition supports a supervised return through membership probation.')
        ->set('reinstatementProbationRequirements', 'Demonstrate continued compliance with the IRDI Code of Ethics and Best Practices.')
        ->set('reinstatementProbationReviewDays', '90')
        ->call('reinstateWithProbation')
        ->assertHasNoErrors();

    $member->refresh();
    $ban->refresh();

    $probation = MembershipEnforcement::query()
        ->where('user_id', $member->id)
        ->where('action', 'probation')
        ->where('status', 'active')
        ->sole();

    expect($member->membership_status)->toBe('active')
        ->and($member->isOnProbation())->toBeTrue()
        ->and($member->isInGoodStanding())->toBeFalse()
        ->and($ban->status)->toBe('resolved')
        ->and($probation->admin_id)->toBe($superAdmin->id)
        ->and($probation->reason)->toBe('The petition supports a supervised return through membership probation.')
        ->and($probation->restoration_requirements)->toBe('Demonstrate continued compliance with the IRDI Code of Ethics and Best Practices.')
        ->and($probation->starts_at?->toDateTimeString())->toBe(now()->toDateTimeString())
        ->and($probation->eligible_for_review_at?->toDateTimeString())
        ->toBe(now()->addDays(90)->toDateTimeString());

    expect(
        BanIdentifier::query()
            ->where('user_id', $member->id)
            ->exists()
    )->toBeFalse();

    $this->assertDatabaseHas('membership_enforcements', [
        'id' => $ban->id,
        'user_id' => $member->id,
        'action' => 'banned',
        'status' => 'resolved',
    ]);

    Notification::assertSentTo(
        $member,
        MembershipProbationNotification::class,
    );
});

test('super administrator can exceptionally reinstate directly to active membership', function () {
    Notification::fake();
    $superAdmin = banLifecycleUser(['is_super_admin' => true]);
    $member = banLifecycleUser(['membership_status' => 'banned']);
    $ban = activeBan($member, [
        'review_requested_at' => now()->subDay(),
        'member_response' => 'Please consider my reinstatement petition.',
        'member_responded_at' => now()->subDay(),
    ]);

    BanIdentifier::create([
        'user_id' => $member->id,
        'membership_enforcement_id' => $ban->id,
        'created_by' => $superAdmin->id,
        'type' => 'email',
        'value' => strtolower($member->email),
        'reason' => $ban->reason,
    ]);

    BanIdentifier::create([
        'user_id' => $member->id,
        'membership_enforcement_id' => $ban->id,
        'created_by' => $superAdmin->id,
        'type' => 'ip',
        'value' => '8.8.8.8',
        'reason' => $ban->reason,
    ]);

    $this->actingAs($superAdmin);

    Livewire::test('admin.members.show', ['user' => $member])
        ->set('unbanReason', 'Exceptional evidence supports direct reinstatement without a probation period.')
        ->call('reinstateToGoodStanding')
        ->assertHasNoErrors();

    $member->refresh();

    expect($member->membership_status)->toBe('active')
        ->and($member->isInGoodStanding())->toBeTrue()
        ->and($ban->fresh()->status)->toBe('resolved');

    expect(
        BanIdentifier::query()
            ->where('user_id', $member->id)
            ->exists()
    )->toBeFalse();

    $this->assertDatabaseHas('membership_enforcements', [
        'id' => $ban->id,
        'user_id' => $member->id,
        'action' => 'banned',
        'status' => 'resolved',
    ]);

    $this->assertDatabaseHas('membership_enforcements', [
        'user_id' => $member->id,
        'action' => 'reinstated_good_standing',
        'status' => 'resolved',
        'reason' => 'Exceptional evidence supports direct reinstatement without a probation period.',
    ]);

    Notification::assertSentTo(
        $member,
        MembershipReinstatedNotification::class,
        function (MembershipReinstatedNotification $notification): bool {
            return $notification->reason === 'Exceptional evidence supports direct reinstatement without a probation period.';
        }
    );
});

test('direct reinstatement preserves historical ban evidence while removing active restriction artifacts', function () {
    Notification::fake();
    $this->freezeTime();

    $originalAdmin = banLifecycleUser([
        'is_super_admin' => true,
        'email' => 'original-ban-admin@example.com',
    ]);

    $reinstatingAdmin = banLifecycleUser([
        'is_super_admin' => true,
        'email' => 'reinstating-admin@example.com',
    ]);

    $member = banLifecycleUser([
        'membership_status' => 'banned',
        'email' => 'historical-ban-member@example.com',
    ]);

    $startsAt = now()->subYears(4);
    $eligibleAt = now()->subYear();
    $reviewRequestedAt = now()->subDays(10);
    $memberRespondedAt = now()->subDays(9);

    $ban = activeBan($member, [
        'admin_id' => $originalAdmin->id,
        'reason' => 'Original permanent disciplinary reason that must remain intact.',
        'starts_at' => $startsAt,
        'reinstatement_eligible_at' => $eligibleAt,
        'permanently_ineligible' => false,
        'review_requested_at' => $reviewRequestedAt,
        'member_response' => 'Original member reinstatement petition.',
        'member_responded_at' => $memberRespondedAt,
    ]);

    $originalBanActivity = StaffActivityLog::create([
        'actor_id' => $originalAdmin->id,
        'target_user_id' => $member->id,
        'action' => 'member_banned',
        'subject_type' => $ban->getMorphClass(),
        'subject_id' => $ban->id,
        'description' => 'Original historical ban activity.',
        'metadata' => [
            'reason' => $ban->reason,
        ],
        'ip_address' => '127.0.0.1',
    ]);

    BanIdentifier::create([
        'user_id' => $member->id,
        'membership_enforcement_id' => $ban->id,
        'created_by' => $originalAdmin->id,
        'type' => 'email',
        'value' => strtolower($member->email),
        'reason' => $ban->reason,
    ]);

    BanIdentifier::create([
        'user_id' => $member->id,
        'membership_enforcement_id' => $ban->id,
        'created_by' => $originalAdmin->id,
        'type' => 'ip',
        'value' => '8.8.8.8',
        'reason' => $ban->reason,
    ]);

    $this->actingAs($reinstatingAdmin);

    Livewire::test('admin.members.show', ['user' => $member])
        ->set(
            'unbanReason',
            'New evidence supports exceptional reinstatement to good standing.'
        )
        ->call('reinstateToGoodStanding')
        ->assertHasNoErrors();

    $ban->refresh();
    $originalBanActivity->refresh();
    $member->refresh();

    expect($member->membership_status)->toBe('active')
        ->and($ban->status)->toBe('resolved')
        ->and($ban->resolved_at?->toDateTimeString())->toBe(now()->toDateTimeString())
        ->and($ban->resolved_by)->toBe($reinstatingAdmin->id);

    expect($ban->admin_id)->toBe($originalAdmin->id)
        ->and($ban->reason)->toBe('Original permanent disciplinary reason that must remain intact.')
        ->and($ban->starts_at?->toDateTimeString())->toBe($startsAt->toDateTimeString())
        ->and($ban->reinstatement_eligible_at?->toDateTimeString())->toBe($eligibleAt->toDateTimeString())
        ->and($ban->permanently_ineligible)->toBeFalse()
        ->and($ban->review_requested_at?->toDateTimeString())->toBe($reviewRequestedAt->toDateTimeString())
        ->and($ban->member_response)->toBe('Original member reinstatement petition.')
        ->and($ban->member_responded_at?->toDateTimeString())->toBe($memberRespondedAt->toDateTimeString());

    expect(
        BanIdentifier::query()
            ->where('membership_enforcement_id', $ban->id)
            ->exists()
    )->toBeFalse();

    expect($originalBanActivity->actor_id)->toBe($originalAdmin->id)
        ->and($originalBanActivity->target_user_id)->toBe($member->id)
        ->and($originalBanActivity->action)->toBe('member_banned')
        ->and($originalBanActivity->subject_type)->toBe($ban->getMorphClass())
        ->and($originalBanActivity->subject_id)->toBe($ban->id)
        ->and($originalBanActivity->description)->toBe('Original historical ban activity.')
        ->and($originalBanActivity->metadata['reason'])
        ->toBe('Original permanent disciplinary reason that must remain intact.');

    $reinstatement = MembershipEnforcement::query()
        ->where('user_id', $member->id)
        ->where('action', 'reinstated_good_standing')
        ->sole();

    expect($reinstatement->id)->not->toBe($ban->id)
        ->and($reinstatement->admin_id)->toBe($reinstatingAdmin->id)
        ->and($reinstatement->status)->toBe('resolved')
        ->and($reinstatement->reason)
        ->toBe('New evidence supports exceptional reinstatement to good standing.');

    $this->assertDatabaseHas('staff_activity_logs', [
        'actor_id' => $originalAdmin->id,
        'target_user_id' => $member->id,
        'action' => 'member_banned',
        'subject_type' => $ban->getMorphClass(),
        'subject_id' => $ban->id,
    ]);

    $this->assertDatabaseHas('staff_activity_logs', [
        'actor_id' => $reinstatingAdmin->id,
        'target_user_id' => $member->id,
        'action' => 'membership_reinstated_good_standing',
        'subject_type' => $reinstatement->getMorphClass(),
        'subject_id' => $reinstatement->id,
    ]);
});

test('administrator cannot decide reinstatement petition', function () {
    Notification::fake();
    $administrator = banLifecycleUser(['is_admin' => true]);
    $member = banLifecycleUser(['membership_status' => 'banned']);
    activeBan($member, [
        'review_requested_at' => now()->subDay(),
        'member_response' => 'Please consider my reinstatement petition.',
    ]);

    $this->actingAs($administrator);

    Livewire::test('admin.members.show', ['user' => $member])
        ->set('reinstatementDenialReason', 'An ordinary administrator must not decide reinstatement.')
        ->call('denyReinstatement')
        ->assertForbidden();

    expect($member->fresh()->membership_status)->toBe('banned');

    Notification::assertNotSentTo(
        $member,
        MembershipReinstatementDeniedNotification::class,
    );
});

test('direct ban resolves all active membership probations', function () {
    Notification::fake();
    $this->freezeTime();

    $superAdmin = banLifecycleUser([
        'is_super_admin' => true,
        'email' => 'super-admin@example.com',
    ]);

    $member = banLifecycleUser([
        'membership_status' => 'active',
        'email' => 'probation-member@example.com',
    ]);

    $firstStartsAt = now()->subDays(30);
    $firstEligibleForReviewAt = now()->addDays(60);
    $firstReviewRequestedAt = now()->subDay();

    $secondStartsAt = now()->subDays(15);
    $secondEligibleForReviewAt = now()->addDays(75);
    $secondReviewRequestedAt = now()->subHours(12);

    $firstProbation = MembershipEnforcement::create([
        'user_id' => $member->id,
        'admin_id' => $superAdmin->id,
        'action' => 'probation',
        'status' => 'active',
        'reason' => 'The member is on formal probation before escalation.',
        'details' => 'First probation historical details must remain intact after direct ban.',
        'starts_at' => $firstStartsAt,
        'eligible_for_review_at' => $firstEligibleForReviewAt,
        'restoration_requirements' => 'Complete the first probation corrective requirements.',
        'review_requested_at' => $firstReviewRequestedAt,
    ]);

    $secondProbation = MembershipEnforcement::create([
        'user_id' => $member->id,
        'admin_id' => $superAdmin->id,
        'action' => 'probation',
        'status' => 'active',
        'reason' => 'A second active probation record must also be resolved during escalation.',
        'details' => 'Second probation historical details must remain intact after direct ban.',
        'starts_at' => $secondStartsAt,
        'eligible_for_review_at' => $secondEligibleForReviewAt,
        'restoration_requirements' => 'Complete the second probation corrective requirements.',
        'review_requested_at' => $secondReviewRequestedAt,
    ]);

    $this->actingAs($superAdmin);

    Livewire::test('admin.members.show', ['user' => $member])
        ->set(
            'banReason',
            'The documented conduct warrants escalation from probation to an IRDI ban.'
        )
        ->call('banMember')
        ->assertHasNoErrors();

    $member->refresh();
    $firstProbation->refresh();
    $secondProbation->refresh();

    expect($member->membership_status)->toBe('banned')
        ->and($firstProbation->status)->toBe('resolved')
        ->and($firstProbation->resolved_at?->toDateTimeString())->toBe(now()->toDateTimeString())
        ->and($firstProbation->resolved_by)->toBe($superAdmin->id)
        ->and($firstProbation->admin_id)->toBe($superAdmin->id)
        ->and($firstProbation->reason)->toBe('The member is on formal probation before escalation.')
        ->and($firstProbation->details)->toBe('First probation historical details must remain intact after direct ban.')
        ->and($firstProbation->starts_at?->toDateTimeString())->toBe($firstStartsAt->toDateTimeString())
        ->and($firstProbation->eligible_for_review_at?->toDateTimeString())->toBe($firstEligibleForReviewAt->toDateTimeString())
        ->and($firstProbation->restoration_requirements)->toBe('Complete the first probation corrective requirements.')
        ->and($firstProbation->review_requested_at?->toDateTimeString())->toBe($firstReviewRequestedAt->toDateTimeString())
        ->and($secondProbation->status)->toBe('resolved')
        ->and($secondProbation->resolved_at?->toDateTimeString())->toBe(now()->toDateTimeString())
        ->and($secondProbation->resolved_by)->toBe($superAdmin->id)
        ->and($secondProbation->admin_id)->toBe($superAdmin->id)
        ->and($secondProbation->reason)->toBe('A second active probation record must also be resolved during escalation.')
        ->and($secondProbation->details)->toBe('Second probation historical details must remain intact after direct ban.')
        ->and($secondProbation->starts_at?->toDateTimeString())->toBe($secondStartsAt->toDateTimeString())
        ->and($secondProbation->eligible_for_review_at?->toDateTimeString())->toBe($secondEligibleForReviewAt->toDateTimeString())
        ->and($secondProbation->restoration_requirements)->toBe('Complete the second probation corrective requirements.')
        ->and($secondProbation->review_requested_at?->toDateTimeString())->toBe($secondReviewRequestedAt->toDateTimeString());

    expect(
        MembershipEnforcement::query()
            ->where('user_id', $member->id)
            ->where('action', 'probation')
            ->where('status', 'active')
            ->count()
    )->toBe(0);

    expect(
        MembershipEnforcement::query()
            ->where('user_id', $member->id)
            ->where('action', 'banned')
            ->where('status', 'active')
            ->count()
    )->toBe(1);

    $this->assertDatabaseHas('staff_activity_logs', [
        'target_user_id' => $member->id,
        'action' => 'membership_probation_escalated',
        'subject_type' => MembershipEnforcement::class,
        'subject_id' => $firstProbation->id,
    ]);

    $this->assertDatabaseHas('staff_activity_logs', [
        'target_user_id' => $member->id,
        'action' => 'membership_probation_escalated',
        'subject_type' => MembershipEnforcement::class,
        'subject_id' => $secondProbation->id,
    ]);
});

test('direct ban deduplicates IP identifiers and excludes loopback addresses', function () {
    Notification::fake();

    $superAdmin = banLifecycleUser([
        'is_super_admin' => true,
        'email' => 'super-admin@example.com',
    ]);

    $member = banLifecycleUser([
        'email' => 'ban-member@example.com',
        'registration_ip' => '8.8.8.8',
        'last_login_ip' => '8.8.8.8',
    ]);

    $this->actingAs($superAdmin);

    Livewire::test('admin.members.show', ['user' => $member])
        ->set(
            'banReason',
            'Serious documented conduct requires termination of IRDI membership.'
        )
        ->call('banMember')
        ->assertHasNoErrors();

    $ban = MembershipEnforcement::query()
        ->where('user_id', $member->id)
        ->where('action', 'banned')
        ->sole();

    expect(
        BanIdentifier::query()
            ->where('membership_enforcement_id', $ban->id)
            ->where('type', 'ip')
            ->pluck('value')
            ->all()
    )->toBe(['8.8.8.8']);

    $loopbackMember = banLifecycleUser([
        'email' => 'loopback-member@example.com',
        'registration_ip' => '127.0.0.1',
        'last_login_ip' => '::1',
    ]);

    Livewire::test('admin.members.show', ['user' => $loopbackMember])
        ->set(
            'banReason',
            'Serious documented conduct requires termination of IRDI membership.'
        )
        ->call('banMember')
        ->assertHasNoErrors();

    $loopbackBan = MembershipEnforcement::query()
        ->where('user_id', $loopbackMember->id)
        ->where('action', 'banned')
        ->sole();

    expect(
        BanIdentifier::query()
            ->where('membership_enforcement_id', $loopbackBan->id)
            ->where('type', 'ip')
            ->count()
    )->toBe(0);

    $this->assertDatabaseHas('ban_identifiers', [
        'user_id' => $loopbackMember->id,
        'membership_enforcement_id' => $loopbackBan->id,
        'type' => 'email',
        'value' => 'loopback-member@example.com',
    ]);
});

test('banned member is logged out on the next web request', function () {
    $member = banLifecycleUser([
        'membership_status' => 'banned',
    ]);

    $this->actingAs($member);

    $response = $this->get(route('account'));

    $response
        ->assertRedirect(route('login'))
        ->assertSessionHasErrors([
            'email' => 'This IRDI account has been banned. If you believe this is an error, please contact IRDI.',
        ]);

    $this->assertGuest();
});

test('banned member cannot authenticate with valid credentials', function () {
    $member = banLifecycleUser([
        'membership_status' => 'banned',
        'email' => 'banned-login@example.com',
        'password' => bcrypt('correct-password'),
    ]);

    $response = $this->post(route('login'), [
        'email' => $member->email,
        'password' => 'correct-password',
    ]);

    $response
        ->assertRedirect()
        ->assertSessionHasErrors([
            'email' => 'This IRDI account has been banned. If you believe this is an error, please contact IRDI.',
        ]);

    $this->assertGuest();
});

test('banned member profile is excluded from the directory and cannot be accessed directly', function () {
    $member = banLifecycleUser([
        'membership_status' => 'banned',
    ]);

    $profile = MemberProfile::factory()->create([
        'user_id' => $member->id,
        'profile_name' => 'Banned Detectorist',
        'username' => 'banned-detectorist',
        'directory_visible' => true,
    ]);

    $directoryResponse = $this->get(route('directory'));

    $directoryResponse
        ->assertOk()
        ->assertDontSee('Banned Detectorist')
        ->assertDontSee('banned-detectorist');

    $this->get(route('member-profiles.show', [
        'profile' => $profile,
    ]))->assertNotFound();
});

test('banned member card cannot be accessed directly', function () {
    $member = banLifecycleUser([
        'membership_status' => 'banned',
    ]);

    $profile = MemberProfile::factory()->create([
        'user_id' => $member->id,
        'profile_name' => 'Banned Detectorist',
        'username' => 'banned-member-card',
        'directory_visible' => true,
    ]);

    $this->get(route('member-profiles.card', [
        'profile' => $profile,
    ]))->assertNotFound();
});

test('banned email identifier prevents re-registration regardless of case', function () {
    $bannedMember = banLifecycleUser([
        'email' => 'banned-registration@example.com',
        'membership_status' => 'banned',
    ]);

    BanIdentifier::create([
        'user_id' => $bannedMember->id,
        'created_by' => null,
        'type' => 'email',
        'value' => 'banned-registration@example.com',
        'reason' => 'Membership permanently terminated.',
    ]);

    $response = $this
        ->withServerVariables([
            'REMOTE_ADDR' => '203.0.113.50',
        ])
        ->post(route('register'), [
            'name' => 'Replacement Account',
            'email' => '  BANNED-REGISTRATION@EXAMPLE.COM  ',
            'password' => 'Password123!',
            'password_confirmation' => 'Password123!',
        ]);

    $response->assertSessionHasErrors([
        'email' => 'This email address is not eligible to register for an IRDI account.',
    ]);

    $this->assertDatabaseMissing('users', [
        'name' => 'Replacement Account',
    ]);
});

test('banned IP identifier prevents registration with a different email address', function () {
    $bannedMember = banLifecycleUser([
        'email' => 'original-banned-member@example.com',
        'membership_status' => 'banned',
    ]);

    BanIdentifier::create([
        'user_id' => $bannedMember->id,
        'created_by' => null,
        'type' => 'ip',
        'value' => '8.8.8.8',
        'reason' => 'Membership permanently terminated.',
    ]);

    $response = $this
        ->withServerVariables([
            'REMOTE_ADDR' => '8.8.8.8',
        ])
        ->post(route('register'), [
            'name' => 'Replacement Account',
            'email' => 'completely-new-email@example.com',
            'password' => 'Password123!',
            'password_confirmation' => 'Password123!',
        ]);

    $response->assertSessionHasErrors([
        'email' => 'Registration is not available from this network. Please contact IRDI if you believe this is an error.',
    ]);

    $this->assertDatabaseMissing('users', [
        'email' => 'completely-new-email@example.com',
    ]);
});

test('loopback IP ban identifier does not block registration', function () {
    $bannedMember = banLifecycleUser([
        'email' => 'historical-banned-member@example.com',
        'membership_status' => 'banned',
    ]);

    BanIdentifier::create([
        'user_id' => $bannedMember->id,
        'created_by' => null,
        'type' => 'ip',
        'value' => '127.0.0.1',
        'reason' => 'Historical loopback identifier.',
    ]);

    $response = $this
        ->withServerVariables([
            'REMOTE_ADDR' => '127.0.0.1',
        ])
        ->post(route('register'), [
            'name' => 'Allowed Registration',
            'email' => 'allowed-registration@example.com',
            'password' => 'Password123!',
            'password_confirmation' => 'Password123!',
        ]);

    $response->assertSessionHasNoErrors();

    $this->assertDatabaseHas('users', [
        'name' => 'Allowed Registration',
        'email' => 'allowed-registration@example.com',
        'registration_ip' => '127.0.0.1',
    ]);
});

test('direct reinstatement removes historical matching email ban identifier without user association', function () {
    Notification::fake();

    $superAdmin = banLifecycleUser(['is_super_admin' => true]);

    $member = banLifecycleUser([
        'membership_status' => 'banned',
        'email' => 'historical-ban@example.com',
    ]);

    $ban = activeBan($member, [
        'review_requested_at' => now()->subDay(),
        'member_response' => 'Please consider my reinstatement petition.',
        'member_responded_at' => now()->subDay(),
    ]);

    BanIdentifier::create([
        'user_id' => null,
        'membership_enforcement_id' => $ban->id,
        'created_by' => $superAdmin->id,
        'type' => 'email',
        'value' => 'historical-ban@example.com',
        'reason' => $ban->reason,
    ]);

    $this->actingAs($superAdmin);

    Livewire::test('admin.members.show', ['user' => $member])
        ->set(
            'unbanReason',
            'Exceptional evidence supports direct reinstatement without a probation period.'
        )
        ->call('reinstateToGoodStanding')
        ->assertHasNoErrors();

    $member->refresh();

    expect($member->membership_status)->toBe('active')
        ->and($ban->fresh()->status)->toBe('resolved')
        ->and(
            BanIdentifier::query()
                ->where('type', 'email')
                ->where('value', 'historical-ban@example.com')
                ->exists()
        )->toBeFalse();
});

test('administrator cannot approve reinstatement petition', function () {
    Notification::fake();
    $administrator = banLifecycleUser(['is_admin' => true]);

    $member = banLifecycleUser([
        'membership_status' => 'banned',
    ]);

    $ban = activeBan($member, [
        'review_requested_at' => now()->subDay(),
        'member_response' => 'Please consider my reinstatement petition.',
        'member_responded_at' => now()->subDay(),
    ]);

    BanIdentifier::create([
        'user_id' => $member->id,
        'membership_enforcement_id' => $ban->id,
        'created_by' => null,
        'type' => 'email',
        'value' => strtolower($member->email),
        'reason' => $ban->reason,
    ]);

    $this->actingAs($administrator);

    Livewire::test('admin.members.show', ['user' => $member])
        ->set(
            'reinstatementProbationReason',
            'An ordinary administrator must not approve reinstatement.'
        )
        ->set(
            'reinstatementProbationRequirements',
            'These requirements must never be applied by this administrator.'
        )
        ->set('reinstatementProbationReviewDays', '90')
        ->call('reinstateWithProbation')
        ->assertForbidden();

    Livewire::test('admin.members.show', ['user' => $member])
        ->set(
            'unbanReason',
            'An ordinary administrator must not directly reinstate this member.'
        )
        ->call('reinstateToGoodStanding')
        ->assertForbidden();

    $member->refresh();
    $ban->refresh();

    expect($member->membership_status)->toBe('banned')
        ->and($ban->status)->toBe('active')
        ->and(
            BanIdentifier::query()
                ->where('user_id', $member->id)
                ->exists()
        )->toBeTrue();

    $this->assertDatabaseMissing('membership_enforcements', [
        'user_id' => $member->id,
        'action' => 'probation',
    ]);

    $this->assertDatabaseMissing('membership_enforcements', [
        'user_id' => $member->id,
        'action' => 'reinstated_good_standing',
    ]);

    Notification::assertNotSentTo(
        $member,
        MembershipReinstatedNotification::class,
    );

});

test('direct reinstatement rolls back completely when staff activity creation fails', function () {
    Notification::fake();

    $superAdmin = banLifecycleUser([
        'is_super_admin' => true,
        'email' => 'rollback-super-admin@example.com',
    ]);

    $member = banLifecycleUser([
        'membership_status' => 'banned',
        'email' => 'rollback-member@example.com',
    ]);

    $ban = activeBan($member, [
        'review_requested_at' => now()->subDay(),
        'member_response' => 'Please consider my reinstatement petition.',
        'member_responded_at' => now()->subDay(),
    ]);

    BanIdentifier::create([
        'user_id' => $member->id,
        'membership_enforcement_id' => $ban->id,
        'created_by' => $superAdmin->id,
        'type' => 'email',
        'value' => strtolower($member->email),
        'reason' => $ban->reason,
    ]);

    BanIdentifier::create([
        'user_id' => $member->id,
        'membership_enforcement_id' => $ban->id,
        'created_by' => $superAdmin->id,
        'type' => 'ip',
        'value' => '8.8.8.8',
        'reason' => $ban->reason,
    ]);

    StaffActivityLog::creating(function (StaffActivityLog $activity): void {
        if ($activity->action === 'membership_reinstated_good_standing') {
            throw new RuntimeException(
                'Forced staff activity failure during reinstatement.'
            );
        }
    });

    $this->actingAs($superAdmin);

    expect(fn () => Livewire::test('admin.members.show', ['user' => $member])
        ->set(
            'unbanReason',
            'Exceptional evidence supports direct reinstatement.'
        )
        ->call('reinstateToGoodStanding')
    )->toThrow(
        RuntimeException::class,
        'Forced staff activity failure during reinstatement.'
    );

    $member->refresh();
    $ban->refresh();

    expect($member->membership_status)->toBe('banned')
        ->and($ban->status)->toBe('active')
        ->and($ban->resolved_at)->toBeNull()
        ->and($ban->resolved_by)->toBeNull();

    expect(
        BanIdentifier::query()
            ->where('user_id', $member->id)
            ->count()
    )->toBe(2);

    $this->assertDatabaseMissing('membership_enforcements', [
        'user_id' => $member->id,
        'action' => 'reinstated_good_standing',
    ]);

    $this->assertDatabaseMissing('staff_activity_logs', [
        'target_user_id' => $member->id,
        'action' => 'membership_reinstated_good_standing',
    ]);

    Notification::assertNotSentTo(
        $member,
        MembershipReinstatedNotification::class,
    );
});

test('direct reinstatement rechecks authoritative membership state inside the transaction', function () {
    Notification::fake();

    $superAdmin = banLifecycleUser([
        'is_super_admin' => true,
        'email' => 'concurrency-super-admin@example.com',
    ]);

    $member = banLifecycleUser([
        'membership_status' => 'banned',
        'email' => 'concurrency-member@example.com',
    ]);

    $ban = activeBan($member, [
        'review_requested_at' => now()->subDay(),
        'member_response' => 'Please consider my reinstatement petition.',
        'member_responded_at' => now()->subDay(),
    ]);

    $this->actingAs($superAdmin);

    $component = Livewire::test('admin.members.show', ['user' => $member]);

    /*
     * Simulate another request successfully changing the authoritative
     * membership state after this Livewire component was loaded.
     */
    $member->update([
        'membership_status' => 'active',
    ]);

    $component
        ->set(
            'unbanReason',
            'This stale request must not create another reinstatement.'
        )
        ->call('reinstateToGoodStanding')
        ->assertHasErrors(['unbanReason']);

    $ban->refresh();

    expect($member->fresh()->membership_status)->toBe('active')
        ->and($ban->status)->toBe('active')
        ->and($ban->resolved_at)->toBeNull()
        ->and($ban->resolved_by)->toBeNull();

    $this->assertDatabaseMissing('membership_enforcements', [
        'user_id' => $member->id,
        'action' => 'reinstated_good_standing',
    ]);

    $this->assertDatabaseMissing('staff_activity_logs', [
        'target_user_id' => $member->id,
        'action' => 'membership_reinstated_good_standing',
    ]);

    Notification::assertNotSentTo(
        $member,
        MembershipReinstatedNotification::class,
    );
});

test('direct reinstatement rechecks authoritative ban state inside the transaction', function () {
    Notification::fake();

    $superAdmin = banLifecycleUser([
        'is_super_admin' => true,
        'email' => 'stale-ban-super-admin@example.com',
    ]);

    $member = banLifecycleUser([
        'membership_status' => 'banned',
        'email' => 'stale-ban-member@example.com',
    ]);

    $ban = activeBan($member, [
        'review_requested_at' => now()->subDay(),
        'member_response' => 'Please consider my reinstatement petition.',
        'member_responded_at' => now()->subDay(),
    ]);

    $this->actingAs($superAdmin);

    $component = Livewire::test('admin.members.show', ['user' => $member]);

    /*
     * Simulate another request resolving the active ban after this
     * Livewire component was loaded.
     */
    $ban->update([
        'status' => 'resolved',
        'resolved_at' => now(),
        'resolved_by' => $superAdmin->id,
    ]);

    $component
        ->set(
            'unbanReason',
            'This stale request must not create another reinstatement.'
        )
        ->call('reinstateToGoodStanding')
        ->assertHasErrors(['unbanReason']);

    $ban->refresh();

    expect($member->fresh()->membership_status)->toBe('banned')
        ->and($ban->status)->toBe('resolved')
        ->and($ban->resolved_at)->not->toBeNull()
        ->and($ban->resolved_by)->toBe($superAdmin->id);

    $this->assertDatabaseMissing('membership_enforcements', [
        'user_id' => $member->id,
        'action' => 'reinstated_good_standing',
    ]);

    $this->assertDatabaseMissing('staff_activity_logs', [
        'target_user_id' => $member->id,
        'action' => 'membership_reinstated_good_standing',
    ]);

    Notification::assertNotSentTo(
        $member,
        MembershipReinstatedNotification::class,
    );
});

test('probation reinstatement rechecks authoritative membership state inside the transaction', function () {
    Notification::fake();

    $superAdmin = banLifecycleUser([
        'is_super_admin' => true,
        'email' => 'probation-concurrency-super-admin@example.com',
    ]);

    $member = banLifecycleUser([
        'membership_status' => 'banned',
        'email' => 'probation-concurrency-member@example.com',
    ]);

    $ban = activeBan($member, [
        'review_requested_at' => now()->subDay(),
        'member_response' => 'Please consider my reinstatement petition.',
        'member_responded_at' => now()->subDay(),
    ]);

    $this->actingAs($superAdmin);

    $component = Livewire::test('admin.members.show', ['user' => $member]);

    /*
     * Simulate another request changing the authoritative membership
     * state after this Livewire component was loaded.
     */
    $member->update([
        'membership_status' => 'active',
    ]);

    $component
        ->set(
            'reinstatementProbationReason',
            'This stale request must not create probation.'
        )
        ->set(
            'reinstatementProbationRequirements',
            'The member must satisfy all probation requirements.'
        )
        ->set('reinstatementProbationReviewDays', '90')
        ->call('reinstateWithProbation')
        ->assertHasErrors(['reinstatementProbationReason']);

    $ban->refresh();

    expect($member->fresh()->membership_status)->toBe('active')
        ->and($ban->status)->toBe('active')
        ->and($ban->resolved_at)->toBeNull()
        ->and($ban->resolved_by)->toBeNull();

    $this->assertDatabaseMissing('membership_enforcements', [
        'user_id' => $member->id,
        'action' => 'probation',
    ]);

    $this->assertDatabaseMissing('staff_activity_logs', [
        'target_user_id' => $member->id,
        'action' => 'membership_reinstated_probation',
    ]);

    Notification::assertNotSentTo(
        $member,
        MembershipProbationNotification::class,
    );
});

test('probation reinstatement rechecks authoritative ban state inside the transaction', function () {
    Notification::fake();

    $superAdmin = banLifecycleUser([
        'is_super_admin' => true,
        'email' => 'probation-stale-ban-super-admin@example.com',
    ]);

    $member = banLifecycleUser([
        'membership_status' => 'banned',
        'email' => 'probation-stale-ban-member@example.com',
    ]);

    $ban = activeBan($member, [
        'review_requested_at' => now()->subDay(),
        'member_response' => 'Please consider my reinstatement petition.',
        'member_responded_at' => now()->subDay(),
    ]);

    $this->actingAs($superAdmin);

    $component = Livewire::test('admin.members.show', ['user' => $member]);

    /*
     * Simulate another request resolving the active ban while the
     * stale component still believes that ban is actionable.
     */
    $ban->update([
        'status' => 'resolved',
        'resolved_at' => now(),
        'resolved_by' => $superAdmin->id,
    ]);

    $component
        ->set(
            'reinstatementProbationReason',
            'This stale request must not create probation.'
        )
        ->set(
            'reinstatementProbationRequirements',
            'The member must satisfy all probation requirements.'
        )
        ->set('reinstatementProbationReviewDays', '90')
        ->call('reinstateWithProbation')
        ->assertHasErrors(['reinstatementProbationReason']);

    $ban->refresh();

    expect($member->fresh()->membership_status)->toBe('banned')
        ->and($ban->status)->toBe('resolved')
        ->and($ban->resolved_at)->not->toBeNull()
        ->and($ban->resolved_by)->toBe($superAdmin->id);

    $this->assertDatabaseMissing('membership_enforcements', [
        'user_id' => $member->id,
        'action' => 'probation',
    ]);

    $this->assertDatabaseMissing('staff_activity_logs', [
        'target_user_id' => $member->id,
        'action' => 'membership_reinstated_probation',
    ]);

    Notification::assertNotSentTo(
        $member,
        MembershipProbationNotification::class,
    );
});

test('probation reinstatement rolls back completely when staff activity creation fails', function () {
    Notification::fake();

    $superAdmin = banLifecycleUser([
        'is_super_admin' => true,
        'email' => 'probation-rollback-super-admin@example.com',
    ]);

    $member = banLifecycleUser([
        'membership_status' => 'banned',
        'email' => 'probation-rollback-member@example.com',
    ]);

    $ban = activeBan($member, [
        'review_requested_at' => now()->subDay(),
        'member_response' => 'Please consider my reinstatement petition.',
        'member_responded_at' => now()->subDay(),
    ]);

    BanIdentifier::create([
        'user_id' => $member->id,
        'membership_enforcement_id' => $ban->id,
        'created_by' => $superAdmin->id,
        'type' => 'email',
        'value' => strtolower($member->email),
        'reason' => $ban->reason,
    ]);

    BanIdentifier::create([
        'user_id' => $member->id,
        'membership_enforcement_id' => $ban->id,
        'created_by' => $superAdmin->id,
        'type' => 'ip',
        'value' => '8.8.8.8',
        'reason' => $ban->reason,
    ]);

    StaffActivityLog::creating(function (StaffActivityLog $activity): void {
        if ($activity->action === 'membership_reinstated_probation') {
            throw new RuntimeException(
                'Forced staff activity failure during probation reinstatement.'
            );
        }
    });

    $this->actingAs($superAdmin);

    expect(fn () => Livewire::test('admin.members.show', ['user' => $member])
        ->set(
            'reinstatementProbationReason',
            'Probation is appropriate following review of the petition.'
        )
        ->set(
            'reinstatementProbationRequirements',
            'The member must satisfy all probation requirements before restoration.'
        )
        ->set('reinstatementProbationReviewDays', '90')
        ->call('reinstateWithProbation')
    )->toThrow(
        RuntimeException::class,
        'Forced staff activity failure during probation reinstatement.'
    );

    $member->refresh();
    $ban->refresh();

    expect($member->membership_status)->toBe('banned')
        ->and($ban->status)->toBe('active')
        ->and($ban->resolved_at)->toBeNull()
        ->and($ban->resolved_by)->toBeNull();

    expect(
        BanIdentifier::query()
            ->where('user_id', $member->id)
            ->count()
    )->toBe(2);

    $this->assertDatabaseMissing('membership_enforcements', [
        'user_id' => $member->id,
        'action' => 'probation',
    ]);

    $this->assertDatabaseMissing('staff_activity_logs', [
        'target_user_id' => $member->id,
        'action' => 'membership_reinstated_probation',
    ]);

    Notification::assertNotSentTo(
        $member,
        MembershipProbationNotification::class,
    );
});

test('reinstatement petition cannot be denied more than once', function () {
    Notification::fake();

    $superAdmin = banLifecycleUser([
        'is_super_admin' => true,
        'email' => 'duplicate-denial-super-admin@example.com',
    ]);

    $member = banLifecycleUser([
        'membership_status' => 'banned',
        'email' => 'duplicate-denial-member@example.com',
    ]);

    $ban = activeBan($member, [
        'review_requested_at' => now()->subDay(),
        'member_response' => 'Please consider my reinstatement petition.',
        'member_responded_at' => now()->subDay(),
    ]);

    $this->actingAs($superAdmin);

    Livewire::test('admin.members.show', ['user' => $member])
        ->set(
            'reinstatementDenialReason',
            'The petition does not demonstrate sufficient grounds for reinstatement.'
        )
        ->call('denyReinstatement')
        ->assertHasNoErrors();

    expect(
        MembershipEnforcement::query()
            ->where('user_id', $member->id)
            ->where('action', 'reinstatement_denied')
            ->count()
    )->toBe(1);

    Notification::assertSentToTimes(
        $member,
        MembershipReinstatementDeniedNotification::class,
        1
    );

    /*
     * Submit another denial against the same still-active Ban.
     * The original denial must make the petition no longer actionable.
     */
    Livewire::test('admin.members.show', ['user' => $member])
        ->set(
            'reinstatementDenialReason',
            'A second denial must not create another disciplinary record.'
        )
        ->call('denyReinstatement')
        ->assertHasErrors(['reinstatementDenialReason']);

    expect($member->fresh()->membership_status)->toBe('banned')
        ->and($ban->fresh()->status)->toBe('active');

    expect(
        MembershipEnforcement::query()
            ->where('user_id', $member->id)
            ->where('action', 'reinstatement_denied')
            ->count()
    )->toBe(1);

    expect(
        StaffActivityLog::query()
            ->where('target_user_id', $member->id)
            ->where('action', 'membership_reinstatement_denied')
            ->count()
    )->toBe(1);

    Notification::assertSentToTimes(
        $member,
        MembershipReinstatementDeniedNotification::class,
        1
    );
});

test('reinstatement denial rechecks authoritative membership state inside the transaction', function () {
    Notification::fake();

    $superAdmin = banLifecycleUser([
        'is_super_admin' => true,
        'email' => 'denial-concurrency-super-admin@example.com',
    ]);

    $member = banLifecycleUser([
        'membership_status' => 'banned',
        'email' => 'denial-concurrency-member@example.com',
    ]);

    $ban = activeBan($member, [
        'review_requested_at' => now()->subDay(),
        'member_response' => 'Please consider my reinstatement petition.',
        'member_responded_at' => now()->subDay(),
    ]);

    $this->actingAs($superAdmin);

    $component = Livewire::test('admin.members.show', ['user' => $member]);

    /*
     * Simulate another request changing the authoritative membership
     * state after this Livewire component was loaded.
     */
    $member->update([
        'membership_status' => 'active',
    ]);

    $component
        ->set(
            'reinstatementDenialReason',
            'This stale request must not deny the reinstatement petition.'
        )
        ->call('denyReinstatement')
        ->assertHasErrors(['reinstatementDenialReason']);

    $ban->refresh();

    expect($member->fresh()->membership_status)->toBe('active')
        ->and($ban->status)->toBe('active')
        ->and($ban->resolved_at)->toBeNull()
        ->and($ban->resolved_by)->toBeNull();

    $this->assertDatabaseMissing('membership_enforcements', [
        'user_id' => $member->id,
        'action' => 'reinstatement_denied',
    ]);

    $this->assertDatabaseMissing('staff_activity_logs', [
        'target_user_id' => $member->id,
        'action' => 'membership_reinstatement_denied',
    ]);

    Notification::assertNotSentTo(
        $member,
        MembershipReinstatementDeniedNotification::class,
    );
});

test('reinstatement denial rechecks authoritative ban state inside the transaction', function () {
    Notification::fake();

    $superAdmin = banLifecycleUser([
        'is_super_admin' => true,
        'email' => 'denial-stale-ban-super-admin@example.com',
    ]);

    $member = banLifecycleUser([
        'membership_status' => 'banned',
        'email' => 'denial-stale-ban-member@example.com',
    ]);

    $ban = activeBan($member, [
        'review_requested_at' => now()->subDay(),
        'member_response' => 'Please consider my reinstatement petition.',
        'member_responded_at' => now()->subDay(),
    ]);

    $this->actingAs($superAdmin);

    $component = Livewire::test('admin.members.show', ['user' => $member]);

    /*
     * Simulate another request resolving the active ban while this
     * Livewire component still holds stale petition state.
     */
    $ban->update([
        'status' => 'resolved',
        'resolved_at' => now(),
        'resolved_by' => $superAdmin->id,
    ]);

    $component
        ->set(
            'reinstatementDenialReason',
            'This stale request must not deny an already resolved petition.'
        )
        ->call('denyReinstatement')
        ->assertHasErrors(['reinstatementDenialReason']);

    $ban->refresh();

    expect($member->fresh()->membership_status)->toBe('banned')
        ->and($ban->status)->toBe('resolved')
        ->and($ban->resolved_at)->not->toBeNull()
        ->and($ban->resolved_by)->toBe($superAdmin->id);

    $this->assertDatabaseMissing('membership_enforcements', [
        'user_id' => $member->id,
        'action' => 'reinstatement_denied',
    ]);

    $this->assertDatabaseMissing('staff_activity_logs', [
        'target_user_id' => $member->id,
        'action' => 'membership_reinstatement_denied',
    ]);

    Notification::assertNotSentTo(
        $member,
        MembershipReinstatementDeniedNotification::class,
    );
});

test('reinstatement denial rolls back completely when staff activity creation fails', function () {
    Notification::fake();

    $superAdmin = banLifecycleUser([
        'is_super_admin' => true,
        'email' => 'denial-rollback-super-admin@example.com',
    ]);

    $member = banLifecycleUser([
        'membership_status' => 'banned',
        'email' => 'denial-rollback-member@example.com',
    ]);

    $ban = activeBan($member, [
        'review_requested_at' => now()->subDay(),
        'member_response' => 'Please consider my reinstatement petition.',
        'member_responded_at' => now()->subDay(),
    ]);

    StaffActivityLog::creating(function (StaffActivityLog $activity): void {
        if ($activity->action === 'membership_reinstatement_denied') {
            throw new RuntimeException(
                'Forced staff activity failure during reinstatement denial.'
            );
        }
    });

    $this->actingAs($superAdmin);

    expect(fn () => Livewire::test('admin.members.show', ['user' => $member])
        ->set(
            'reinstatementDenialReason',
            'The petition does not establish sufficient grounds for reinstatement.'
        )
        ->call('denyReinstatement')
    )->toThrow(
        RuntimeException::class,
        'Forced staff activity failure during reinstatement denial.'
    );

    $member->refresh();
    $ban->refresh();

    expect($member->membership_status)->toBe('banned')
        ->and($ban->status)->toBe('active')
        ->and($ban->resolved_at)->toBeNull()
        ->and($ban->resolved_by)->toBeNull();

    $this->assertDatabaseMissing('membership_enforcements', [
        'user_id' => $member->id,
        'action' => 'reinstatement_denied',
    ]);

    $this->assertDatabaseMissing('staff_activity_logs', [
        'target_user_id' => $member->id,
        'action' => 'membership_reinstatement_denied',
    ]);

    Notification::assertNotSentTo(
        $member,
        MembershipReinstatementDeniedNotification::class,
    );
});

test('reinstatement petition rechecks authoritative membership state inside the transaction', function () {
    Notification::fake();

    $member = banLifecycleUser([
        'membership_status' => 'banned',
        'email' => 'petition-concurrency-member@example.com',
    ]);

    $ban = activeBan($member);

    $this->actingAs($member);

    $component = Livewire::test('account.membership-ban');

    /*
     * Simulate another request changing the authoritative membership
     * state after this Livewire component was loaded.
     */
    $member->update([
        'membership_status' => 'active',
    ]);

    $component
        ->set(
            'petitionStatement',
            'This stale request must not create a reinstatement petition.'
        )
        ->call('requestReinstatement', $ban->id)
        ->assertHasErrors(['petitionStatement']);

    $ban->refresh();

    expect($member->fresh()->membership_status)->toBe('active')
        ->and($ban->review_requested_at)->toBeNull()
        ->and($ban->member_response)->toBeNull()
        ->and($ban->member_responded_at)->toBeNull();

    Notification::assertNotSentTo(
        $member,
        MembershipReinstatementPetitionSubmittedNotification::class,
    );
});

test('reinstatement petition rechecks authoritative ban state inside the transaction', function () {
    Notification::fake();

    $member = banLifecycleUser([
        'membership_status' => 'banned',
        'email' => 'petition-stale-ban-member@example.com',
    ]);

    $ban = activeBan($member);

    $this->actingAs($member);

    $component = Livewire::test('account.membership-ban');

    /*
     * Simulate another request resolving the Ban after this component
     * was loaded while deliberately leaving the User marked banned.
     */
    $ban->update([
        'status' => 'resolved',
        'resolved_at' => now(),
        'resolved_by' => $member->id,
    ]);

    expect(fn () => $component
        ->set(
            'petitionStatement',
            'This stale request must not create a reinstatement petition.'
        )
        ->call('requestReinstatement', $ban->id)
    )->toThrow(ModelNotFoundException::class);

    $ban->refresh();

    expect($member->fresh()->membership_status)->toBe('banned')
        ->and($ban->status)->toBe('resolved')
        ->and($ban->resolved_at)->not->toBeNull()
        ->and($ban->review_requested_at)->toBeNull()
        ->and($ban->member_response)->toBeNull()
        ->and($ban->member_responded_at)->toBeNull();

    Notification::assertNotSentTo(
        $member,
        MembershipReinstatementPetitionSubmittedNotification::class,
    );
});

test('reinstatement petition preserves historical ban evidence', function () {
    Notification::fake();

    $superAdmin = banLifecycleUser([
        'is_super_admin' => true,
        'email' => 'petition-history-super-admin@example.com',
    ]);

    $member = banLifecycleUser([
        'membership_status' => 'banned',
        'email' => 'petition-history-member@example.com',
    ]);

    $startsAt = now()->subYear()->startOfSecond();
    $eligibleAt = now()->subDay()->startOfSecond();

    $ban = activeBan($member, [
        'admin_id' => $superAdmin->id,
        'reason' => 'Original permanent disciplinary reason that must remain intact.',
        'starts_at' => $startsAt,
        'permanently_ineligible' => false,
        'reinstatement_eligible_at' => $eligibleAt,
    ]);

    BanIdentifier::create([
        'user_id' => $member->id,
        'membership_enforcement_id' => $ban->id,
        'created_by' => $superAdmin->id,
        'type' => 'email',
        'value' => strtolower($member->email),
        'reason' => $ban->reason,
    ]);

    BanIdentifier::create([
        'user_id' => $member->id,
        'membership_enforcement_id' => $ban->id,
        'created_by' => $superAdmin->id,
        'type' => 'ip',
        'value' => '8.8.8.8',
        'reason' => $ban->reason,
    ]);

    $originalBanId = $ban->id;
    $originalAdminId = $ban->admin_id;
    $originalReason = $ban->reason;
    $originalStartsAt = $ban->starts_at;
    $originalEligibleAt = $ban->reinstatement_eligible_at;

    $this->actingAs($member);

    Livewire::test('account.membership-ban')
        ->set(
            'petitionStatement',
            'I am requesting reinstatement after addressing the conduct that resulted in this ban.'
        )
        ->call('requestReinstatement', $ban->id)
        ->assertHasNoErrors();

    $ban->refresh();

    expect($ban->id)->toBe($originalBanId)
        ->and($ban->admin_id)->toBe($originalAdminId)
        ->and($ban->reason)->toBe($originalReason)
        ->and($ban->starts_at?->toDateTimeString())
        ->toBe($originalStartsAt?->toDateTimeString())
        ->and($ban->reinstatement_eligible_at?->toDateTimeString())
        ->toBe($originalEligibleAt?->toDateTimeString())
        ->and($ban->permanently_ineligible)->toBeFalse()
        ->and($ban->status)->toBe('active')
        ->and($ban->resolved_at)->toBeNull()
        ->and($ban->resolved_by)->toBeNull();

    expect($ban->member_response)
        ->toBe('I am requesting reinstatement after addressing the conduct that resulted in this ban.')
        ->and($ban->member_responded_at)->not->toBeNull()
        ->and($ban->review_requested_at)->not->toBeNull();

    expect(
        BanIdentifier::query()
            ->where('user_id', $member->id)
            ->where('membership_enforcement_id', $ban->id)
            ->count()
    )->toBe(2);

    Notification::assertSentToTimes(
        $member,
        MembershipReinstatementPetitionSubmittedNotification::class,
        1
    );
});

test('reinstating member does not remove shared IP ban identifier belonging to another member', function () {
    Notification::fake();

    $superAdmin = banLifecycleUser([
        'is_super_admin' => true,
    ]);

    $member = banLifecycleUser([
        'membership_status' => 'banned',
        'registration_ip' => '8.8.8.8',
    ]);

    $otherBannedMember = banLifecycleUser([
        'membership_status' => 'banned',
        'registration_ip' => '8.8.8.8',
    ]);

    $ban = activeBan($member, [
        'review_requested_at' => now()->subDay(),
        'member_response' => 'Please consider my reinstatement petition.',
        'member_responded_at' => now()->subDay(),
    ]);

    $otherBan = activeBan($otherBannedMember);

    BanIdentifier::create([
        'user_id' => $member->id,
        'membership_enforcement_id' => $ban->id,
        'created_by' => $superAdmin->id,
        'type' => 'email',
        'value' => strtolower($member->email),
        'reason' => $ban->reason,
    ]);

    BanIdentifier::create([
        'user_id' => $otherBannedMember->id,
        'membership_enforcement_id' => $otherBan->id,
        'created_by' => $superAdmin->id,
        'type' => 'ip',
        'value' => '8.8.8.8',
        'reason' => $otherBan->reason,
    ]);

    $this->actingAs($superAdmin);

    Livewire::test('admin.members.show', ['user' => $member])
        ->set(
            'unbanReason',
            'Exceptional evidence supports direct reinstatement without a probation period.'
        )
        ->call('reinstateToGoodStanding')
        ->assertHasNoErrors();

    expect($member->fresh()->membership_status)->toBe('active')
        ->and($otherBannedMember->fresh()->membership_status)->toBe('banned');

    expect(
        BanIdentifier::query()
            ->where('user_id', $member->id)
            ->exists()
    )->toBeFalse();

    $this->assertDatabaseHas('ban_identifiers', [
        'user_id' => $otherBannedMember->id,
        'membership_enforcement_id' => $otherBan->id,
        'type' => 'ip',
        'value' => '8.8.8.8',
    ]);
});

test('banning second member does not transfer shared IP ban identifier from first member', function () {
    Notification::fake();

    $superAdmin = banLifecycleUser([
        'is_super_admin' => true,
    ]);

    $firstMember = banLifecycleUser([
        'membership_status' => 'banned',
        'registration_ip' => '8.8.8.8',
        'last_login_ip' => null,
    ]);

    $secondMember = banLifecycleUser([
        'membership_status' => 'suspended',
        'registration_ip' => '8.8.8.8',
        'last_login_ip' => null,
    ]);

    $firstBan = activeBan($firstMember);

    $existingIdentifier = BanIdentifier::create([
        'user_id' => $firstMember->id,
        'membership_enforcement_id' => $firstBan->id,
        'created_by' => $superAdmin->id,
        'type' => 'ip',
        'value' => '8.8.8.8',
        'reason' => $firstBan->reason,
    ]);

    $secondSuspension = MembershipEnforcement::create([
        'user_id' => $secondMember->id,
        'admin_id' => $superAdmin->id,
        'action' => 'suspended',
        'status' => 'active',
        'reason' => 'The membership is suspended pending permanent-removal review.',
        'starts_at' => now()->subDays(180),
        'eligible_for_review_at' => now()->subDay(),
    ]);

    $this->actingAs($superAdmin);

    Livewire::test('admin.members.show', ['user' => $secondMember])
        ->set(
            'banReason',
            'Administrative review determined that permanent removal is required.'
        )
        ->call('resolveSuspensionWithBan', $secondSuspension->id);

    $existingIdentifier->refresh();

    expect($secondMember->fresh()->membership_status)->toBe('banned')
        ->and($existingIdentifier->user_id)->toBe($firstMember->id)
        ->and($existingIdentifier->membership_enforcement_id)->toBe($firstBan->id);
});

test('directly banning second member does not transfer shared IP ban identifier from first member', function () {
    Notification::fake();

    $superAdmin = banLifecycleUser([
        'is_super_admin' => true,
    ]);

    $firstMember = banLifecycleUser([
        'membership_status' => 'banned',
        'registration_ip' => '8.8.8.8',
        'last_login_ip' => null,
    ]);

    $secondMember = banLifecycleUser([
        'membership_status' => 'active',
        'registration_ip' => '8.8.8.8',
        'last_login_ip' => null,
    ]);

    $firstBan = activeBan($firstMember);

    $existingIdentifier = BanIdentifier::create([
        'user_id' => $firstMember->id,
        'membership_enforcement_id' => $firstBan->id,
        'created_by' => $superAdmin->id,
        'type' => 'ip',
        'value' => '8.8.8.8',
        'reason' => $firstBan->reason,
    ]);

    $this->actingAs($superAdmin);

    Livewire::test('admin.members.show', ['user' => $secondMember])
        ->set(
            'banReason',
            'Direct administrative ban for shared-IP ownership testing.'
        )
        ->call('banMember');

    $existingIdentifier->refresh();

    expect($secondMember->fresh()->membership_status)->toBe('banned')
        ->and($existingIdentifier->user_id)->toBe($firstMember->id)
        ->and($existingIdentifier->membership_enforcement_id)->toBe($firstBan->id);
});

test('administrative hold ban does not transfer shared IP ban identifier from first member', function () {
    Notification::fake();

    $superAdmin = banLifecycleUser([
        'is_super_admin' => true,
    ]);

    $firstMember = banLifecycleUser([
        'membership_status' => 'banned',
        'registration_ip' => '8.8.8.8',
        'last_login_ip' => null,
    ]);

    $secondMember = banLifecycleUser([
        'membership_status' => 'active',
        'registration_ip' => '8.8.8.8',
        'last_login_ip' => null,
    ]);

    $firstBan = activeBan($firstMember);

    $existingIdentifier = BanIdentifier::create([
        'user_id' => $firstMember->id,
        'membership_enforcement_id' => $firstBan->id,
        'created_by' => $superAdmin->id,
        'type' => 'ip',
        'value' => '8.8.8.8',
        'reason' => $firstBan->reason,
    ]);

    $administrativeHold = MembershipEnforcement::create([
        'user_id' => $secondMember->id,
        'admin_id' => $superAdmin->id,
        'action' => 'administrative_hold',
        'status' => 'active',
        'reason' => 'Administrative hold pending final determination.',
        'starts_at' => now()->subDay(),
    ]);

    $this->actingAs($superAdmin);

    Livewire::test('admin.members.show', ['user' => $secondMember])
        ->set(
            'banReason',
            'Administrative hold review determined that permanent removal is required.'
        )
        ->call('resolveAdministrativeHoldWithBan', $administrativeHold->id);

    $existingIdentifier->refresh();

    expect($secondMember->fresh()->membership_status)->toBe('banned')
        ->and($existingIdentifier->user_id)->toBe($firstMember->id)
        ->and($existingIdentifier->membership_enforcement_id)->toBe($firstBan->id);
});
