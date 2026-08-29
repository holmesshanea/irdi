<?php

use App\Models\MemberBlock;
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

        if (
            $message->sender_id !== $userId
            && $message->recipient_id !== $userId
        ) {
            abort(403);
        }

        if (
            $message->sender_id === $userId
            && $message->sender_deleted_at !== null
        ) {
            abort(404);
        }

        if (
            $message->recipient_id === $userId
            && $message->recipient_deleted_at !== null
        ) {
            abort(404);
        }

        $this->message = $message->load([
            'sender.memberProfile',
            'recipient.memberProfile',
        ]);

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

    public function getHasBlockedOtherMemberProperty(): bool
    {
        return MemberBlock::query()
            ->where('blocker_id', auth()->id())
            ->where('blocked_id', $this->otherUser->id)
            ->exists();
    }

    public function getOtherMemberHasBlockedMeProperty(): bool
    {
        return MemberBlock::query()
            ->where('blocker_id', $this->otherUser->id)
            ->where('blocked_id', auth()->id())
            ->exists();
    }

    public function blockMember(): void
    {
        $userId = auth()->id();

        if ($userId === null) {
            abort(403);
        }

        if ($this->otherUser->id === $userId) {
            abort(403);
        }

        MemberBlock::query()->firstOrCreate([
            'blocker_id' => $userId,
            'blocked_id' => $this->otherUser->id,
        ]);

        session()->flash(
            'status',
            $this->otherProfile?->profile_name . ' has been blocked.'
        );
    }

    public function unblockMember(): void
    {
        $userId = auth()->id();

        if ($userId === null) {
            abort(403);
        }

        MemberBlock::query()
            ->where('blocker_id', $userId)
            ->where('blocked_id', $this->otherUser->id)
            ->delete();

        session()->flash(
            'status',
            $this->otherProfile?->profile_name . ' has been unblocked.'
        );
    }

    public function deleteMessage(): void
    {
        $userId = auth()->id();

        if (
            $this->message->sender_id !== $userId
            && $this->message->recipient_id !== $userId
        ) {
            abort(403);
        }

        if ($this->message->sender_id === $userId) {
            $this->message->update([
                'sender_deleted_at' => now(),
            ]);
        }

        if ($this->message->recipient_id === $userId) {
            $this->message->update([
                'recipient_deleted_at' => now(),
            ]);
        }

        session()->flash('status', 'Message deleted.');

        $this->redirectRoute('messages.index');
    }
};
?>

<section class="bg-zinc-50">
    <div class="mx-auto max-w-7xl px-6 py-16 lg:px-8 lg:py-20">

        <div class="mx-auto max-w-3xl">

            @if (session('status'))
                <div class="mb-8 rounded-lg bg-green-50 p-4 text-sm text-green-800">
                    {{ session('status') }}
                </div>
            @endif

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
                        && ! $this->hasBlockedOtherMember
                        && ! $this->otherMemberHasBlockedMe
                    )

                        <flux:button
                            :href="route('messages.create', [
                                'profile' => $this->otherProfile,
                                'reply_to' => $message->id,
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

                    @if ($this->hasBlockedOtherMember)

                        <flux:button
                            type="button"
                            wire:click="unblockMember"
                            wire:confirm="Unblock this member? They may be able to message you again depending on your messaging preferences."
                            variant="outline"
                        >
                            Unblock Member
                        </flux:button>

                    @else

                        <flux:button
                            type="button"
                            wire:click="blockMember"
                            wire:confirm="Block this member? You will no longer be able to exchange private messages with each other."
                            variant="danger"
                        >
                            Block Member
                        </flux:button>

                    @endif

                    <flux:button
                        type="button"
                        wire:click="deleteMessage"
                        wire:confirm="Delete this message? It will be removed from your messages, but the other member will keep their copy."
                        variant="danger"
                        icon="trash"
                    >
                        Delete Message
                    </flux:button>

                </div>

            </flux:card>

        </div>

    </div>
</section>
