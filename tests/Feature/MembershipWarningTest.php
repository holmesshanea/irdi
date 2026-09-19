<?php

use App\Models\MembershipEnforcement;
use App\Models\StaffActivityLog;
use App\Models\User;
use App\Notifications\MembershipWarningIssuedNotification;
use App\Notifications\MembershipWarningResolvedNotification;
use App\Notifications\MembershipWarningWithdrawnNotification;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\Notification;
use Livewire\Livewire;

function membershipWarningUser(array $attributes = []): User
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

test('administrator can issue formal warning to active ordinary member', function () {
    Notification::fake();

    $administrator = membershipWarningUser([
        'is_admin' => true,
    ]);

    $member = membershipWarningUser();

    $this->actingAs($administrator);

    Livewire::test('admin.members.show', ['user' => $member])
        ->set('warningReason', 'Repeated conduct requires a formal corrective warning.')
        ->set('warningDetails', 'Internal administrative context for this warning.')
        ->set('warningRequirements', 'Review the Code of Ethics and confirm future compliance.')
        ->call('issueWarning')
        ->assertHasNoErrors();

    $warning = MembershipEnforcement::query()
        ->where('user_id', $member->id)
        ->where('action', 'warning')
        ->sole();

    expect($warning->admin_id)->toBe($administrator->id)
        ->and($warning->status)->toBe('active')
        ->and($warning->reason)->toBe('Repeated conduct requires a formal corrective warning.')
        ->and($warning->details)->toBe('Internal administrative context for this warning.')
        ->and($warning->restoration_requirements)->toBe('Review the Code of Ethics and confirm future compliance.')
        ->and($warning->starts_at)->not->toBeNull()
        ->and($warning->resolved_at)->toBeNull()
        ->and($member->fresh()->membership_status)->toBe('active')
        ->and($member->fresh()->isInGoodStanding())->toBeTrue();

    Notification::assertSentTo(
        $member,
        MembershipWarningIssuedNotification::class,
    );
});

test('formal warning leaves active member in good standing', function () {
    Notification::fake();

    $administrator = membershipWarningUser([
        'is_admin' => true,
    ]);

    $member = membershipWarningUser();

    $this->actingAs($administrator);

    Livewire::test('admin.members.show', ['user' => $member])
        ->set('warningReason', 'A formal warning is required for corrective purposes.')
        ->call('issueWarning')
        ->assertHasNoErrors();

    $member->refresh();

    expect($member->membership_status)->toBe('active')
        ->and($member->hasActiveMembershipWarning())->toBeTrue()
        ->and($member->isInGoodStanding())->toBeTrue();
});

test('moderator cannot issue formal warning', function () {
    Notification::fake();

    $moderator = membershipWarningUser([
        'is_moderator' => true,
    ]);

    $member = membershipWarningUser();

    $this->actingAs($moderator);

    Livewire::test('admin.members.show', ['user' => $member])
        ->set('warningReason', 'Moderators must not be allowed to issue formal warnings.')
        ->call('issueWarning')
        ->assertStatus(403);

    expect(
        MembershipEnforcement::query()
            ->where('user_id', $member->id)
            ->where('action', 'warning')
            ->exists()
    )->toBeFalse();

    Notification::assertNothingSent();
});

test('administrator cannot issue formal warning to another administrator', function () {
    Notification::fake();

    $administrator = membershipWarningUser([
        'is_admin' => true,
    ]);

    $otherAdministrator = membershipWarningUser([
        'is_admin' => true,
    ]);

    $this->actingAs($administrator);

    Livewire::test('admin.members.show', ['user' => $otherAdministrator])
        ->set('warningReason', 'Administrators must not discipline peer administrators.')
        ->call('issueWarning')
        ->assertStatus(403);

    expect(
        MembershipEnforcement::query()
            ->where('user_id', $otherAdministrator->id)
            ->where('action', 'warning')
            ->exists()
    )->toBeFalse();

    Notification::assertNothingSent();
});

