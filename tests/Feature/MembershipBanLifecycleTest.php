<?php

use App\Models\BanIdentifier;
use App\Models\MemberProfile;
use App\Models\MembershipEnforcement;
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
    $member = banLifecycleUser(['membership_status' => 'banned']);
    $ban = activeBan($member);

    $this->actingAs($member);

    Livewire::test('account.membership-ban')
        ->set('petitionStatement', 'I am requesting reinstatement and have addressed the conduct that resulted in my ban.')
        ->call('requestReinstatement', $ban->id)
        ->assertHasNoErrors();

    $ban->refresh();

    expect($ban->review_requested_at)->not->toBeNull()
        ->and($ban->member_response)->toContain('requesting reinstatement');
    Notification::assertSentTo(
        $member,
        MembershipReinstatementPetitionSubmittedNotification::class,
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

test('administrator cannot decide reinstatement petition', function () {
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
});

test('direct ban resolves an active membership probation', function () {
    Notification::fake();

    $superAdmin = banLifecycleUser([
        'is_super_admin' => true,
        'email' => 'super-admin@example.com',
    ]);

    $member = banLifecycleUser([
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
            'banReason',
            'The documented conduct warrants escalation from probation to an IRDI ban.'
        )
        ->call('banMember')
        ->assertHasNoErrors();

    $member->refresh();
    $probation->refresh();

    expect($member->membership_status)->toBe('banned')
        ->and($probation->status)->toBe('resolved')
        ->and($probation->resolved_at)->not->toBeNull()
        ->and($probation->resolved_by)->toBe($superAdmin->id);

    $this->assertDatabaseHas('staff_activity_logs', [
        'target_user_id' => $member->id,
        'action' => 'membership_probation_escalated',
        'subject_type' => MembershipEnforcement::class,
        'subject_id' => $probation->id,
    ]);

    $this->assertDatabaseHas('membership_enforcements', [
        'user_id' => $member->id,
        'action' => 'banned',
        'status' => 'active',
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
});
