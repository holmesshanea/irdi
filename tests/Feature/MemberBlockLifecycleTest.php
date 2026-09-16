
<?php

use App\Models\MemberBlock;
use App\Models\MemberProfile;
use App\Models\Message;
use App\Models\User;
use Livewire\Livewire;

function memberBlockUser(array $attributes = []): User
{
    return User::factory()->create(array_merge([
        'membership_status' => 'active',
        'member_since' => now()->toDateString(),
        'ethics_agreed_at' => now(),
        'best_practices_agreed_at' => now(),
    ], $attributes));
}

function memberBlockProfile(
    User $user,
    array $attributes = []
): MemberProfile {
    return MemberProfile::factory()->for($user)->create(array_merge([
        'directory_visible' => true,
        'allow_member_messages' => true,
    ], $attributes));
}

function memberBlockMessage(
    User $sender,
    User $recipient,
    array $attributes = []
): Message {
    return Message::create(array_merge([
        'sender_id' => $sender->id,
        'recipient_id' => $recipient->id,
        'subject' => 'Member block lifecycle test',
        'body' => 'This existing message is used to test the member block lifecycle.',
    ], $attributes));
}

test('member can block another member from an existing message', function () {
    $sender = memberBlockUser();
    $recipient = memberBlockUser();

    memberBlockProfile($sender);
    memberBlockProfile($recipient);

    $message = memberBlockMessage($sender, $recipient);

    $this->actingAs($recipient);

    Livewire::test('messages.show', ['message' => $message])
        ->call('blockMember')
        ->assertHasNoErrors();

    $this->assertDatabaseHas('member_blocks', [
        'blocker_id' => $recipient->id,
        'blocked_id' => $sender->id,
    ]);
});

test('blocking same member twice does not create duplicate block', function () {
    $sender = memberBlockUser();
    $recipient = memberBlockUser();

    memberBlockProfile($sender);
    memberBlockProfile($recipient);

    $message = memberBlockMessage($sender, $recipient);

    $this->actingAs($recipient);

    $component = Livewire::test('messages.show', ['message' => $message]);

    $component
        ->call('blockMember')
        ->assertHasNoErrors()
        ->call('blockMember')
        ->assertHasNoErrors();

    expect(
        MemberBlock::query()
            ->where('blocker_id', $recipient->id)
            ->where('blocked_id', $sender->id)
            ->count()
    )->toBe(1);
});

test('member can unblock member they previously blocked', function () {
    $sender = memberBlockUser();
    $recipient = memberBlockUser();

    memberBlockProfile($sender);
    memberBlockProfile($recipient);

    $message = memberBlockMessage($sender, $recipient);

    MemberBlock::create([
        'blocker_id' => $recipient->id,
        'blocked_id' => $sender->id,
    ]);

    $this->actingAs($recipient);

    Livewire::test('messages.show', ['message' => $message])
        ->call('unblockMember')
        ->assertHasNoErrors();

    $this->assertDatabaseMissing('member_blocks', [
        'blocker_id' => $recipient->id,
        'blocked_id' => $sender->id,
    ]);
});

test('member cannot unblock another member when no block exists', function () {
    $sender = memberBlockUser();
    $recipient = memberBlockUser();

    memberBlockProfile($sender);
    memberBlockProfile($recipient);

    $message = memberBlockMessage($sender, $recipient);

    $this->actingAs($recipient);

    Livewire::test('messages.show', ['message' => $message])
        ->call('unblockMember')
        ->assertHasErrors(['blockStatus']);

    expect(MemberBlock::query()->count())->toBe(0);
});

test('blocking a member preserves existing messages', function () {
    $sender = memberBlockUser();
    $recipient = memberBlockUser();

    memberBlockProfile($sender);
    memberBlockProfile($recipient);

    $message = memberBlockMessage($sender, $recipient);

    $this->actingAs($recipient);

    Livewire::test('messages.show', ['message' => $message])
        ->call('blockMember')
        ->assertHasNoErrors();

    $this->assertDatabaseHas('messages', [
        'id' => $message->id,
        'sender_id' => $sender->id,
        'recipient_id' => $recipient->id,
        'subject' => 'Member block lifecycle test',
    ]);
});

