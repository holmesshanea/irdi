<?php

use App\Models\Message;
use Livewire\Attributes\Layout;
use Livewire\Component;

new
#[Layout('components.layouts.public')]
class extends Component
{
    public Message $message;

    public function mount(Message $message): void
    {
        $userId = auth()->id();

        /*
         * Only the sender or recipient may view this message.
         */
        if (
            $message->sender_id !== $userId
            && $message->recipient_id !== $userId
        ) {
            abort(403);
        }

        $this->message = $message->load([
            'sender.memberProfile',
            'recipient.memberProfile',
        ]);

        /*
         * Mark the message as read only when the recipient opens it.
         */
        if (
            $message->recipient_id === $userId
            && $message->read_at === null
        ) {
            $message->update([
                'read_at' => now(),
            ]);

            $this->message->refresh();
        }
    }

    public function getOtherProfileProperty(): ?\App\Models\MemberProfile
    {
        if ($this->message->sender_id === auth()->id()) {
            return $this->message->recipient->memberProfile;
        }

        return $this->message->sender->memberProfile;
    }

    public function getOtherUserProperty(): \App\Models\User
    {
        if ($this->message->sender_id === auth()->id()) {
            return $this->message->recipient;
        }

        return $this->message->sender;
    }

    public function getIsReceivedProperty(): bool
    {
        return $this->message->recipient_id === auth()->id();
    }
};
?>

<section class="bg-zinc-50">
    <div class="mx-auto max-w-7xl px-6 py-16 lg:px-8 lg:py-20">

        <div class="mx-auto max-w-3xl">

            <div class="text-center">

                <h1 class="text-3xl font-bold tracking-tight text-irdi-green sm:text-4xl">
                    Message
                </h1>

                <div class="mx-auto mt-4 h-1 w-20 bg-irdi-gold"></div>

                <p class="mt-6 text-lg text-zinc-600">
                    Your private IRDI message.
                </p>

            </div>

            <flux:card class="mt-10 p-6">

                <div class="border-b border-zinc-200 pb-6">

                    <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">

                        <div>

                            <p class="text-sm text-zinc-500">
                                @if ($this->isReceived)
                                    From
                                @else
                                    To
                                @endif
                            </p>

                            <p class="mt-1 font-semibold text-irdi-green">
                                {{ $this->otherProfile?->profile_name ?? $this->otherUser->name }}
                            </p>

                            @if ($this->otherProfile)

                                <p class="mt-1 text-sm text-zinc-500">
                                    {{ '@' . $this->otherProfile->username }}
                                </p>

                            @endif

                        </div>

                        <div class="text-left sm:text-right">

                            <p class="text-sm text-zinc-500">
                                {{ $message->created_at->format('F j, Y') }}
                            </p>

                            <p class="mt-1 text-sm text-zinc-500">
                                {{ $message->created_at->format('g:i A') }}
                            </p>

                        </div>

                    </div>

                </div>

                <div class="py-6">

                    <p class="text-sm font-medium text-zinc-500">
                        Subject
                    </p>

                    <h2 class="mt-2 text-xl font-semibold text-zinc-900">
                        {{ $message->subject }}
                    </h2>

                </div>

                <div class="border-t border-zinc-200 pt-6">

                    <div class="whitespace-pre-wrap leading-7 text-zinc-700">{{ $message->body }}</div>

                </div>

                <div class="mt-8 flex flex-wrap items-center gap-3 border-t border-zinc-200 pt-6">

                    @if (
                        $this->isReceived
                        && $this->otherProfile
                        && $this->otherUser->membership_status === 'active'
                        && $this->otherProfile->allow_member_messages
                        && $this->otherProfile->directory_visible
                    )

                        <flux:button
                            :href="route('messages.create', [
        'profile' => $this->otherProfile,
        'subject' => str_starts_with($message->subject, 'Re:')
            ? $message->subject
            : 'Re: ' . $message->subject,
    ])"
                            variant="primary"
                            icon="arrow-uturn-left"
                        >
                            Reply
                        </flux:button>

                    @endif

                    <flux:button
                        :href="route('messages.index')"
                        variant="outline"
                        icon="arrow-left"
                    >
                        Back to Messages
                    </flux:button>

                    @if ($this->otherProfile)

                        <flux:button
                            :href="route('member-profiles.show', $this->otherProfile)"
                            variant="ghost"
                        >
                            View Member Profile
                        </flux:button>

                    @endif

                </div>

            </flux:card>

        </div>

    </div>
</section>
