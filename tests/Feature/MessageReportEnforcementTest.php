<?php

use App\Models\Message;
use App\Models\MessageReport;
use App\Models\MessageReportModeration;
use App\Models\MessagingEnforcement;
use App\Models\StaffActivityLog;
use App\Models\User;
use App\Notifications\MessagingRestoredNotification;
use App\Notifications\MessagingRestrictedNotification;
use Illuminate\Support\Facades\Notification;
use Livewire\Livewire;

function messageReportEnforcementUser(array $attributes = []): User
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

test('administrator can restrict reported member messaging for seven days', function () {
    Notification::fake();
    $this->freezeTime();

    $administrator = messageReportEnforcementUser([
        'is_admin' => true,
    ]);

    $reportedMember = messageReportEnforcementUser();
    $reporter = messageReportEnforcementUser();

    $message = Message::create([
        'sender_id' => $reportedMember->id,
        'recipient_id' => $reporter->id,
        'subject' => 'Reported message',
        'body' => 'This message is being used to test report enforcement.',
    ]);

    $report = MessageReport::create([
        'message_id' => $message->id,
        'reporter_id' => $reporter->id,
        'reason' => 'harassment',
        'details' => 'The reporter submitted this message for administrative review.',
        'status' => 'pending',
    ]);

    $this->actingAs($administrator);

    Livewire::test('admin.message-reports.show', ['report' => $report])
        ->set('restrictionType', '7_days')
        ->set('enforcementReason', 'Reported messaging conduct requires a temporary messaging restriction.')
        ->call('disableMessaging')
        ->assertHasNoErrors();

    $reportedMember->refresh();

    expect($reportedMember->messaging_disabled_at?->toDateTimeString())
        ->toBe(now()->toDateTimeString())
        ->and($reportedMember->messaging_disabled_until?->toDateTimeString())
        ->toBe(now()->addDays(7)->toDateTimeString());

    $enforcement = MessagingEnforcement::query()
        ->where('user_id', $reportedMember->id)
        ->where('message_report_id', $report->id)
        ->where('action', 'disabled')
        ->sole();

    expect($enforcement->admin_id)
        ->toBe($administrator->id)
        ->and($enforcement->reason)
        ->toBe('Reported messaging conduct requires a temporary messaging restriction.')
        ->and($enforcement->expires_at?->toDateTimeString())
        ->toBe(now()->addDays(7)->toDateTimeString());

    $activity = StaffActivityLog::query()
        ->where('actor_id', $administrator->id)
        ->where('target_user_id', $reportedMember->id)
        ->where('action', 'messaging_disabled_from_report')
        ->sole();

    expect($activity->subject_type)
        ->toBe($enforcement->getMorphClass())
        ->and($activity->subject_id)
        ->toBe($enforcement->id)
        ->and($activity->description)
        ->toBe(
            'Disabled messaging for '.$reportedMember->name.' from message report #'.$report->id.'.'
        )
        ->and($activity->metadata['message_report_id'])
        ->toBe($report->id)
        ->and($activity->metadata['restriction_type'])
        ->toBe('7_days');

    Notification::assertSentTo(
        $reportedMember,
        MessagingRestrictedNotification::class,
        function (MessagingRestrictedNotification $notification): bool {
            return $notification->expiresAt?->toDateTimeString()
                === now()->addDays(7)->toDateTimeString();
        }
    );
});

