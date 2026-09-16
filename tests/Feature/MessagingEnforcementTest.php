<?php

use App\Models\MessagingEnforcement;
use App\Models\StaffActivityLog;
use App\Models\User;
use App\Notifications\MessagingRestoredNotification;
use App\Notifications\MessagingRestrictedNotification;
use Illuminate\Support\Facades\Notification;
use Livewire\Livewire;

function messagingEnforcementUser(array $attributes = []): User
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

test('administrator can restrict member messaging for seven days', function () {
    Notification::fake();
    $this->freezeTime();

    $administrator = messagingEnforcementUser([
        'is_admin' => true,
    ]);

    $member = messagingEnforcementUser();

    $this->actingAs($administrator);

    Livewire::test('admin.members.show', ['user' => $member])
        ->set('restrictionType', '7_days')
        ->set('enforcementReason', 'Repeated misuse of the member messaging system requires a temporary restriction.')
        ->call('disableMessaging')
        ->assertHasNoErrors();

    $member->refresh();

    expect($member->messaging_disabled_at?->toDateTimeString())
        ->toBe(now()->toDateTimeString())
        ->and($member->messaging_disabled_until?->toDateTimeString())
        ->toBe(now()->addDays(7)->toDateTimeString());

    $enforcement = MessagingEnforcement::query()
        ->where('user_id', $member->id)
        ->where('action', 'disabled')
        ->sole();

    expect($enforcement->reason)
        ->toBe('Repeated misuse of the member messaging system requires a temporary restriction.')
        ->and($enforcement->expires_at?->toDateTimeString())
        ->toBe(now()->addDays(7)->toDateTimeString());

    $activity = StaffActivityLog::query()
        ->where('actor_id', $administrator->id)
        ->where('target_user_id', $member->id)
        ->where('action', 'messaging_disabled')
        ->sole();

    expect($activity->subject_type)
        ->toBe($enforcement->getMorphClass())
        ->and($activity->subject_id)
        ->toBe($enforcement->id)
        ->and($activity->description)
        ->toBe('Disabled messaging for '.$member->name.'.')
        ->and($activity->metadata['reason'])
        ->toBe('Repeated misuse of the member messaging system requires a temporary restriction.')
        ->and($activity->metadata['restriction_type'])
        ->toBe('7_days');

    Notification::assertSentTo(
        $member,
        MessagingRestrictedNotification::class,
    );
});

test('administrator can manually restore restricted member messaging', function () {
    Notification::fake();

    $administrator = messagingEnforcementUser([
        'is_admin' => true,
    ]);

    $member = messagingEnforcementUser([
        'messaging_disabled_at' => now()->subDay(),
        'messaging_disabled_until' => now()->addDays(6),
    ]);

    MessagingEnforcement::create([
        'user_id' => $member->id,
        'admin_id' => $administrator->id,
        'message_report_id' => null,
        'action' => 'disabled',
        'reason' => 'Temporary messaging restriction requiring administrative review.',
        'expires_at' => now()->addDays(6),
    ]);

    $this->actingAs($administrator);

    Livewire::test('admin.members.show', ['user' => $member])
        ->set('restoreReason', 'The messaging restriction has been reviewed and may now be lifted.')
        ->call('restoreMessaging')
        ->assertHasNoErrors();

    $member->refresh();

    expect($member->messaging_disabled_at)->toBeNull()
        ->and($member->messaging_disabled_until)->toBeNull();

    $restoration = MessagingEnforcement::query()
        ->where('user_id', $member->id)
        ->where('action', 'restored')
        ->sole();

    expect($restoration->reason)
        ->toBe('The messaging restriction has been reviewed and may now be lifted.')
        ->and($restoration->expires_at)->toBeNull();

    $activity = StaffActivityLog::query()
        ->where('actor_id', $administrator->id)
        ->where('target_user_id', $member->id)
        ->where('action', 'messaging_restored')
        ->sole();

    expect($activity->subject_type)
        ->toBe($restoration->getMorphClass())
        ->and($activity->subject_id)
        ->toBe($restoration->id)
        ->and($activity->description)
        ->toBe('Restored messaging for '.$member->name.'.')
        ->and($activity->metadata['reason'])
        ->toBe('The messaging restriction has been reviewed and may now be lifted.');

    Notification::assertSentTo(
        $member,
        MessagingRestoredNotification::class,
    );
});

