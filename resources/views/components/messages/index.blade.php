
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

    public function showInbox(): void
    {
        $this->tab = 'inbox';

        $this->resetPage();
    }

    public function showSent(): void
    {
        $this->tab = 'sent';

        $this->resetPage();
    }

    public function with(): array
    {
        $userId = auth()->id();

        $messages = $this->tab === 'sent'
            ? Message::query()
                ->where('sender_id', $userId)
                ->with('recipient.memberProfile')
                ->latest()
                ->paginate(15)
            : Message::query()
                ->where('recipient_id', $userId)
                ->with('sender.memberProfile')
                ->latest()
                ->paginate(15);

        $unreadCount = Message::query()
            ->where('recipient_id', $userId)
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

            <div class="text-center">

                <h1 class="text-3xl font-bold tracking-tight text-irdi-green sm:text-4xl">
                    Messages
                </h1>

                <div class="mx-auto mt-4 h-1 w-20 bg-irdi-gold"></div>

                <p class="mt-6 text-lg text-zinc-600">
                    Read and manage your private IRDI messages.
                </p>

            </div>

            <flux:card class="mt-10 p-6">

                <div class="flex flex-wrap items-center gap-3 border-b border-zinc-200 pb-5">

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

                                <a
                                    href="{{ route('messages.show', $message) }}"
                                    wire:navigate
                                    class="block rounded-lg px-3 py-4 transition hover:bg-zinc-50"
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