test('super administrator can issue formal warning to administrator', function () {
    Notification::fake();

    $superAdmin = membershipWarningUser([
        'is_super_admin' => true,
    ]);

    $administrator = membershipWarningUser([
        'is_admin' => true,
    ]);

    $this->actingAs($superAdmin);

    Livewire::test('admin.members.show', ['user' => $administrator])
        ->set('warningReason', 'Super Administrator warning authority is permitted here.')
        ->call('issueWarning')
        ->assertHasNoErrors();

    $warning = MembershipEnforcement::query()
        ->where('user_id', $administrator->id)
        ->where('action', 'warning')
        ->sole();

    expect($warning->admin_id)->toBe($superAdmin->id)
        ->and($warning->status)->toBe('active');

    Notification::assertSentTo(
        $administrator,
        MembershipWarningIssuedNotification::class,
    );
});

test('administrator can resolve active formal warning', function () {
    Notification::fake();
    $administrator = membershipWarningUser([
        'is_admin' => true,
    ]);

    $member = membershipWarningUser();

    $originalStartsAt = now()->subDays(14);

    $warning = MembershipEnforcement::create([
        'user_id' => $member->id,
        'admin_id' => $administrator->id,
        'action' => 'warning',
        'status' => 'active',
        'reason' => 'This warning will be resolved after corrective action.',
        'details' => 'The member received documented guidance about the conduct that resulted in the warning.',
        'starts_at' => $originalStartsAt,
        'restoration_requirements' => 'Complete the documented corrective action and avoid repeating the conduct.',
    ]);

    $this->actingAs($administrator);

    Livewire::test('admin.members.show', ['user' => $member])
        ->call('resolveWarning', $warning->id)
        ->assertHasNoErrors();

    $warning->refresh();

    expect($warning->status)->toBe('resolved')
        ->and($warning->resolved_at)->not->toBeNull()
        ->and($warning->resolved_by)->toBe($administrator->id)
        ->and($warning->admin_id)->toBe($administrator->id)
        ->and($warning->reason)->toBe('This warning will be resolved after corrective action.')
        ->and($warning->details)->toBe('The member received documented guidance about the conduct that resulted in the warning.')
        ->and($warning->starts_at?->toDateTimeString())->toBe($originalStartsAt->toDateTimeString())
        ->and($warning->restoration_requirements)->toBe('Complete the documented corrective action and avoid repeating the conduct.')
        ->and($member->fresh()->hasActiveMembershipWarning())->toBeFalse()
        ->and($member->fresh()->isInGoodStanding())->toBeTrue();

    $this->assertDatabaseHas('staff_activity_logs', [
        'actor_id' => $administrator->id,
        'target_user_id' => $member->id,
        'action' => 'membership_warning_resolved',
        'subject_type' => MembershipEnforcement::class,
        'subject_id' => $warning->id,
    ]);

    Notification::assertSentTo(
        $member,
        MembershipWarningResolvedNotification::class,
    );
});

test('administrator can withdraw active formal warning', function () {
    Notification::fake();
    $administrator = membershipWarningUser([
        'is_admin' => true,
    ]);

    $member = membershipWarningUser();

    $originalStartsAt = now()->subDays(7);

    $warning = MembershipEnforcement::create([
        'user_id' => $member->id,
        'admin_id' => $administrator->id,
        'action' => 'warning',
        'status' => 'active',
        'reason' => 'This warning will be withdrawn after review.',
        'details' => 'The original warning details must remain intact after the warning is withdrawn.',
        'starts_at' => $originalStartsAt,
        'restoration_requirements' => 'Complete the originally documented corrective requirements.',
    ]);

    $this->actingAs($administrator);

    Livewire::test('admin.members.show', ['user' => $member])
        ->call('withdrawWarning', $warning->id)
        ->assertHasNoErrors();

    $warning->refresh();

    expect($warning->status)->toBe('withdrawn')
        ->and($warning->resolved_at)->not->toBeNull()
        ->and($warning->resolved_by)->toBe($administrator->id)
        ->and($warning->admin_id)->toBe($administrator->id)
        ->and($warning->reason)->toBe('This warning will be withdrawn after review.')
        ->and($warning->details)->toBe('The original warning details must remain intact after the warning is withdrawn.')
        ->and($warning->starts_at?->toDateTimeString())->toBe($originalStartsAt->toDateTimeString())
        ->and($warning->restoration_requirements)->toBe('Complete the originally documented corrective requirements.')
        ->and($member->fresh()->hasActiveMembershipWarning())->toBeFalse()
        ->and($member->fresh()->isInGoodStanding())->toBeTrue();

    $this->assertDatabaseHas('staff_activity_logs', [
        'actor_id' => $administrator->id,
        'target_user_id' => $member->id,
        'action' => 'membership_warning_withdrawn',
        'subject_type' => MembershipEnforcement::class,
        'subject_id' => $warning->id,
    ]);

    Notification::assertSentTo(
        $member,
        MembershipWarningWithdrawnNotification::class,
    );
});

