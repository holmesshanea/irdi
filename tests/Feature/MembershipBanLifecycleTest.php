<?php

use App\Models\BanIdentifier;
use App\Models\MembershipEnforcement;
use App\Models\User;
use Illuminate\Database\Eloquent\ModelNotFoundException;
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
    $superAdmin = banLifecycleUser(['is_super_admin' => true]);
    $member = banLifecycleUser(['membership_status' => 'banned']);
    $ban = activeBan($member, [
        'review_requested_at' => now()->subDay(),
        'member_response' => 'Please consider my reinstatement petition.',
        'member_responded_at' => now()->subDay(),
    ]);

    $this->actingAs($superAdmin);

    Livewire::test('admin.members.show', ['user' => $member])
        ->set('reinstatementDenialReason', 'The current record does not support reinstatement at this time.')
        ->call('denyReinstatement')
        ->assertHasNoErrors();

    expect($member->fresh()->membership_status)->toBe('banned')
        ->and($ban->fresh()->status)->toBe('active');

    $this->assertDatabaseHas('membership_enforcements', [
        'user_id' => $member->id,
        'action' => 'reinstatement_denied',
        'status' => 'resolved',
    ]);
});

test('super administrator can reinstate banned member on probation', function () {
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

    expect($member->membership_status)->toBe('active')
        ->and($member->isOnProbation())->toBeTrue()
        ->and($member->isInGoodStanding())->toBeFalse()
        ->and($ban->status)->toBe('resolved');

    expect(BanIdentifier::query()->where('user_id', $member->id)->exists())->toBeFalse();
});

test('super administrator can exceptionally reinstate directly to active membership', function () {
    $superAdmin = banLifecycleUser(['is_super_admin' => true]);
    $member = banLifecycleUser(['membership_status' => 'banned']);
    $ban = activeBan($member, [
        'review_requested_at' => now()->subDay(),
        'member_response' => 'Please consider my reinstatement petition.',
        'member_responded_at' => now()->subDay(),
    ]);

    $this->actingAs($superAdmin);

    Livewire::test('admin.members.show', ['user' => $member])
        ->set('unbanReason', 'Exceptional evidence supports direct reinstatement without a probation period.')
        ->call('unbanMember')
        ->assertHasNoErrors();

    $member->refresh();

    expect($member->membership_status)->toBe('active')
        ->and($member->isInGoodStanding())->toBeTrue()
        ->and($ban->fresh()->status)->toBe('resolved');
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
