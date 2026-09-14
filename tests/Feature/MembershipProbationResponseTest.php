<?php

use App\Models\MembershipEnforcement;
use App\Models\User;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

function probationResponseUser(array $attributes = []): User
{
    return User::factory()->create(array_merge([
        'membership_status' => 'active',
        'member_since' => now()->subYear(),
        'ethics_agreed_at' => now()->subYear(),
        'best_practices_agreed_at' => now()->subYear(),
    ], $attributes));
}

function activeProbationFor(
    User $member,
    ?User $admin = null,
    array $attributes = []
): MembershipEnforcement {
    $admin ??= probationResponseUser([
        'is_admin' => true,
    ]);

    return MembershipEnforcement::create(array_merge([
        'user_id' => $member->id,
        'admin_id' => $admin->id,
        'action' => 'probation',
        'status' => 'active',
        'reason' => 'Formal membership probation is currently in effect.',
        'details' => 'Internal staff-only information must not be shown to the member.',
        'starts_at' => now()->subDays(30),
        'eligible_for_review_at' => now()->addDays(60),
        'restoration_requirements' => 'Complete the required corrective actions before formal review.',
    ], $attributes));
}

test('member can submit private response to own active probation', function () {
    $member = probationResponseUser();

    $probation = activeProbationFor($member);

    $this->actingAs($member);

    Livewire::test('account.membership-probation')
        ->set('response', 'I acknowledge this probation and will complete the required corrective actions.')
        ->call('submitResponse', $probation->id)
        ->assertHasNoErrors();

    $probation->refresh();

    expect($probation->member_response)
        ->toBe('I acknowledge this probation and will complete the required corrective actions.')
        ->and($probation->member_responded_at)
        ->not->toBeNull();
});

test('member cannot respond to another members probation', function () {
    $member = probationResponseUser();
    $otherMember = probationResponseUser();

    $probation = activeProbationFor($otherMember);

    $this->actingAs($member);

    expect(fn () => Livewire::test('account.membership-probation')
        ->set('response', 'This response should not be accepted for another member.')
        ->call('submitResponse', $probation->id))
        ->toThrow(ModelNotFoundException::class);

    expect($probation->fresh()->member_response)->toBeNull();
});

test('member cannot respond to resolved probation', function () {
    $member = probationResponseUser();

    $probation = activeProbationFor($member, attributes: [
        'status' => 'resolved',
        'resolved_at' => now(),
    ]);

    $this->actingAs($member);

    expect(fn () => Livewire::test('account.membership-probation')
        ->set('response', 'This response should not be accepted after probation is resolved.')
        ->call('submitResponse', $probation->id))
        ->toThrow(ModelNotFoundException::class);

    expect($probation->fresh()->member_response)->toBeNull();
});

test('member cannot respond to future probation', function () {
    $member = probationResponseUser();

    $probation = activeProbationFor($member, attributes: [
        'starts_at' => now()->addDay(),
    ]);

    $this->actingAs($member);

    expect(fn () => Livewire::test('account.membership-probation')
        ->set('response', 'This response should not be accepted before probation begins.')
        ->call('submitResponse', $probation->id))
        ->toThrow(ModelNotFoundException::class);

    expect($probation->fresh()->member_response)->toBeNull();
});

test('member cannot overwrite existing probation response', function () {
    $member = probationResponseUser();

    $probation = activeProbationFor($member, attributes: [
        'member_response' => 'My original probation response remains on record.',
        'member_responded_at' => now()->subDay(),
    ]);

    $this->actingAs($member);

    Livewire::test('account.membership-probation')
        ->set('response', 'Attempting to replace the existing response.')
        ->call('submitResponse', $probation->id)
        ->assertHasErrors(['response']);

    expect($probation->fresh()->member_response)
        ->toBe('My original probation response remains on record.');
});

test('probation response requires minimum valid content', function () {
    $member = probationResponseUser();

    $probation = activeProbationFor($member);

    $this->actingAs($member);

    Livewire::test('account.membership-probation')
        ->set('response', 'Too short')
        ->call('submitResponse', $probation->id)
        ->assertHasErrors(['response']);

    expect($probation->fresh()->member_response)->toBeNull();
});

test('member cannot request formal review before eligibility date', function () {
    $member = probationResponseUser();

    $probation = activeProbationFor($member, attributes: [
        'eligible_for_review_at' => now()->addDay(),
    ]);

    $this->actingAs($member);

    Livewire::test('account.membership-probation')
        ->call('requestReview', $probation->id)
        ->assertHasErrors(['reviewRequest']);

    expect($probation->fresh()->review_requested_at)->toBeNull();
});

test('member can request formal review once eligible', function () {
    $member = probationResponseUser();

    $probation = activeProbationFor($member, attributes: [
        'starts_at' => now()->subDays(100),
        'eligible_for_review_at' => now()->subDays(10),
    ]);

    $this->actingAs($member);

    Livewire::test('account.membership-probation')
        ->call('requestReview', $probation->id)
        ->assertHasNoErrors();

    expect($probation->fresh()->review_requested_at)->not->toBeNull();
});

test('member cannot request formal review twice', function () {
    $member = probationResponseUser();

    $probation = activeProbationFor($member, attributes: [
        'starts_at' => now()->subDays(100),
        'eligible_for_review_at' => now()->subDays(10),
        'review_requested_at' => now()->subDay(),
    ]);

    $this->actingAs($member);

    Livewire::test('account.membership-probation')
        ->call('requestReview', $probation->id)
        ->assertHasErrors(['reviewRequest']);

    expect($probation->fresh()->review_requested_at?->toDateString())
        ->toBe(now()->subDay()->toDateString());
});

test('member cannot request review of another members probation', function () {
    $member = probationResponseUser();
    $otherMember = probationResponseUser();

    $probation = activeProbationFor($otherMember, attributes: [
        'starts_at' => now()->subDays(100),
        'eligible_for_review_at' => now()->subDays(10),
    ]);

    $this->actingAs($member);

    expect(fn () => Livewire::test('account.membership-probation')
        ->call('requestReview', $probation->id))
        ->toThrow(ModelNotFoundException::class);

    expect($probation->fresh()->review_requested_at)->toBeNull();
});

test('only current active probation is rendered', function () {
    $member = probationResponseUser();

    $active = activeProbationFor($member, attributes: [
        'reason' => 'This active probation should be visible to the member.',
    ]);

    activeProbationFor($member, attributes: [
        'status' => 'resolved',
        'resolved_at' => now()->subDay(),
        'reason' => 'This resolved probation should not be visible.',
    ]);

    activeProbationFor($member, attributes: [
        'starts_at' => now()->addDay(),
        'reason' => 'This future probation should not be visible.',
    ]);

    $this->actingAs($member);

    Livewire::test('account.membership-probation')
        ->assertSee($active->reason)
        ->assertDontSee('This resolved probation should not be visible.')
        ->assertDontSee('This future probation should not be visible.');
});

test('member facing probation does not expose staff only details', function () {
    $member = probationResponseUser();

    activeProbationFor($member, attributes: [
        'details' => 'Confidential staff investigation notes must remain private.',
    ]);

    $this->actingAs($member);

    Livewire::test('account.membership-probation')
        ->assertDontSee('Confidential staff investigation notes must remain private.');
});