test('warning resolution rolls back completely when staff activity creation fails', function () {
    Notification::fake();

    $administrator = membershipWarningUser([
        'is_admin' => true,
    ]);

    $member = membershipWarningUser();

    $originalStartsAt = now()->subDays(14);

    $warning = MembershipEnforcement::create([
        'user_id' => $member->id,
        'admin_id' => $administrator->id,
        'action' => 'warning',
        'status' => 'active',
        'reason' => 'This warning must remain active if its resolution audit record cannot be created.',
        'details' => 'Historical warning details must survive a failed resolution transaction.',
        'starts_at' => $originalStartsAt,
        'restoration_requirements' => 'Complete the original corrective requirements.',
    ]);

    StaffActivityLog::creating(function (StaffActivityLog $activity): void {
        if ($activity->action === 'membership_warning_resolved') {
            throw new RuntimeException('Forced warning resolution activity failure.');
        }
    });

    $this->actingAs($administrator);

    expect(fn () => Livewire::test('admin.members.show', ['user' => $member])
        ->call('resolveWarning', $warning->id))
        ->toThrow(RuntimeException::class, 'Forced warning resolution activity failure.');

    $warning->refresh();

    expect($warning->status)->toBe('active')
        ->and($warning->resolved_at)->toBeNull()
        ->and($warning->resolved_by)->toBeNull()
        ->and($warning->admin_id)->toBe($administrator->id)
        ->and($warning->reason)->toBe('This warning must remain active if its resolution audit record cannot be created.')
        ->and($warning->details)->toBe('Historical warning details must survive a failed resolution transaction.')
        ->and($warning->starts_at?->toDateTimeString())->toBe($originalStartsAt->toDateTimeString())
        ->and($warning->restoration_requirements)->toBe('Complete the original corrective requirements.')
        ->and($member->fresh()->hasActiveMembershipWarning())->toBeTrue()
        ->and($member->fresh()->isInGoodStanding())->toBeTrue();

    $this->assertDatabaseMissing('staff_activity_logs', [
        'actor_id' => $administrator->id,
        'target_user_id' => $member->id,
        'action' => 'membership_warning_resolved',
        'subject_type' => MembershipEnforcement::class,
        'subject_id' => $warning->id,
    ]);

    Notification::assertNothingSent();
});

test('warning withdrawal rolls back completely when staff activity creation fails', function () {
    Notification::fake();

    $administrator = membershipWarningUser([
        'is_admin' => true,
    ]);

    $member = membershipWarningUser();

    $originalStartsAt = now()->subDays(7);

    $warning = MembershipEnforcement::create([
        'user_id' => $member->id,
        'admin_id' => $administrator->id,
        'action' => 'warning',
        'status' => 'active',
        'reason' => 'This warning must remain active if its withdrawal audit record cannot be created.',
        'details' => 'Historical warning details must survive a failed withdrawal transaction.',
        'starts_at' => $originalStartsAt,
        'restoration_requirements' => 'Complete the originally documented corrective requirements.',
    ]);

    StaffActivityLog::creating(function (StaffActivityLog $activity): void {
        if ($activity->action === 'membership_warning_withdrawn') {
            throw new RuntimeException('Forced warning withdrawal activity failure.');
        }
    });

    $this->actingAs($administrator);

    expect(fn () => Livewire::test('admin.members.show', ['user' => $member])
        ->call('withdrawWarning', $warning->id))
        ->toThrow(RuntimeException::class, 'Forced warning withdrawal activity failure.');

    $warning->refresh();

    expect($warning->status)->toBe('active')
        ->and($warning->resolved_at)->toBeNull()
        ->and($warning->resolved_by)->toBeNull()
        ->and($warning->admin_id)->toBe($administrator->id)
        ->and($warning->reason)->toBe('This warning must remain active if its withdrawal audit record cannot be created.')
        ->and($warning->details)->toBe('Historical warning details must survive a failed withdrawal transaction.')
        ->and($warning->starts_at?->toDateTimeString())->toBe($originalStartsAt->toDateTimeString())
        ->and($warning->restoration_requirements)->toBe('Complete the originally documented corrective requirements.')
        ->and($member->fresh()->hasActiveMembershipWarning())->toBeTrue()
        ->and($member->fresh()->isInGoodStanding())->toBeTrue();

    $this->assertDatabaseMissing('staff_activity_logs', [
        'actor_id' => $administrator->id,
        'target_user_id' => $member->id,
        'action' => 'membership_warning_withdrawn',
        'subject_type' => MembershipEnforcement::class,
        'subject_id' => $warning->id,
    ]);

    Notification::assertNothingSent();
});

