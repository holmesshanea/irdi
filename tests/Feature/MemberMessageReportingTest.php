
<?php

use App\Models\MemberProfile;
use App\Models\Message;
use App\Models\MessageReport;
use App\Models\User;
use Livewire\Livewire;

function memberMessageReportingUser(array $attributes = []): User
{
    return User::factory()->create(array_merge([
        'membership_status' => 'active',
        'member_since' => now()->toDateString(),
        'ethics_agreed_at' => now(),
        'best_practices_agreed_at' => now(),
    ], $attributes));
}

function memberMessageReportingProfile(User $user): MemberProfile
{
    return MemberProfile::factory()->for($user)->create([
        'directory_visible' => true,
        'allow_member_messages' => true,
    ]);
}

function memberMessageReportingMessage(
    User $sender,
    User $recipient,
    array $attributes = []
): Message {
    return Message::create(array_merge([
        'sender_id' => $sender->id,
        'recipient_id' => $recipient->id,
        'subject' => 'Message reporting lifecycle test',
        'body' => 'This message is used to test member message reporting.',
    ], $attributes));
}

test('recipient can report received message', function () {
    $sender = memberMessageReportingUser();
    $recipient = memberMessageReportingUser();

    memberMessageReportingProfile($sender);
    memberMessageReportingProfile($recipient);

    $message = memberMessageReportingMessage($sender, $recipient);

    $this->actingAs($recipient);

    Livewire::test('messages.show', ['message' => $message])
        ->set('reportReason', 'harassment')
        ->set('reportDetails', 'This message contains conduct that should be reviewed by IRDI.')
        ->call('reportMessage')
        ->assertHasNoErrors();

    $report = MessageReport::query()
        ->where('message_id', $message->id)
        ->where('reporter_id', $recipient->id)
        ->sole();

    expect($report->reason)
        ->toBe('harassment')
        ->and($report->details)
        ->toBe('This message contains conduct that should be reviewed by IRDI.')
        ->and($report->status)
        ->toBe('pending');
});

test('sender can report message they participated in', function () {
    $sender = memberMessageReportingUser();
    $recipient = memberMessageReportingUser();

    memberMessageReportingProfile($sender);
    memberMessageReportingProfile($recipient);

    $message = memberMessageReportingMessage($sender, $recipient);

    $this->actingAs($sender);

    Livewire::test('messages.show', ['message' => $message])
        ->set('reportReason', 'inappropriate')
        ->set('reportDetails', 'The sender is reporting this conversation for staff review.')
        ->call('reportMessage')
        ->assertHasNoErrors();

    $this->assertDatabaseHas('message_reports', [
        'message_id' => $message->id,
        'reporter_id' => $sender->id,
        'reason' => 'inappropriate',
        'status' => 'pending',
    ]);
});

test('outsider cannot access or report another members message', function () {
    $sender = memberMessageReportingUser();
    $recipient = memberMessageReportingUser();
    $outsider = memberMessageReportingUser();

    memberMessageReportingProfile($sender);
    memberMessageReportingProfile($recipient);
    memberMessageReportingProfile($outsider);

    $message = memberMessageReportingMessage($sender, $recipient);

    $this->actingAs($outsider);

    Livewire::test('messages.show', ['message' => $message])
        ->assertForbidden();

    expect(
        MessageReport::query()
            ->where('message_id', $message->id)
            ->exists()
    )->toBeFalse();
});

test('same member cannot report same message twice', function () {
    $sender = memberMessageReportingUser();
    $recipient = memberMessageReportingUser();

    memberMessageReportingProfile($sender);
    memberMessageReportingProfile($recipient);

    $message = memberMessageReportingMessage($sender, $recipient);

    $this->actingAs($recipient);

    $component = Livewire::test('messages.show', ['message' => $message]);

    $component
        ->set('reportReason', 'spam')
        ->set('reportDetails', 'First report submitted for this message.')
        ->call('reportMessage')
        ->assertHasNoErrors();

    $component
        ->set('reportReason', 'harassment')
        ->set('reportDetails', 'A duplicate report must not replace the original report.')
        ->call('reportMessage')
        ->assertHasNoErrors();

    expect(
        MessageReport::query()
            ->where('message_id', $message->id)
            ->where('reporter_id', $recipient->id)
            ->count()
    )->toBe(1);

    $report = MessageReport::query()
        ->where('message_id', $message->id)
        ->where('reporter_id', $recipient->id)
        ->sole();

    expect($report->reason)
        ->toBe('spam')
        ->and($report->details)
        ->toBe('First report submitted for this message.');
});