test('administrator can restore reported member messaging', function () {
    Notification::fake();

    $administrator = messageReportEnforcementUser([
        'is_admin' => true,
    ]);

    $reportedMember = messageReportEnforcementUser([
        'messaging_disabled_at' => now()->subDay(),
        'messaging_disabled_until' => now()->addDays(6),
    ]);

    $reporter = messageReportEnforcementUser();

    $message = Message::create([
        'sender_id' => $reportedMember->id,
        'recipient_id' => $reporter->id,
        'subject' => 'Reported message',
        'body' => 'This message is being used to test report enforcement restoration.',
    ]);

    $report = MessageReport::create([
        'message_id' => $message->id,
        'reporter_id' => $reporter->id,
        'reason' => 'harassment',
        'details' => 'The reporter submitted this message for administrative review.',
        'status' => 'pending',
    ]);

    MessagingEnforcement::create([
        'user_id' => $reportedMember->id,
        'admin_id' => $administrator->id,
        'message_report_id' => $report->id,
        'action' => 'disabled',
        'reason' => 'Reported messaging conduct required a temporary restriction.',
        'expires_at' => now()->addDays(6),
    ]);

    $this->actingAs($administrator);

    Livewire::test('admin.message-reports.show', ['report' => $report])
        ->set('restoreReason', 'Administrative review supports restoring member messaging access.')
        ->call('restoreMessaging')
        ->assertHasNoErrors();

    $reportedMember->refresh();

    expect($reportedMember->messaging_disabled_at)->toBeNull()
        ->and($reportedMember->messaging_disabled_until)->toBeNull();

    $restoration = MessagingEnforcement::query()
        ->where('user_id', $reportedMember->id)
        ->where('message_report_id', $report->id)
        ->where('action', 'restored')
        ->sole();

    expect($restoration->admin_id)
        ->toBe($administrator->id)
        ->and($restoration->reason)
        ->toBe('Administrative review supports restoring member messaging access.')
        ->and($restoration->expires_at)
        ->toBeNull();

    $activity = StaffActivityLog::query()
        ->where('actor_id', $administrator->id)
        ->where('target_user_id', $reportedMember->id)
        ->where('action', 'messaging_restored_from_report')
        ->sole();

    expect($activity->subject_type)
        ->toBe($restoration->getMorphClass())
        ->and($activity->subject_id)
        ->toBe($restoration->id)
        ->and($activity->description)
        ->toBe(
            'Restored messaging for '.$reportedMember->name.' from message report #'.$report->id.'.'
        )
        ->and($activity->metadata['message_report_id'])
        ->toBe($report->id)
        ->and($activity->metadata['reason'])
        ->toBe('Administrative review supports restoring member messaging access.');

    Notification::assertSentTo(
        $reportedMember,
        MessagingRestoredNotification::class,
    );
});

test('administrator cannot restore reported member messaging when member is not restricted', function () {
    Notification::fake();

    $administrator = messageReportEnforcementUser([
        'is_admin' => true,
    ]);

    $reportedMember = messageReportEnforcementUser();
    $reporter = messageReportEnforcementUser();

    $message = Message::create([
        'sender_id' => $reportedMember->id,
        'recipient_id' => $reporter->id,
        'subject' => 'Reported message',
        'body' => 'This message is being used to test invalid report restoration.',
    ]);

    $report = MessageReport::create([
        'message_id' => $message->id,
        'reporter_id' => $reporter->id,
        'reason' => 'harassment',
        'details' => 'The reporter submitted this message for administrative review.',
        'status' => 'pending',
    ]);

    $this->actingAs($administrator);

    Livewire::test('admin.message-reports.show', ['report' => $report])
        ->set('restoreReason', 'This restoration must not exist without an active restriction.')
        ->call('restoreMessaging')
        ->assertHasErrors(['restoreReason']);

    $reportedMember->refresh();

    expect($reportedMember->messaging_disabled_at)->toBeNull()
        ->and($reportedMember->messaging_disabled_until)->toBeNull();

    expect(
        MessagingEnforcement::query()
            ->where('user_id', $reportedMember->id)
            ->where('message_report_id', $report->id)
            ->where('action', 'restored')
            ->exists()
    )->toBeFalse();

    Notification::assertNotSentTo(
        $reportedMember,
        MessagingRestoredNotification::class,
    );
});

