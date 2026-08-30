<?php

use App\Models\Message;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithPagination;

new
#[Layout('components.layouts.public')]
class extends Component
{
    use WithPagination;

    public string $tab = 'inbox';

    /**
     * @var array<int, int|string>
     */
    public array $selectedMessages = [];

    public bool $selectAll = false;

    public function showInbox(): void
    {
        $this->tab = 'inbox';

        $this->clearSelection();

        $this->resetPage();
    }

    public function showSent(): void
    {
        $this->tab = 'sent';

        $this->clearSelection();

        $this->resetPage();
    }

    public function updatedSelectAll(bool $value): void
    {
        if (! $value) {
            $this->selectedMessages = [];

            return;
        }

        $this->selectedMessages = $this->visibleMessageIds();
    }

    public function updatedSelectedMessages(): void
    {
        $visibleIds = $this->visibleMessageIds();

        $selectedIds = collect($this->selectedMessages)
            ->map(fn ($id) => (int) $id)
            ->intersect($visibleIds)
            ->values()
            ->all();

        $this->selectAll = count($visibleIds) > 0
            && count($selectedIds) === count($visibleIds);
    }

    public function deleteSelected(): void
    {
        $userId = auth()->id();

        $selectedIds = collect($this->selectedMessages)
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values()
            ->all();

        if ($selectedIds === []) {
            return;
        }

        if ($this->tab === 'sent') {
            Message::query()
                ->where('sender_id', $userId)
                ->whereNull('sender_deleted_at')
                ->whereIn('id', $selectedIds)
                ->update([
                    'sender_deleted_at' => now(),
                ]);
        } else {
            Message::query()
                ->where('recipient_id', $userId)
                ->whereNull('recipient_deleted_at')
                ->whereIn('id', $selectedIds)
                ->update([
                    'recipient_deleted_at' => now(),
                ]);
        }

        $this->clearSelection();

        session()->flash('status', 'Selected messages deleted.');
    }

    public function clearCurrentFolder(): void
    {
        $userId = auth()->id();

        if ($this->tab === 'sent') {
            Message::query()
                ->where('sender_id', $userId)
                ->whereNull('sender_deleted_at')
                ->update([
                    'sender_deleted_at' => now(),
                ]);

            session()->flash('status', 'Your Sent messages have been cleared.');
        } else {
            Message::query()
                ->where('recipient_id', $userId)
                ->whereNull('recipient_deleted_at')
                ->update([
                    'recipient_deleted_at' => now(),
                ]);

            session()->flash('status', 'Your Inbox has been cleared.');
        }

        $this->clearSelection();

        $this->resetPage();
    }

    private function clearSelection(): void
    {
        $this->selectedMessages = [];
        $this->selectAll = false;
    }

    /**
     * @return array<int, int>
     */
    private function visibleMessageIds(): array
    {
        $userId = auth()->id();

        return $this->tab === 'sent'
            ? Message::query()
                ->where('sender_id', $userId)
                ->whereNull('sender_deleted_at')
                ->latest()
                ->limit(15)
                ->pluck('id')
                ->map(fn ($id) => (int) $id)
                ->all()
            : Message::query()
                ->where('recipient_id', $userId)
                ->whereNull('recipient_deleted_at')
                ->latest()
                ->limit(15)
                ->pluck('id')
                ->map(fn ($id) => (int) $id)
                ->all();
    }

    public function with(): array
    {
        $userId = auth()->id();

        $messages = $this->tab === 'sent'
            ? Message::query()
                ->where('sender_id', $userId)
                ->whereNull('sender_deleted_at')
                ->with('recipient.memberProfile')
                ->latest()
                ->paginate(15)
            : Message::query()
                ->where('recipient_id', $userId)
                ->whereNull('recipient_deleted_at')
                ->with('sender.memberProfile')
                ->latest()
                ->paginate(15);

        $unreadCount = Message::query()
            ->where('recipient_id', $userId)
            ->whereNull('recipient_deleted_at')
            ->whereNull('read_at')
            ->count();

        return [
            'messages' => $messages,
            'unreadCount' => $unreadCount,
        ];
    }
};
?>