test('both participants can independently report same message', function () {
    $sender = memberMessageReportingUser();
    $recipient = memberMessageReportingUser();

    memberMessageReportingProfile($sender);
    memberMessageReportingProfile($recipient);

    $message = memberMessageReportingMessage($sender, $recipient);

    $this->actingAs($recipient);

    Livewire::test('messages.show', ['message' => $message])
        ->set('reportReason', 'harassment')
        ->set('reportDetails', 'Recipient report for administrative review.')
        ->call('reportMessage')
        ->assertHasNoErrors();

    $this->actingAs($sender);

    Livewire::test('messages.show', ['message' => $message])
        ->set('reportReason', 'other')
        ->set('reportDetails', 'Sender independently reports the same message.')
        ->call('reportMessage')
        ->assertHasNoErrors();

    expect(
        MessageReport::query()
            ->where('message_id', $message->id)
            ->count()
    )->toBe(2);

    $this->assertDatabaseHas('message_reports', [
        'message_id' => $message->id,
        'reporter_id' => $recipient->id,
    ]);

    $this->assertDatabaseHas('message_reports', [
        'message_id' => $message->id,
        'reporter_id' => $sender->id,
    ]);
});

test('message report reason is required', function () {
    $sender = memberMessageReportingUser();
    $recipient = memberMessageReportingUser();

    memberMessageReportingProfile($sender);
    memberMessageReportingProfile($recipient);

    $message = memberMessageReportingMessage($sender, $recipient);

    $this->actingAs($recipient);

    Livewire::test('messages.show', ['message' => $message])
        ->set('reportReason', '')
        ->set('reportDetails', 'Details without a reason must not create a report.')
        ->call('reportMessage')
        ->assertHasErrors(['reportReason']);

    expect(
        MessageReport::query()
            ->where('message_id', $message->id)
            ->exists()
    )->toBeFalse();
});

test('message report rejects invalid reason', function () {
    $sender = memberMessageReportingUser();
    $recipient = memberMessageReportingUser();

    memberMessageReportingProfile($sender);
    memberMessageReportingProfile($recipient);

    $message = memberMessageReportingMessage($sender, $recipient);

    $this->actingAs($recipient);

    Livewire::test('messages.show', ['message' => $message])
        ->set('reportReason', 'not-a-valid-reason')
        ->call('reportMessage')
        ->assertHasErrors(['reportReason']);

    expect(
        MessageReport::query()
            ->where('message_id', $message->id)
            ->exists()
    )->toBeFalse();
});

test('all supported message report reasons are accepted', function (string $reason) {
    $sender = memberMessageReportingUser();
    $recipient = memberMessageReportingUser();

    memberMessageReportingProfile($sender);
    memberMessageReportingProfile($recipient);

    $message = memberMessageReportingMessage($sender, $recipient);

    $this->actingAs($recipient);

    Livewire::test('messages.show', ['message' => $message])
        ->set('reportReason', $reason)
        ->call('reportMessage')
        ->assertHasNoErrors();

    $this->assertDatabaseHas('message_reports', [
        'message_id' => $message->id,
        'reporter_id' => $recipient->id,
        'reason' => $reason,
        'status' => 'pending',
    ]);
})->with([
    'harassment',
    'spam',
    'inappropriate',
    'threatening',
    'impersonation',
    'other',
]);

test('message report details are optional and stored as null when omitted', function () {
    $sender = memberMessageReportingUser();
    $recipient = memberMessageReportingUser();

    memberMessageReportingProfile($sender);
    memberMessageReportingProfile($recipient);

    $message = memberMessageReportingMessage($sender, $recipient);

    $this->actingAs($recipient);

    Livewire::test('messages.show', ['message' => $message])
        ->set('reportReason', 'spam')
        ->set('reportDetails', '')
        ->call('reportMessage')
        ->assertHasNoErrors();

    $report = MessageReport::query()
        ->where('message_id', $message->id)
        ->where('reporter_id', $recipient->id)
        ->sole();

    expect($report->details)->toBeNull();
});

test('message report details cannot exceed two thousand characters', function () {
    $sender = memberMessageReportingUser();
    $recipient = memberMessageReportingUser();

    memberMessageReportingProfile($sender);
    memberMessageReportingProfile($recipient);

    $message = memberMessageReportingMessage($sender, $recipient);

    $this->actingAs($recipient);

    Livewire::test('messages.show', ['message' => $message])
        ->set('reportReason', 'other')
        ->set('reportDetails', str_repeat('a', 2001))
        ->call('reportMessage')
        ->assertHasErrors(['reportDetails']);

    expect(
        MessageReport::query()
            ->where('message_id', $message->id)
            ->exists()
    )->toBeFalse();
});

test('reporting message preserves original message and deletion state', function () {
    $sender = memberMessageReportingUser();
    $recipient = memberMessageReportingUser();

    memberMessageReportingProfile($sender);
    memberMessageReportingProfile($recipient);

    $message = memberMessageReportingMessage($sender, $recipient);

    $this->actingAs($recipient);

    Livewire::test('messages.show', ['message' => $message])
        ->set('reportReason', 'threatening')
        ->set('reportDetails', 'This message should remain available after it is reported.')
        ->call('reportMessage')
        ->assertHasNoErrors();

    $message->refresh();

    expect($message->subject)
        ->toBe('Message reporting lifecycle test')
        ->and($message->body)
        ->toBe('This message is used to test member message reporting.')
        ->and($message->sender_deleted_at)
        ->toBeNull()
        ->and($message->recipient_deleted_at)
        ->toBeNull();

    $this->assertDatabaseHas('messages', [
        'id' => $message->id,
        'sender_id' => $sender->id,
        'recipient_id' => $recipient->id,
    ]);
});
