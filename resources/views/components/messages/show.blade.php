<?php

use App\Models\MemberBlock;
use App\Models\Message;
use App\Models\MessageReport;
use Livewire\Attributes\Layout;
use Livewire\Component;

new
#[Layout('components.layouts.public')]
class extends Component
{
    public Message $message;

    public bool $showReportForm = false;

    public string $reportReason = '';

    public string $reportDetails = '';

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

    public function getHasReportedMessageProperty(): bool
    {
        return MessageReport::query()
            ->where('message_id', $this->message->id)
            ->where('reporter_id', auth()->id())
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
            ($this->otherProfile?->profile_name ?? $this->otherUser->name)
            . ' has been blocked.'
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
            ($this->otherProfile?->profile_name ?? $this->otherUser->name)
            . ' has been unblocked.'
        );
    }

    public function startReporting(): void
    {
        if ($this->hasReportedMessage) {
            return;
        }

        $this->reportReason = '';
        $this->reportDetails = '';
        $this->showReportForm = true;

        $this->resetValidation([
            'reportReason',
            'reportDetails',
        ]);
    }

    public function cancelReporting(): void
    {
        $this->reportReason = '';
        $this->reportDetails = '';
        $this->showReportForm = false;

        $this->resetValidation([
            'reportReason',
            'reportDetails',
        ]);
    }

    public function reportMessage(): void
    {
        $userId = auth()->id();

        if ($userId === null) {
            abort(403);
        }

        /*
         * Only someone involved in this message can report it.
         */
        if (
            $this->message->sender_id !== $userId
            && $this->message->recipient_id !== $userId
        ) {
            abort(403);
        }

        /*
         * Prevent duplicate reports.
         */
        if ($this->hasReportedMessage) {
            $this->showReportForm = false;

            return;
        }

        $validated = $this->validate([
            'reportReason' => [
                'required',
                'string',
                'in:harassment,spam,inappropriate,threatening,impersonation,other',
            ],
            'reportDetails' => [
                'nullable',
                'string',
                'max:2000',
            ],
        ]);

        MessageReport::create([
            'message_id' => $this->message->id,
            'reporter_id' => $userId,
            'reason' => $validated['reportReason'],
            'details' => $validated['reportDetails'] ?: null,
            'status' => 'pending',
        ]);

        $this->reportReason = '';
        $this->reportDetails = '';
        $this->showReportForm = false;

        session()->flash(
            'status',
            'Thank you. This message has been reported to IRDI for review.'
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

                @if ($showReportForm)

                    <div class="mt-8 rounded-lg border border-amber-200 bg-amber-50 p-5">

                        <h3 class="font-semibold text-zinc-900">
                            Report Message
                        </h3>

                        <p class="mt-1 text-sm text-zinc-600">
                            Tell IRDI why you are reporting this message. Reports are reviewed privately.
                        </p>

                        <form wire:submit="reportMessage" class="mt-5 space-y-5">

                            <flux:select
                                wire:model="reportReason"
                                label="Reason for report"
                                placeholder="Select a reason"
                                required
                            >
                                <flux:select.option value="harassment">
                                    Harassment or abusive behavior
                                </flux:select.option>

                                <flux:select.option value="spam">
                                    Spam or unwanted solicitation
                                </flux:select.option>

                                <flux:select.option value="inappropriate">
                                    Inappropriate content
                                </flux:select.option>

                                <flux:select.option value="threatening">
                                    Threatening behavior
                                </flux:select.option>

                                <flux:select.option value="impersonation">
                                    Impersonation or misleading identity
                                </flux:select.option>

                                <flux:select.option value="other">
                                    Other
                                </flux:select.option>
                            </flux:select>

                            <div>

                                <flux:textarea
                                    wire:model.live="reportDetails"
                                    label="Additional details"
                                    description="Optional. Provide any information that may help IRDI review this report."
                                    rows="5"
                                    maxlength="2000"
                                />

                                <p class="mt-2 text-right text-sm text-zinc-500">
                                    {{ strlen($reportDetails) }} / 2,000 characters
                                </p>

                            </div>

                            <div class="flex flex-wrap items-center gap-3">

                                <flux:button
                                    type="submit"
                                    variant="primary"
                                    wire:loading.attr="disabled"
                                    wire:target="reportMessage"
                                >
                                    <span wire:loading.remove wire:target="reportMessage">
                                        Submit Report
                                    </span>

                                    <span wire:loading wire:target="reportMessage">
                                        Submitting...
                                    </span>
                                </flux:button>

                                <flux:button
                                    type="button"
                                    wire:click="cancelReporting"
                                    variant="ghost"
                                >
                                    Cancel
                                </flux:button>

                            </div>

                        </form>

                    </div>

                @endif

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

                    @if ($this->hasReportedMessage)

                        <flux:button
                            type="button"
                            variant="outline"
                            disabled
                        >
                            Reported
                        </flux:button>

                    @elseif (! $showReportForm)

                        <flux:button
                            type="button"
                            wire:click="startReporting"
                            variant="outline"
                            icon="flag"
                        >
                            Report Message
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