test('administrator can permanently restrict member messaging', function () {
    Notification::fake();
    $this->freezeTime();

    $administrator = messagingEnforcementUser([
        'is_admin' => true,
    ]);

    $member = messagingEnforcementUser();

    $this->actingAs($administrator);

    Livewire::test('admin.members.show', ['user' => $member])
        ->set('restrictionType', 'permanent')
        ->set('enforcementReason', 'Repeated serious misuse requires a permanent messaging restriction.')
        ->call('disableMessaging')
        ->assertHasNoErrors();

    $member->refresh();

    expect($member->messaging_disabled_at?->toDateTimeString())
        ->toBe(now()->toDateTimeString())
        ->and($member->messaging_disabled_until)
        ->toBeNull();

    $enforcement = MessagingEnforcement::query()
        ->where('user_id', $member->id)
        ->where('action', 'disabled')
        ->sole();

    expect($enforcement->reason)
        ->toBe('Repeated serious misuse requires a permanent messaging restriction.')
        ->and($enforcement->expires_at)
        ->toBeNull();

    Notification::assertSentTo(
        $member,
        MessagingRestrictedNotification::class,
    );
});

test('expired temporary messaging restriction is automatically restored', function () {
    Notification::fake();
    $this->freezeTime();

    $member = messagingEnforcementUser([
        'messaging_disabled_at' => now()->subDays(8),
        'messaging_disabled_until' => now()->subMinute(),
    ]);

    MessagingEnforcement::create([
        'user_id' => $member->id,
        'admin_id' => null,
        'message_report_id' => null,
        'action' => 'disabled',
        'reason' => 'Temporary messaging restriction.',
        'expires_at' => now()->subMinute(),
    ]);

    $this->artisan('messaging:restore-expired')
        ->expectsOutput('Restored messaging access for 1 member(s).')
        ->assertSuccessful();

    $member->refresh();

    expect($member->messaging_disabled_at)->toBeNull()
        ->and($member->messaging_disabled_until)->toBeNull();

    $restoration = MessagingEnforcement::query()
        ->where('user_id', $member->id)
        ->where('action', 'restored')
        ->sole();

    expect($restoration->admin_id)->toBeNull()
        ->and($restoration->message_report_id)->toBeNull()
        ->and($restoration->reason)
        ->toBe('Messaging access automatically restored after the temporary restriction expired.')
        ->and($restoration->expires_at)->toBeNull();

    Notification::assertSentTo(
        $member,
        MessagingRestoredNotification::class,
    );
});

test('unexpired temporary messaging restriction is not automatically restored', function () {
    Notification::fake();
    $this->freezeTime();

    $member = messagingEnforcementUser([
        'messaging_disabled_at' => now()->subDay(),
        'messaging_disabled_until' => now()->addDays(6),
    ]);

    MessagingEnforcement::create([
        'user_id' => $member->id,
        'admin_id' => null,
        'message_report_id' => null,
        'action' => 'disabled',
        'reason' => 'Temporary messaging restriction.',
        'expires_at' => now()->addDays(6),
    ]);

    $this->artisan('messaging:restore-expired')
        ->expectsOutput('Restored messaging access for 0 member(s).')
        ->assertSuccessful();

    $member->refresh();

    expect($member->messaging_disabled_at)->not->toBeNull()
        ->and($member->messaging_disabled_until?->toDateTimeString())
        ->toBe(now()->addDays(6)->toDateTimeString());

    expect(
        MessagingEnforcement::query()
            ->where('user_id', $member->id)
            ->where('action', 'restored')
            ->exists()
    )->toBeFalse();

    Notification::assertNotSentTo(
        $member,
        MessagingRestoredNotification::class,
    );
});