test('administrator cannot restrict reported member messaging when already restricted', function () {
    Notification::fake();

    $administrator = messageReportEnforcementUser([
        'is_admin' => true,
    ]);

    $reportedMember = messageReportEnforcementUser([
        'messaging_disabled_at' => now()->subDay(),
        'messaging_disabled_until' => now()->addDays(6),
    ]);

    $reporter = messageReportEnforcementUser();

    $message = Message::create([
        'sender_id' => $reportedMember->id,
        'recipient_id' => $reporter->id,
        'subject' => 'Reported message',
        'body' => 'This message is being used to test duplicate report enforcement.',
    ]);

    $report = MessageReport::create([
        'message_id' => $message->id,
        'reporter_id' => $reporter->id,
        'reason' => 'harassment',
        'details' => 'The reporter submitted this message for administrative review.',
        'status' => 'pending',
    ]);

    $existingEnforcement = MessagingEnforcement::create([
        'user_id' => $reportedMember->id,
        'admin_id' => $administrator->id,
        'message_report_id' => null,
        'action' => 'disabled',
        'reason' => 'Existing temporary messaging restriction.',
        'expires_at' => now()->addDays(6),
    ]);

    $originalDisabledAt = $reportedMember->messaging_disabled_at?->toDateTimeString();
    $originalDisabledUntil = $reportedMember->messaging_disabled_until?->toDateTimeString();

    $this->actingAs($administrator);

    Livewire::test('admin.message-reports.show', ['report' => $report])
        ->set('restrictionType', '30_days')
        ->set('enforcementReason', 'This duplicate report restriction must not be created.')
        ->call('disableMessaging')
        ->assertHasErrors(['restrictionType']);

    $reportedMember->refresh();

    expect($reportedMember->messaging_disabled_at?->toDateTimeString())
        ->toBe($originalDisabledAt)
        ->and($reportedMember->messaging_disabled_until?->toDateTimeString())
        ->toBe($originalDisabledUntil);

    expect(
        MessagingEnforcement::query()
            ->where('user_id', $reportedMember->id)
            ->where('message_report_id', $report->id)
            ->where('action', 'disabled')
            ->exists()
    )->toBeFalse();

    expect($existingEnforcement->fresh())->not->toBeNull();

    Notification::assertNotSentTo(
        $reportedMember,
        MessagingRestrictedNotification::class,
    );
});

test('administrator cannot restrict another administrator through message report', function () {
    Notification::fake();

    $administrator = messageReportEnforcementUser([
        'is_admin' => true,
    ]);

    $reportedAdministrator = messageReportEnforcementUser([
        'is_admin' => true,
    ]);

    $reporter = messageReportEnforcementUser();

    $message = Message::create([
        'sender_id' => $reportedAdministrator->id,
        'recipient_id' => $reporter->id,
        'subject' => 'Reported message',
        'body' => 'This message is being used to test report enforcement authorization.',
    ]);

    $report = MessageReport::create([
        'message_id' => $message->id,
        'reporter_id' => $reporter->id,
        'reason' => 'harassment',
        'details' => 'The reporter submitted this message for administrative review.',
        'status' => 'pending',
    ]);

    $this->actingAs($administrator);

    Livewire::test('admin.message-reports.show', ['report' => $report])
        ->set('restrictionType', '7_days')
        ->set(
            'enforcementReason',
            'This enforcement must not be permitted against equal-rank staff.'
        )
        ->call('disableMessaging')
        ->assertForbidden();

    $reportedAdministrator->refresh();

    expect($reportedAdministrator->messaging_disabled_at)->toBeNull()
        ->and($reportedAdministrator->messaging_disabled_until)->toBeNull();

    expect(
        MessagingEnforcement::query()
            ->where('user_id', $reportedAdministrator->id)
            ->where('message_report_id', $report->id)
            ->exists()
    )->toBeFalse();

    Notification::assertNotSentTo(
        $reportedAdministrator,
        MessagingRestrictedNotification::class,
    );
});

