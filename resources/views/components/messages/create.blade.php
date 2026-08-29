<?php

use App\Models\MemberBlock;
use App\Models\MemberProfile;
use App\Models\Message;
use App\Notifications\NewMemberMessageNotification;
use Illuminate\Support\Facades\RateLimiter;
use Livewire\Attributes\Layout;
use Livewire\Component;

new
#[Layout('components.layouts.public')]
class extends Component
{
    public MemberProfile $profile;

    public string $subject = '';

    public string $body = '';

    public bool $sent = false;

    public ?int $replyToMessageId = null;

    public function mount(MemberProfile $profile): void
    {
        $this->profile = $profile->load('user');

        $replyTo = request()->query('reply_to');

        if (is_numeric($replyTo)) {
            $this->replyToMessageId = (int) $replyTo;
        }

        $this->ensureCanMessage();

        if ($this->replyToMessageId !== null) {
            $originalMessage = $this->replyToMessage();

            if ($originalMessage !== null) {
                $this->subject = str_starts_with($originalMessage->subject, 'Re:')
                    ? $originalMessage->subject
                    : 'Re: ' . $originalMessage->subject;
            }
        }
    }

    private function replyToMessage(): ?Message
    {
        if ($this->replyToMessageId === null) {
            return null;
        }

        return Message::query()
            ->whereKey($this->replyToMessageId)
            ->where('recipient_id', auth()->id())
            ->where('sender_id', $this->profile->user_id)
            ->first();
    }

    private function isValidReply(): bool
    {
        return $this->replyToMessage() !== null;
    }

    private function ensureCanMessage(): void
    {
        $sender = auth()->user();

        if ($sender === null) {
            abort(403);
        }

        /*
         * Only active IRDI members may send messages.
         */
        if ($sender->membership_status !== 'active') {
            abort(403);
        }

        /*
         * Members cannot message themselves.
         */
        if ($sender->id === $this->profile->user_id) {
            abort(403);
        }

        /*
         * The recipient must still have an active IRDI membership.
         */
        if ($this->profile->user->membership_status !== 'active') {
            abort(404);
        }

        /*
         * The recipient may block this sender.
         *
         * Blocking overrides normal messaging and reply permissions.
         */
        $recipientBlockedSender = MemberBlock::query()
            ->where('blocker_id', $this->profile->user_id)
            ->where('blocked_id', $sender->id)
            ->exists();

        if ($recipientBlockedSender) {
            abort(403);
        }

        /*
         * If the sender has blocked the recipient, they may not
         * continue messaging that member either.
         */
        $senderBlockedRecipient = MemberBlock::query()
            ->where('blocker_id', $sender->id)
            ->where('blocked_id', $this->profile->user_id)
            ->exists();

        if ($senderBlockedRecipient) {
            abort(403);
        }

        /*
         * A valid reply is allowed because this member previously
         * initiated the conversation with the logged-in user.
         */
        if ($this->isValidReply()) {
            return;
        }

        /*
         * Normal first-contact messages still require the recipient
         * to have messaging enabled and a visible directory profile.
         */
        if (! $this->profile->allow_member_messages) {
            abort(404);
        }

        if (! $this->profile->directory_visible) {
            abort(404);
        }
    }

    public function send(): void
    {
        /*
         * Re-check the recipient and permissions immediately before
         * saving the message.
         */
        $this->profile->refresh();
        $this->profile->load('user');

        $this->ensureCanMessage();

        $validated = $this->validate([
            'subject' => [
                'required',
                'string',
                'max:150',
            ],

            'body' => [
                'required',
                'string',
                'max:5000',
            ],
        ]);

        $sender = auth()->user();

        if ($sender === null) {
            abort(403);
        }

        $rateLimitKey = 'send-member-message:' . $sender->id;

        $allowed = RateLimiter::attempt(
            $rateLimitKey,
            5,
            function () use ($sender, $validated): void {
                $message = Message::create([
                    'sender_id' => $sender->id,
                    'recipient_id' => $this->profile->user_id,
                    'subject' => $validated['subject'],
                    'body' => $validated['body'],
                ]);

                $message->load([
                    'sender.memberProfile',
                    'recipient',
                ]);

                if ($message->recipient->email_member_message_notifications) {
                    $message->recipient->notify(
                        new NewMemberMessageNotification($message)
                    );
                }
            },
            60
        );

        if (! $allowed) {
            $seconds = RateLimiter::availableIn($rateLimitKey);

            $this->addError(
                'body',
                "You're sending messages too quickly. Please try again in {$seconds} seconds."
            );

            return;
        }

        $this->reset('subject', 'body');

        $this->replyToMessageId = null;

        $this->sent = true;
    }
};
?>

<section class="bg-zinc-50">
    <div class="mx-auto max-w-7xl px-6 py-16 lg:px-8 lg:py-20">

        <div class="mx-auto max-w-2xl">

            <div class="text-center">

                <h1 class="text-3xl font-bold tracking-tight text-irdi-green sm:text-4xl">
                    Message Member
                </h1>

                <div class="mx-auto mt-4 h-1 w-20 bg-irdi-gold"></div>

                <p class="mt-6 text-lg text-zinc-600">
                    Send a private message to another IRDI member.
                </p>

            </div>

            @if ($sent)

                <div class="mt-10 rounded-lg border border-green-200 bg-green-50 p-5">

                    <div class="flex items-start gap-3">

                        <flux:icon.check-circle class="mt-0.5 size-5 text-green-700" />

                        <div>

                            <h2 class="font-semibold text-green-800">
                                Message sent
                            </h2>

                            <p class="mt-1 text-sm text-green-700">
                                Your message was sent to {{ $profile->profile_name }}.
                            </p>

                        </div>

                    </div>

                </div>

            @endif

            <flux:card class="mt-8 p-6">

                <div class="border-b border-zinc-200 pb-6">

                    <p class="text-sm text-zinc-500">
                        Sending message to
                    </p>

                    <div class="mt-2">

                        <h2 class="text-lg font-semibold text-irdi-green">
                            {{ $profile->profile_name }}
                        </h2>

                        <p class="text-sm text-zinc-500">
                            {{ '@' . $profile->username }}
                        </p>

                    </div>

                </div>

                <form wire:submit="send" class="mt-6 space-y-6">

                    <flux:input
                        wire:model="subject"
                        label="Subject"
                        maxlength="150"
                        required
                    />

                    <div>

                        <flux:textarea
                            wire:model.live="body"
                            label="Message"
                            rows="8"
                            maxlength="5000"
                            required
                        />

                        <p class="mt-2 text-right text-sm text-zinc-500">
                            {{ strlen($body) }} / 5,000 characters
                        </p>

                    </div>

                    <div class="rounded-lg bg-zinc-50 p-4 text-sm text-zinc-600">
                        This message will be delivered through IRDI. Your email address will not be shared with the recipient.
                    </div>

                    <div class="flex flex-wrap items-center gap-3">

                        <flux:button
                            type="submit"
                            variant="primary"
                            icon="paper-airplane"
                            wire:loading.attr="disabled"
                            wire:target="send"
                        >
                            <span wire:loading.remove wire:target="send">
                                Send Message
                            </span>

                            <span wire:loading wire:target="send">
                                Sending...
                            </span>
                        </flux:button>

                        <flux:button
                            :href="route('member-profiles.show', $profile)"
                            variant="ghost"
                        >
                            Cancel
                        </flux:button>

                    </div>

                </form>

            </flux:card>

        </div>

    </div>
</section>