test('permanent messaging restriction is not automatically restored', function () {
    Notification::fake();
    $this->freezeTime();

    $member = messagingEnforcementUser([
        'messaging_disabled_at' => now()->subYear(),
        'messaging_disabled_until' => null,
    ]);

    MessagingEnforcement::create([
        'user_id' => $member->id,
        'admin_id' => null,
        'message_report_id' => null,
        'action' => 'disabled',
        'reason' => 'Permanent messaging restriction.',
        'expires_at' => null,
    ]);

    $this->artisan('messaging:restore-expired')
        ->expectsOutput('Restored messaging access for 0 member(s).')
        ->assertSuccessful();

    $member->refresh();

    expect($member->messaging_disabled_at)->not->toBeNull()
        ->and($member->messaging_disabled_until)->toBeNull();

    expect(
        MessagingEnforcement::query()
            ->where('user_id', $member->id)
            ->where('action', 'restored')
            ->exists()
    )->toBeFalse();

    Notification::assertNotSentTo(
        $member,
        MessagingRestoredNotification::class,
    );
});

test('moderator can restrict ordinary member messaging', function () {
    Notification::fake();

    $moderator = messagingEnforcementUser([
        'is_moderator' => true,
    ]);

    $member = messagingEnforcementUser();

    $this->actingAs($moderator);

    Livewire::test('admin.members.show', ['user' => $member])
        ->set('restrictionType', '7_days')
        ->set('enforcementReason', 'Documented messaging conduct requires a temporary restriction.')
        ->call('disableMessaging')
        ->assertHasNoErrors();

    $member->refresh();

    expect($member->messaging_disabled_at)->not->toBeNull()
        ->and($member->messaging_disabled_until)->not->toBeNull();

    $this->assertDatabaseHas('messaging_enforcements', [
        'user_id' => $member->id,
        'admin_id' => $moderator->id,
        'action' => 'disabled',
        'reason' => 'Documented messaging conduct requires a temporary restriction.',
    ]);

    Notification::assertSentTo(
        $member,
        MessagingRestrictedNotification::class,
    );
});

test('moderator cannot restrict another moderator messaging', function () {
    Notification::fake();

    $moderator = messagingEnforcementUser([
        'is_moderator' => true,
    ]);

    $targetModerator = messagingEnforcementUser([
        'is_moderator' => true,
    ]);

    $this->actingAs($moderator);

    Livewire::test('admin.members.show', ['user' => $targetModerator])
        ->set('restrictionType', '7_days')
        ->set('enforcementReason', 'This enforcement must not be permitted against equal-rank staff.')
        ->call('disableMessaging')
        ->assertForbidden();

    $targetModerator->refresh();

    expect($targetModerator->messaging_disabled_at)->toBeNull()
        ->and($targetModerator->messaging_disabled_until)->toBeNull();

    expect(
        MessagingEnforcement::query()
            ->where('user_id', $targetModerator->id)
            ->exists()
    )->toBeFalse();

    Notification::assertNotSentTo(
        $targetModerator,
        MessagingRestrictedNotification::class,
    );
});