<section class="bg-zinc-50">
    <div class="mx-auto max-w-7xl px-6 py-16 lg:px-8 lg:py-20">

        <div class="mx-auto max-w-4xl">

            @if (session('status'))
                <div class="mb-8 rounded-lg bg-green-50 p-4 text-sm text-green-800">
                    {{ session('status') }}
                </div>
            @endif

            <div class="text-center">

                <h1 class="text-3xl font-bold tracking-tight text-irdi-green sm:text-4xl">
                    Messages
                </h1>

                <div class="mx-auto mt-4 h-1 w-20 bg-irdi-gold"></div>

                <p class="mt-6 text-lg text-zinc-600">
                    Read and manage your private IRDI messages.
                </p>

            </div>

            @if (auth()->user()->messaging_disabled_at)
                <div class="mt-10 rounded-xl border border-amber-300 bg-amber-50 p-5">
                    <div class="flex gap-4">
                        <div class="shrink-0">
                            <div class="flex size-10 items-center justify-center rounded-full bg-amber-100">
                                <flux:icon.exclamation-triangle class="size-5 text-amber-700" />
                            </div>
                        </div>

                        <div class="min-w-0">
                            <h2 class="font-semibold text-amber-900">
                                Your messaging privileges are suspended
                            </h2>

                            <p class="mt-2 text-sm leading-6 text-amber-800">
                                You can continue to read and manage your existing messages,
                                but you cannot send new messages while this restriction is active.
                            </p>

                            <dl class="mt-4 space-y-2 text-sm">
                                <div class="flex flex-wrap gap-x-2">
                                    <dt class="font-medium text-amber-900">
                                        Suspended since:
                                    </dt>

                                    <dd class="text-amber-800">
                                        {{ auth()->user()->messaging_disabled_at->format('F j, Y \a\t g:i A') }}
                                    </dd>
                                </div>

                                @if (auth()->user()->messaging_disabled_until)
                                    <div class="flex flex-wrap gap-x-2">
                                        <dt class="font-medium text-amber-900">
                                            Scheduled restoration:
                                        </dt>

                                        <dd class="text-amber-800">
                                            {{ auth()->user()->messaging_disabled_until->format('F j, Y \a\t g:i A') }}
                                        </dd>
                                    </div>
                                @else
                                    <div class="flex flex-wrap gap-x-2">
                                        <dt class="font-medium text-amber-900">
                                            Duration:
                                        </dt>

                                        <dd class="text-amber-800">
                                            Indefinite
                                        </dd>
                                    </div>
                                @endif
                            </dl>

                            <p class="mt-4 text-sm leading-6 text-amber-800">
                                If you have questions about this restriction or believe it should be reviewed,
                                please contact IRDI.
                            </p>

                            <div class="mt-4">
                                <flux:button
                                    href="{{ url('/contact') }}"
                                    variant="outline"
                                    size="sm"
                                    wire:navigate
                                >
                                    Contact IRDI
                                </flux:button>
                            </div>
                        </div>
                    </div>
                </div>
            @endif

            <flux:card class="mt-10 p-6">

                <div class="flex flex-wrap items-center justify-between gap-4 border-b border-zinc-200 pb-5">

                    <div class="flex flex-wrap items-center gap-3">

                        <flux:button
                            type="button"
                            wire:click="showInbox"
                            :variant="$tab === 'inbox' ? 'primary' : 'outline'"
                        >
                            Inbox

                            @if ($unreadCount > 0)
                                ({{ $unreadCount }})
                            @endif
                        </flux:button>

                        <flux:button
                            type="button"
                            wire:click="showSent"
                            :variant="$tab === 'sent' ? 'primary' : 'outline'"
                        >
                            Sent
                        </flux:button>

                    </div>

                    @if ($messages->count() > 0)

                        <flux:button
                            type="button"
                            wire:click="clearCurrentFolder"
                            wire:confirm="{{ $tab === 'inbox'
                                ? 'Clear Inbox? This will remove all messages from your Inbox. This does not remove them from the sender\'s Sent messages.'
                                : 'Clear Sent Messages? This will remove all messages from your Sent list. Recipients will still keep their copies.'
                            }}"
                            variant="danger"
                            size="sm"
                        >
                            {{ $tab === 'inbox' ? 'Clear Inbox' : 'Clear Sent' }}
                        </flux:button>

                    @endif

                </div>

                <div class="mt-6">

                    @if ($messages->count() === 0)

                        <div class="rounded-lg bg-zinc-50 p-6 text-center">

                            <p class="text-sm text-zinc-600">
                                @if ($tab === 'inbox')
                                    You don't have any messages yet.
                                @else
                                    You haven't sent any messages yet.
                                @endif
                            </p>

                        </div>

                    @else

                        <div class="mb-3 flex flex-wrap items-center justify-between gap-3 rounded-lg bg-zinc-50 px-3 py-3">

                            <label class="flex cursor-pointer items-center gap-2 text-sm font-medium text-zinc-700">

                                <input
                                    type="checkbox"
                                    wire:model.live="selectAll"
                                    class="size-4 rounded border-zinc-300 text-irdi-green focus:ring-irdi-green"
                                >

                                Select All

                            </label>

                            @if (count($selectedMessages) > 0)

                                <flux:button
                                    type="button"
                                    wire:click="deleteSelected"
                                    wire:confirm="Delete the selected messages? They will be removed from your {{ $tab === 'inbox' ? 'Inbox' : 'Sent messages' }}, but the other member will keep their copy."
                                    variant="danger"
                                    size="sm"
                                    icon="trash"
                                >
                                    Delete Selected ({{ count($selectedMessages) }})
                                </flux:button>

                            @endif

                        </div>

                        <div class="divide-y divide-zinc-200">

                            @foreach ($messages as $message)

                                @php
                                    $otherProfile = $tab === 'sent'
                                        ? $message->recipient->memberProfile
                                        : $message->sender->memberProfile;

                                    $otherUser = $tab === 'sent'
                                        ? $message->recipient
                                        : $message->sender;
                                @endphp

                                <div
                                    wire:key="message-{{ $message->id }}"
                                    class="flex items-start gap-3 rounded-lg px-3 py-4 transition hover:bg-zinc-50"
                                >

                                    <div class="pt-1">

                                        <input
                                            type="checkbox"
                                            value="{{ $message->id }}"
                                            wire:model.live="selectedMessages"
                                            aria-label="Select message {{ $message->subject }}"
                                            class="size-4 rounded border-zinc-300 text-irdi-green focus:ring-irdi-green"
                                        >

                                    </div>

                                    <a
                                        href="{{ route('messages.show', $message) }}"
                                        wire:navigate
                                        class="min-w-0 flex-1"
                                    >

                                        <div class="flex flex-col gap-2 sm:flex-row sm:items-start sm:justify-between">

                                            <div class="min-w-0">

                                                <div class="flex flex-wrap items-center gap-2">

                                                    <p
                                                        class="
                                                            text-sm text-zinc-600
                                                            @if (
                                                                $tab === 'inbox'
                                                                && $message->read_at === null
                                                            )
                                                                font-bold text-irdi-green
                                                            @endif
                                                        "
                                                    >
                                                        @if ($tab === 'sent')
                                                            To:
                                                        @else
                                                            From:
                                                        @endif

                                                        {{ $otherProfile?->profile_name ?? $otherUser->name }}
                                                    </p>

                                                    @if ($otherProfile)

                                                        <span class="text-sm text-zinc-400">
                                                            {{ '@' . $otherProfile->username }}
                                                        </span>

                                                    @endif

                                                    @if (
                                                        $tab === 'inbox'
                                                        && $message->read_at === null
                                                    )

                                                        <flux:badge
                                                            size="sm"
                                                            color="amber"
                                                        >
                                                            Unread
                                                        </flux:badge>

                                                    @endif

                                                </div>

                                                <p
                                                    class="
                                                        mt-2 truncate
                                                        @if (
                                                            $tab === 'inbox'
                                                            && $message->read_at === null
                                                        )
                                                            font-semibold text-zinc-900
                                                        @else
                                                            text-zinc-800
                                                        @endif
                                                    "
                                                >
                                                    {{ $message->subject }}
                                                </p>

                                                <p class="mt-1 line-clamp-2 text-sm text-zinc-500">
                                                    {{ $message->body }}
                                                </p>

                                            </div>

                                            <p class="shrink-0 text-xs text-zinc-500">
                                                {{ $message->created_at->format('M j, Y g:i A') }}
                                            </p>

                                        </div>

                                    </a>

                                </div>

                            @endforeach

                        </div>

                        <div class="mt-6">
                            {{ $messages->links() }}
                        </div>

                    @endif

                </div>

            </flux:card>

        </div>

    </div>
</section>