test('administrator cannot restore another administrator through message report', function () {
    Notification::fake();

    $administrator = messageReportEnforcementUser([
        'is_admin' => true,
    ]);

    $reportedAdministrator = messageReportEnforcementUser([
        'is_admin' => true,
        'messaging_disabled_at' => now()->subDay(),
        'messaging_disabled_until' => now()->addDays(6),
    ]);

    $reporter = messageReportEnforcementUser();

    $message = Message::create([
        'sender_id' => $reportedAdministrator->id,
        'recipient_id' => $reporter->id,
        'subject' => 'Reported message',
        'body' => 'This message is being used to test report restoration authorization.',
    ]);

    $report = MessageReport::create([
        'message_id' => $message->id,
        'reporter_id' => $reporter->id,
        'reason' => 'harassment',
        'details' => 'The reporter submitted this message for administrative review.',
        'status' => 'pending',
    ]);

    MessagingEnforcement::create([
        'user_id' => $reportedAdministrator->id,
        'admin_id' => null,
        'message_report_id' => $report->id,
        'action' => 'disabled',
        'reason' => 'Existing messaging restriction.',
        'expires_at' => now()->addDays(6),
    ]);

    $originalDisabledAt = $reportedAdministrator->messaging_disabled_at?->toDateTimeString();
    $originalDisabledUntil = $reportedAdministrator->messaging_disabled_until?->toDateTimeString();

    $this->actingAs($administrator);

    Livewire::test('admin.message-reports.show', ['report' => $report])
        ->set(
            'restoreReason',
            'This restoration must not be permitted against equal-rank staff.'
        )
        ->call('restoreMessaging')
        ->assertForbidden();

    $reportedAdministrator->refresh();

    expect($reportedAdministrator->messaging_disabled_at?->toDateTimeString())
        ->toBe($originalDisabledAt)
        ->and($reportedAdministrator->messaging_disabled_until?->toDateTimeString())
        ->toBe($originalDisabledUntil);

    expect(
        MessagingEnforcement::query()
            ->where('user_id', $reportedAdministrator->id)
            ->where('message_report_id', $report->id)
            ->where('action', 'restored')
            ->exists()
    )->toBeFalse();

    Notification::assertNotSentTo(
        $reportedAdministrator,
        MessagingRestoredNotification::class,
    );
});

test('administrator can mark pending message report as reviewed', function () {
    $administrator = messageReportEnforcementUser([
        'is_admin' => true,
    ]);

    $reportedMember = messageReportEnforcementUser();
    $reporter = messageReportEnforcementUser();

    $message = Message::create([
        'sender_id' => $reportedMember->id,
        'recipient_id' => $reporter->id,
        'subject' => 'Reported message',
        'body' => 'This message is being used to test report review resolution.',
    ]);

    $report = MessageReport::create([
        'message_id' => $message->id,
        'reporter_id' => $reporter->id,
        'reason' => 'harassment',
        'details' => 'The reporter submitted this message for administrative review.',
        'status' => 'pending',
    ]);

    $this->actingAs($administrator);

    Livewire::test('admin.message-reports.show', ['report' => $report])
        ->call('markReviewed')
        ->assertHasNoErrors();

    expect($report->fresh()->status)->toBe('reviewed');

    $moderation = MessageReportModeration::query()
        ->where('message_report_id', $report->id)
        ->where('user_id', $administrator->id)
        ->where('action', 'reviewed')
        ->sole();

    $activity = StaffActivityLog::query()
        ->where('actor_id', $administrator->id)
        ->where('target_user_id', $reportedMember->id)
        ->where('action', 'message_report_reviewed')
        ->sole();

    expect($activity->subject_type)
        ->toBe($moderation->getMorphClass())
        ->and($activity->subject_id)
        ->toBe($moderation->id)
        ->and($activity->description)
        ->toBe('Marked message report #'.$report->id.' as reviewed.')
        ->and($activity->metadata['message_report_id'])
        ->toBe($report->id)
        ->and($activity->metadata['reporter_id'])
        ->toBe($reporter->id);
});