test('member cannot message someone they have blocked', function () {
    $sender = memberBlockUser();
    $recipient = memberBlockUser();

    memberBlockProfile($sender);
    $recipientProfile = memberBlockProfile($recipient);

    MemberBlock::create([
        'blocker_id' => $sender->id,
        'blocked_id' => $recipient->id,
    ]);

    $this->actingAs($sender);

    Livewire::test('messages.create', ['profile' => $recipientProfile])
        ->assertForbidden();

    expect(
        Message::query()
            ->where('sender_id', $sender->id)
            ->where('recipient_id', $recipient->id)
            ->exists()
    )->toBeFalse();
});

test('blocked member cannot message member who blocked them', function () {
    $sender = memberBlockUser();
    $recipient = memberBlockUser();

    memberBlockProfile($sender);
    $recipientProfile = memberBlockProfile($recipient);

    MemberBlock::create([
        'blocker_id' => $recipient->id,
        'blocked_id' => $sender->id,
    ]);

    $this->actingAs($sender);

    Livewire::test('messages.create', ['profile' => $recipientProfile])
        ->assertForbidden();

    expect(
        Message::query()
            ->where('sender_id', $sender->id)
            ->where('recipient_id', $recipient->id)
            ->exists()
    )->toBeFalse();
});

test('unblocking restores messaging when all other requirements are satisfied', function () {
    $memberA = memberBlockUser();
    $memberB = memberBlockUser();

    memberBlockProfile($memberA);
    $profileB = memberBlockProfile($memberB);

    $existingMessage = memberBlockMessage($memberB, $memberA);

    MemberBlock::create([
        'blocker_id' => $memberA->id,
        'blocked_id' => $memberB->id,
    ]);

    $this->actingAs($memberA);

    Livewire::test('messages.show', ['message' => $existingMessage])
        ->call('unblockMember')
        ->assertHasNoErrors();

    Livewire::test('messages.create', ['profile' => $profileB])
        ->set('subject', 'Messaging restored')
        ->set('body', 'This message should be sent after the member is unblocked.')
        ->call('send')
        ->assertHasNoErrors()
        ->assertSet('sent', true);

    $this->assertDatabaseHas('messages', [
        'sender_id' => $memberA->id,
        'recipient_id' => $memberB->id,
        'subject' => 'Messaging restored',
    ]);
});

test('block created after compose page opens prevents message from being sent', function () {
    $sender = memberBlockUser();
    $recipient = memberBlockUser();

    memberBlockProfile($sender);
    $recipientProfile = memberBlockProfile($recipient);

    $this->actingAs($sender);

    $component = Livewire::test('messages.create', [
        'profile' => $recipientProfile,
    ])
        ->set('subject', 'Race condition test')
        ->set('body', 'This message must not be sent after the recipient blocks the sender.');

    MemberBlock::create([
        'blocker_id' => $recipient->id,
        'blocked_id' => $sender->id,
    ]);

    $component->call('send');

    expect(
        Message::query()
            ->where('sender_id', $sender->id)
            ->where('recipient_id', $recipient->id)
            ->where('subject', 'Race condition test')
            ->exists()
    )->toBeFalse();
});

test('block between two members does not affect unrelated member messaging', function () {
    $memberA = memberBlockUser();
    $memberB = memberBlockUser();
    $memberC = memberBlockUser();

    memberBlockProfile($memberA);
    memberBlockProfile($memberB);
    $profileC = memberBlockProfile($memberC);

    MemberBlock::create([
        'blocker_id' => $memberA->id,
        'blocked_id' => $memberB->id,
    ]);

    $this->actingAs($memberA);

    Livewire::test('messages.create', ['profile' => $profileC])
        ->set('subject', 'Unrelated member')
        ->set('body', 'The block involving another member must not affect this message.')
        ->call('send')
        ->assertHasNoErrors()
        ->assertSet('sent', true);

    $this->assertDatabaseHas('messages', [
        'sender_id' => $memberA->id,
        'recipient_id' => $memberC->id,
        'subject' => 'Unrelated member',
    ]);
});
