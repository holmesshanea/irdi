<?php

use App\Models\MembershipEnforcement;
use App\Models\User;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Livewire\Livewire;

function suspensionAccountUser(array $attributes = []): User
{
    return User::factory()->create(array_merge([
        'membership_status' => 'suspended',
        'member_since' => now()->toDateString(),
        'ethics_agreed_at' => now(),
        'best_practices_agreed_at' => now(),
        'is_admin' => false,
        'is_super_admin' => false,
        'is_moderator' => false,
    ], $attributes));
}

function activeMemberSuspension(User $member, array $attributes = []): MembershipEnforcement
{
    return MembershipEnforcement::create(array_merge([
        'user_id' => $member->id,
        'admin_id' => null,
        'action' => 'suspended',
        'status' => 'active',
        'reason' => 'A documented membership concern requires temporary suspension.',
        'starts_at' => now()->subDays(30),
        'eligible_for_review_at' => now()->addDays(60),
    ], $attributes));
}

test('suspended member can submit one private suspension response', function () {
    $member = suspensionAccountUser();
    $suspension = activeMemberSuspension($member);

    $this->actingAs($member);

    Livewire::test('account.membership-suspension')
        ->set(
            "responses.{$suspension->id}",
            'I would like this response included in the private IRDI enforcement record.'
        )
        ->call('submitResponse', $suspension->id)
        ->assertHasNoErrors();

    $suspension->refresh();

    expect($suspension->member_response)
        ->toBe('I would like this response included in the private IRDI enforcement record.')
        ->and($suspension->member_responded_at)
        ->not->toBeNull();
});

test('suspended member cannot submit a second suspension response', function () {
    $member = suspensionAccountUser();
    $suspension = activeMemberSuspension($member, [
        'member_response' => 'My original response remains part of the private record.',
        'member_responded_at' => now()->subDay(),
    ]);

    $this->actingAs($member);

    Livewire::test('account.membership-suspension')
        ->set(
            "responses.{$suspension->id}",
            'This second response must not replace the original response.'
        )
        ->call('submitResponse', $suspension->id)
        ->assertHasErrors(["responses.{$suspension->id}"]);

    expect($suspension->fresh()->member_response)
        ->toBe('My original response remains part of the private record.');
});

test('member cannot respond to another members suspension', function () {
    $member = suspensionAccountUser();
    $otherMember = suspensionAccountUser();
    $suspension = activeMemberSuspension($otherMember);

    $this->actingAs($member);

    expect(fn () => Livewire::test('account.membership-suspension')
        ->set(
            "responses.{$suspension->id}",
            'This response must never be attached to another member suspension.'
        )
        ->call('submitResponse', $suspension->id))
        ->toThrow(ModelNotFoundException::class);

    expect($suspension->fresh()->member_response)->toBeNull();
});

test('suspended member can request formal review once eligible', function () {
    $member = suspensionAccountUser();
    $suspension = activeMemberSuspension($member, [
        'eligible_for_review_at' => now()->subMinute(),
    ]);

    $this->actingAs($member);

    Livewire::test('account.membership-suspension')
        ->call('requestReview', $suspension->id)
        ->assertHasNoErrors();

    expect($suspension->fresh()->review_requested_at)->not->toBeNull();
});

test('suspended member cannot request formal review before eligibility date', function () {
    $member = suspensionAccountUser();
    $suspension = activeMemberSuspension($member, [
        'eligible_for_review_at' => now()->addDays(30),
    ]);

    $this->actingAs($member);

    Livewire::test('account.membership-suspension')
        ->call('requestReview', $suspension->id)
        ->assertHasErrors(["review.{$suspension->id}"]);

    expect($suspension->fresh()->review_requested_at)->toBeNull();
});

test('indefinite suspension is not immediately eligible for formal review', function () {
    $member = suspensionAccountUser();
    $suspension = activeMemberSuspension($member, [
        'eligible_for_review_at' => null,
    ]);

    $this->actingAs($member);

    Livewire::test('account.membership-suspension')
        ->call('requestReview', $suspension->id)
        ->assertHasErrors(["review.{$suspension->id}"]);

    expect($suspension->fresh()->review_requested_at)->toBeNull();
});

test('suspended member cannot request the same formal review twice', function () {
    $member = suspensionAccountUser();
    $suspension = activeMemberSuspension($member, [
        'eligible_for_review_at' => now()->subDay(),
        'review_requested_at' => now()->subHour(),
    ]);

    $originalRequest = $suspension->review_requested_at;

    $this->actingAs($member);

    Livewire::test('account.membership-suspension')
        ->call('requestReview', $suspension->id)
        ->assertHasErrors(["review.{$suspension->id}"]);

    expect($suspension->fresh()->review_requested_at?->toDateTimeString())
        ->toBe($originalRequest?->toDateTimeString());
});

test('suspended member cannot request review of a resolved suspension', function () {
    $member = suspensionAccountUser();

    $suspension = activeMemberSuspension($member, [
        'status' => 'resolved',
        'eligible_for_review_at' => now()->subDay(),
        'resolved_at' => now()->subHour(),
    ]);

    $this->actingAs($member);

    expect(fn () => Livewire::test('account.membership-suspension')
        ->call('requestReview', $suspension->id))
        ->toThrow(ModelNotFoundException::class);

    expect($suspension->fresh()->review_requested_at)->toBeNull();
});

test('member cannot request review for another members suspension', function () {
    $member = suspensionAccountUser();
    $otherMember = suspensionAccountUser();
    $suspension = activeMemberSuspension($otherMember, [
        'eligible_for_review_at' => now()->subDay(),
    ]);

    $this->actingAs($member);

    expect(fn () => Livewire::test('account.membership-suspension')
        ->call('requestReview', $suspension->id))
        ->toThrow(ModelNotFoundException::class);

    expect($suspension->fresh()->review_requested_at)->toBeNull();
});