test('warning cannot be resolved through a different member component', function () {
    $administrator = membershipWarningUser([
        'is_admin' => true,
    ]);

    $warningOwner = membershipWarningUser();
    $otherMember = membershipWarningUser();

    $warning = MembershipEnforcement::create([
        'user_id' => $warningOwner->id,
        'admin_id' => $administrator->id,
        'action' => 'warning',
        'status' => 'active',
        'reason' => 'This warning belongs only to its actual member.',
        'starts_at' => now(),
    ]);

    $this->actingAs($administrator);

    expect(fn () => Livewire::test('admin.members.show', ['user' => $otherMember])
        ->call('resolveWarning', $warning->id))
        ->toThrow(ModelNotFoundException::class);

    expect($warning->fresh()->status)->toBe('active')
        ->and($warning->fresh()->resolved_at)->toBeNull();
});

test('warning cannot be withdrawn through a different member component', function () {
    $administrator = membershipWarningUser([
        'is_admin' => true,
    ]);

    $warningOwner = membershipWarningUser();
    $otherMember = membershipWarningUser();

    $warning = MembershipEnforcement::create([
        'user_id' => $warningOwner->id,
        'admin_id' => $administrator->id,
        'action' => 'warning',
        'status' => 'active',
        'reason' => 'This warning belongs only to its actual member.',
        'starts_at' => now(),
    ]);

    $this->actingAs($administrator);

    expect(fn () => Livewire::test('admin.members.show', ['user' => $otherMember])
        ->call('withdrawWarning', $warning->id))
        ->toThrow(ModelNotFoundException::class);

    expect($warning->fresh()->status)->toBe('active')
        ->and($warning->fresh()->resolved_at)->toBeNull();
});

test('formal warning requires valid reason', function () {
    Notification::fake();

    $administrator = membershipWarningUser([
        'is_admin' => true,
    ]);

    $member = membershipWarningUser();

    $this->actingAs($administrator);

    Livewire::test('admin.members.show', ['user' => $member])
        ->set('warningReason', 'Too short')
        ->call('issueWarning')
        ->assertHasErrors(['warningReason']);

    expect(
        MembershipEnforcement::query()
            ->where('user_id', $member->id)
            ->where('action', 'warning')
            ->exists()
    )->toBeFalse();

    Notification::assertNothingSent();
});

test('formal warning cannot be issued to non active member', function (string $membershipStatus) {
    Notification::fake();

    $superAdmin = membershipWarningUser([
        'is_super_admin' => true,
    ]);

    $member = membershipWarningUser([
        'membership_status' => $membershipStatus,
    ]);

    $this->actingAs($superAdmin);

    Livewire::test('admin.members.show', ['user' => $member])
        ->set('warningReason', 'Formal warnings apply only to currently active members.')
        ->call('issueWarning')
        ->assertHasErrors(['warningReason']);

    expect(
        MembershipEnforcement::query()
            ->where('user_id', $member->id)
            ->where('action', 'warning')
            ->exists()
    )->toBeFalse();

    Notification::assertNothingSent();
})->with([
    'suspended',
    'banned',
]);
