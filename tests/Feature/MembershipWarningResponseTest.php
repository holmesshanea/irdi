
<?php

use App\Models\MembershipEnforcement;
use App\Models\User;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Livewire\Livewire;

function warningResponseUser(array $attributes = []): User
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

test('member can submit response to own active formal warning', function () {
    $administrator = warningResponseUser([
        'is_admin' => true,
    ]);

    $member = warningResponseUser();

    $warning = MembershipEnforcement::create([
        'user_id' => $member->id,
        'admin_id' => $administrator->id,
        'action' => 'warning',
        'status' => 'active',
        'reason' => 'A formal warning requiring a member response.',
        'starts_at' => now(),
    ]);

    $this->actingAs($member);

    Livewire::test('account.membership-warnings')
        ->set("responses.{$warning->id}", 'I understand the concern and will follow the requested guidance.')
        ->call('submitResponse', $warning->id)
        ->assertHasNoErrors();

    $warning->refresh();

    expect($warning->member_response)
        ->toBe('I understand the concern and will follow the requested guidance.')
        ->and($warning->member_responded_at)
        ->not->toBeNull();
});

test('member cannot submit response to another members warning', function () {
    $administrator = warningResponseUser([
        'is_admin' => true,
    ]);

    $warningOwner = warningResponseUser();
    $otherMember = warningResponseUser();

    $warning = MembershipEnforcement::create([
        'user_id' => $warningOwner->id,
        'admin_id' => $administrator->id,
        'action' => 'warning',
        'status' => 'active',
        'reason' => 'This warning belongs to another member.',
        'starts_at' => now(),
    ]);

    $this->actingAs($otherMember);

    expect(fn () => Livewire::test('account.membership-warnings')
        ->set("responses.{$warning->id}", 'This response should never be accepted.')
        ->call('submitResponse', $warning->id))
        ->toThrow(ModelNotFoundException::class);

    $warning->refresh();

    expect($warning->member_response)->toBeNull()
        ->and($warning->member_responded_at)->toBeNull();
});

test('member cannot respond to resolved warning', function () {
    $administrator = warningResponseUser([
        'is_admin' => true,
    ]);

    $member = warningResponseUser();

    $warning = MembershipEnforcement::create([
        'user_id' => $member->id,
        'admin_id' => $administrator->id,
        'action' => 'warning',
        'status' => 'resolved',
        'reason' => 'This warning has already been resolved.',
        'starts_at' => now()->subDay(),
        'resolved_at' => now(),
        'resolved_by' => $administrator->id,
    ]);

    $this->actingAs($member);

    expect(fn () => Livewire::test('account.membership-warnings')
        ->set("responses.{$warning->id}", 'This response should never be accepted.')
        ->call('submitResponse', $warning->id))
        ->toThrow(ModelNotFoundException::class);

    expect($warning->fresh()->member_response)->toBeNull();
});

test('member cannot respond to future warning before start date', function () {
    $administrator = warningResponseUser([
        'is_admin' => true,
    ]);

    $member = warningResponseUser();

    $warning = MembershipEnforcement::create([
        'user_id' => $member->id,
        'admin_id' => $administrator->id,
        'action' => 'warning',
        'status' => 'active',
        'reason' => 'This warning has not started yet.',
        'starts_at' => now()->addDay(),
    ]);

    $this->actingAs($member);

    expect(fn () => Livewire::test('account.membership-warnings')
        ->set("responses.{$warning->id}", 'This response should not be accepted yet.')
        ->call('submitResponse', $warning->id))
        ->toThrow(ModelNotFoundException::class);

    expect($warning->fresh()->member_response)->toBeNull();
});

test('member cannot overwrite an existing warning response', function () {
    $administrator = warningResponseUser([
        'is_admin' => true,
    ]);

    $member = warningResponseUser();

    $warning = MembershipEnforcement::create([
        'user_id' => $member->id,
        'admin_id' => $administrator->id,
        'action' => 'warning',
        'status' => 'active',
        'reason' => 'A warning with an existing member response.',
        'starts_at' => now(),
        'member_response' => 'My original response remains part of the record.',
        'member_responded_at' => now()->subHour(),
    ]);

    $this->actingAs($member);

    Livewire::test('account.membership-warnings')
        ->set("responses.{$warning->id}", 'I am attempting to replace my original response.')
        ->call('submitResponse', $warning->id)
        ->assertHasErrors(["responses.{$warning->id}"]);

    $warning->refresh();

    expect($warning->member_response)
        ->toBe('My original response remains part of the record.');
});

test('warning response is required and must meet minimum length', function () {
    $administrator = warningResponseUser([
        'is_admin' => true,
    ]);

    $member = warningResponseUser();

    $warning = MembershipEnforcement::create([
        'user_id' => $member->id,
        'admin_id' => $administrator->id,
        'action' => 'warning',
        'status' => 'active',
        'reason' => 'A warning requiring a valid response.',
        'starts_at' => now(),
    ]);

    $this->actingAs($member);

    Livewire::test('account.membership-warnings')
        ->set("responses.{$warning->id}", 'Short')
        ->call('submitResponse', $warning->id)
        ->assertHasErrors(["responses.{$warning->id}"]);

    expect($warning->fresh()->member_response)->toBeNull();
});

test('only active current warnings are rendered for member', function () {
    $administrator = warningResponseUser([
        'is_admin' => true,
    ]);

    $member = warningResponseUser();

    MembershipEnforcement::create([
        'user_id' => $member->id,
        'admin_id' => $administrator->id,
        'action' => 'warning',
        'status' => 'active',
        'reason' => 'VISIBLE ACTIVE WARNING',
        'starts_at' => now(),
    ]);

    MembershipEnforcement::create([
        'user_id' => $member->id,
        'admin_id' => $administrator->id,
        'action' => 'warning',
        'status' => 'resolved',
        'reason' => 'HIDDEN RESOLVED WARNING',
        'starts_at' => now()->subDay(),
        'resolved_at' => now(),
        'resolved_by' => $administrator->id,
    ]);

    MembershipEnforcement::create([
        'user_id' => $member->id,
        'admin_id' => $administrator->id,
        'action' => 'warning',
        'status' => 'active',
        'reason' => 'HIDDEN FUTURE WARNING',
        'starts_at' => now()->addDay(),
    ]);

    $this->actingAs($member);

    Livewire::test('account.membership-warnings')
        ->assertSee('VISIBLE ACTIVE WARNING')
        ->assertDontSee('HIDDEN RESOLVED WARNING')
        ->assertDontSee('HIDDEN FUTURE WARNING');
});