test('administrator can restrict moderator messaging', function () {
    Notification::fake();

    $administrator = messagingEnforcementUser([
        'is_admin' => true,
    ]);

    $moderator = messagingEnforcementUser([
        'is_moderator' => true,
    ]);

    $this->actingAs($administrator);

    Livewire::test('admin.members.show', ['user' => $moderator])
        ->set('restrictionType', '7_days')
        ->set('enforcementReason', 'Documented messaging conduct requires a temporary staff messaging restriction.')
        ->call('disableMessaging')
        ->assertHasNoErrors();

    $moderator->refresh();

    expect($moderator->messaging_disabled_at)->not->toBeNull()
        ->and($moderator->messaging_disabled_until)->not->toBeNull();

    $this->assertDatabaseHas('messaging_enforcements', [
        'user_id' => $moderator->id,
        'admin_id' => $administrator->id,
        'action' => 'disabled',
        'reason' => 'Documented messaging conduct requires a temporary staff messaging restriction.',
    ]);

    Notification::assertSentTo(
        $moderator,
        MessagingRestrictedNotification::class,
    );
});

test('administrator cannot restrict another administrator messaging', function () {
    Notification::fake();

    $administrator = messagingEnforcementUser([
        'is_admin' => true,
    ]);

    $targetAdministrator = messagingEnforcementUser([
        'is_admin' => true,
    ]);

    $this->actingAs($administrator);

    Livewire::test('admin.members.show', ['user' => $targetAdministrator])
        ->set('restrictionType', '7_days')
        ->set('enforcementReason', 'This enforcement must not be permitted against equal-rank staff.')
        ->call('disableMessaging')
        ->assertForbidden();

    $targetAdministrator->refresh();

    expect($targetAdministrator->messaging_disabled_at)->toBeNull()
        ->and($targetAdministrator->messaging_disabled_until)->toBeNull();

    expect(
        MessagingEnforcement::query()
            ->where('user_id', $targetAdministrator->id)
            ->exists()
    )->toBeFalse();

    Notification::assertNotSentTo(
        $targetAdministrator,
        MessagingRestrictedNotification::class,
    );
});

test('super administrator can restrict administrator messaging', function () {
    Notification::fake();

    $superAdministrator = messagingEnforcementUser([
        'is_super_admin' => true,
    ]);

    $administrator = messagingEnforcementUser([
        'is_admin' => true,
    ]);

    $this->actingAs($superAdministrator);

    Livewire::test('admin.members.show', ['user' => $administrator])
        ->set('restrictionType', '7_days')
        ->set('enforcementReason', 'Documented messaging conduct requires an administrative messaging restriction.')
        ->call('disableMessaging')
        ->assertHasNoErrors();

    $administrator->refresh();

    expect($administrator->messaging_disabled_at)->not->toBeNull()
        ->and($administrator->messaging_disabled_until)->not->toBeNull();

    $this->assertDatabaseHas('messaging_enforcements', [
        'user_id' => $administrator->id,
        'admin_id' => $superAdministrator->id,
        'action' => 'disabled',
        'reason' => 'Documented messaging conduct requires an administrative messaging restriction.',
    ]);

    Notification::assertSentTo(
        $administrator,
        MessagingRestrictedNotification::class,
    );
});

test('administrator cannot restrict super administrator messaging', function () {
    Notification::fake();

    $administrator = messagingEnforcementUser([
        'is_admin' => true,
    ]);

    $superAdministrator = messagingEnforcementUser([
        'is_super_admin' => true,
    ]);

    $this->actingAs($administrator);

    Livewire::test('admin.members.show', ['user' => $superAdministrator])
        ->set('restrictionType', '7_days')
        ->set('enforcementReason', 'This enforcement must not be permitted against the Super Administrator.')
        ->call('disableMessaging')
        ->assertForbidden();

    $superAdministrator->refresh();

    expect($superAdministrator->messaging_disabled_at)->toBeNull()
        ->and($superAdministrator->messaging_disabled_until)->toBeNull();

    expect(
        MessagingEnforcement::query()
            ->where('user_id', $superAdministrator->id)
            ->exists()
    )->toBeFalse();

    Notification::assertNotSentTo(
        $superAdministrator,
        MessagingRestrictedNotification::class,
    );
});