test('administrator can dismiss pending message report', function () {
    $administrator = messageReportEnforcementUser([
        'is_admin' => true,
    ]);

    $reportedMember = messageReportEnforcementUser();
    $reporter = messageReportEnforcementUser();

    $message = Message::create([
        'sender_id' => $reportedMember->id,
        'recipient_id' => $reporter->id,
        'subject' => 'Reported message',
        'body' => 'This message is being used to test report dismissal resolution.',
    ]);

    $report = MessageReport::create([
        'message_id' => $message->id,
        'reporter_id' => $reporter->id,
        'reason' => 'harassment',
        'details' => 'The reporter submitted this message for administrative review.',
        'status' => 'pending',
    ]);

    $this->actingAs($administrator);

    Livewire::test('admin.message-reports.show', ['report' => $report])
        ->call('dismissReport')
        ->assertHasNoErrors();

    expect($report->fresh()->status)->toBe('dismissed');

    $moderation = MessageReportModeration::query()
        ->where('message_report_id', $report->id)
        ->where('user_id', $administrator->id)
        ->where('action', 'dismissed')
        ->sole();

    $activity = StaffActivityLog::query()
        ->where('actor_id', $administrator->id)
        ->where('target_user_id', $reportedMember->id)
        ->where('action', 'message_report_dismissed')
        ->sole();

    expect($activity->subject_type)
        ->toBe($moderation->getMorphClass())
        ->and($activity->subject_id)
        ->toBe($moderation->id)
        ->and($activity->description)
        ->toBe('Dismissed message report #'.$report->id.'.')
        ->and($activity->metadata['message_report_id'])
        ->toBe($report->id)
        ->and($activity->metadata['reporter_id'])
        ->toBe($reporter->id);
});

test('resolved message report cannot be changed to another resolution', function () {
    $administrator = messageReportEnforcementUser([
        'is_admin' => true,
    ]);

    $reportedMember = messageReportEnforcementUser();
    $reporter = messageReportEnforcementUser();

    $message = Message::create([
        'sender_id' => $reportedMember->id,
        'recipient_id' => $reporter->id,
        'subject' => 'Reported message',
        'body' => 'This message is being used to test terminal report resolution.',
    ]);

    $report = MessageReport::create([
        'message_id' => $message->id,
        'reporter_id' => $reporter->id,
        'reason' => 'harassment',
        'details' => 'The reporter submitted this message for administrative review.',
        'status' => 'pending',
    ]);

    $this->actingAs($administrator);

    Livewire::test('admin.message-reports.show', ['report' => $report])
        ->call('markReviewed')
        ->assertHasNoErrors()
        ->call('dismissReport')
        ->assertHasErrors(['reportStatus']);

    expect($report->fresh()->status)->toBe('reviewed');

    expect(
        MessageReportModeration::query()
            ->where('message_report_id', $report->id)
            ->where('action', 'reviewed')
            ->count()
    )->toBe(1);

    expect(
        MessageReportModeration::query()
            ->where('message_report_id', $report->id)
            ->where('action', 'dismissed')
            ->exists()
    )->toBeFalse();

    expect(
        StaffActivityLog::query()
            ->where('target_user_id', $reportedMember->id)
            ->where('action', 'message_report_dismissed')
            ->exists()
    )->toBeFalse();
});

test('dismissed message report cannot later be marked as reviewed', function () {
    $administrator = messageReportEnforcementUser([
        'is_admin' => true,
    ]);

    $reportedMember = messageReportEnforcementUser();
    $reporter = messageReportEnforcementUser();

    $message = Message::create([
        'sender_id' => $reportedMember->id,
        'recipient_id' => $reporter->id,
        'subject' => 'Reported message',
        'body' => 'This message is being used to test terminal dismissed report resolution.',
    ]);

    $report = MessageReport::create([
        'message_id' => $message->id,
        'reporter_id' => $reporter->id,
        'reason' => 'harassment',
        'details' => 'The reporter submitted this message for administrative review.',
        'status' => 'pending',
    ]);

    $this->actingAs($administrator);

    Livewire::test('admin.message-reports.show', ['report' => $report])
        ->call('dismissReport')
        ->assertHasNoErrors()
        ->call('markReviewed')
        ->assertHasErrors(['reportStatus']);

    expect($report->fresh()->status)->toBe('dismissed');

    expect(
        MessageReportModeration::query()
            ->where('message_report_id', $report->id)
            ->where('action', 'dismissed')
            ->count()
    )->toBe(1);

    expect(
        MessageReportModeration::query()
            ->where('message_report_id', $report->id)
            ->where('action', 'reviewed')
            ->exists()
    )->toBeFalse();

    expect(
        StaffActivityLog::query()
            ->where('target_user_id', $reportedMember->id)
            ->where('action', 'message_report_reviewed')
            ->exists()
    )->toBeFalse();
});