test('moderator cannot restore another moderator messaging', function () {
    Notification::fake();

    $moderator = messagingEnforcementUser([
        'is_moderator' => true,
    ]);

    $targetModerator = messagingEnforcementUser([
        'is_moderator' => true,
        'messaging_disabled_at' => now()->subDay(),
        'messaging_disabled_until' => now()->addDays(6),
    ]);

    MessagingEnforcement::create([
        'user_id' => $targetModerator->id,
        'admin_id' => null,
        'message_report_id' => null,
        'action' => 'disabled',
        'reason' => 'Existing messaging restriction.',
        'expires_at' => now()->addDays(6),
    ]);

    $this->actingAs($moderator);

    Livewire::test('admin.members.show', ['user' => $targetModerator])
        ->set('restoreReason', 'This restoration must not be permitted against equal-rank staff.')
        ->call('restoreMessaging')
        ->assertForbidden();

    $targetModerator->refresh();

    expect($targetModerator->messaging_disabled_at)->not->toBeNull()
        ->and($targetModerator->messaging_disabled_until)->not->toBeNull();

    expect(
        MessagingEnforcement::query()
            ->where('user_id', $targetModerator->id)
            ->where('action', 'restored')
            ->exists()
    )->toBeFalse();

    Notification::assertNotSentTo(
        $targetModerator,
        MessagingRestoredNotification::class,
    );
});

test('administrator cannot restrict member messaging when already restricted', function () {
    Notification::fake();

    $administrator = messagingEnforcementUser([
        'is_admin' => true,
    ]);

    $member = messagingEnforcementUser([
        'messaging_disabled_at' => now()->subDay(),
        'messaging_disabled_until' => now()->addDays(6),
    ]);

    $existingEnforcement = MessagingEnforcement::create([
        'user_id' => $member->id,
        'admin_id' => $administrator->id,
        'message_report_id' => null,
        'action' => 'disabled',
        'reason' => 'Existing temporary messaging restriction.',
        'expires_at' => now()->addDays(6),
    ]);

    $originalDisabledAt = $member->messaging_disabled_at?->toDateTimeString();
    $originalDisabledUntil = $member->messaging_disabled_until?->toDateTimeString();

    $this->actingAs($administrator);

    Livewire::test('admin.members.show', ['user' => $member])
        ->set('restrictionType', '30_days')
        ->set('enforcementReason', 'This duplicate restriction must not be created.')
        ->call('disableMessaging')
        ->assertHasErrors(['restrictionType']);

    $member->refresh();

    expect($member->messaging_disabled_at?->toDateTimeString())
        ->toBe($originalDisabledAt)
        ->and($member->messaging_disabled_until?->toDateTimeString())
        ->toBe($originalDisabledUntil);

    expect(
        MessagingEnforcement::query()
            ->where('user_id', $member->id)
            ->where('action', 'disabled')
            ->count()
    )->toBe(1);

    expect($existingEnforcement->fresh())->not->toBeNull();

    Notification::assertNotSentTo(
        $member,
        MessagingRestrictedNotification::class,
    );
});

test('administrator cannot restore messaging when member is not restricted', function () {
    Notification::fake();

    $administrator = messagingEnforcementUser([
        'is_admin' => true,
    ]);

    $member = messagingEnforcementUser();

    $this->actingAs($administrator);

    Livewire::test('admin.members.show', ['user' => $member])
        ->set('restoreReason', 'This restoration must not exist without an active restriction.')
        ->call('restoreMessaging')
        ->assertHasErrors(['restoreReason']);

    $member->refresh();

    expect($member->messaging_disabled_at)->toBeNull()
        ->and($member->messaging_disabled_until)->toBeNull();

    expect(
        MessagingEnforcement::query()
            ->where('user_id', $member->id)
            ->where('action', 'restored')
            ->exists()
    )->toBeFalse();

    Notification::assertNotSentTo(
        $member,
        MessagingRestoredNotification::class,
    );
});
