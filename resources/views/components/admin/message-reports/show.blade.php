<?php

use App\Models\MessageReport;
use App\Models\MessageReportModeration;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Component;

new
#[Layout('components.layouts.public')]
class extends Component
{
    public MessageReport $report;

    public function mount(MessageReport $report): void
    {
        $this->loadReport();
    }

    public function markReviewed(): void
    {
        $this->report->update([
            'status' => 'reviewed',
        ]);

        MessageReportModeration::create([
            'message_report_id' => $this->report->id,
            'user_id' => Auth::id(),
            'action' => 'reviewed',
        ]);

        $this->loadReport();

        session()->flash('status', 'Message report marked as reviewed.');
    }

    public function dismissReport(): void
    {
        $this->report->update([
            'status' => 'dismissed',
        ]);

        MessageReportModeration::create([
            'message_report_id' => $this->report->id,
            'user_id' => Auth::id(),
            'action' => 'dismissed',
        ]);

        $this->loadReport();

        session()->flash('status', 'Message report dismissed.');
    }

    public function reopenReport(): void
    {
        $this->report->update([
            'status' => 'pending',
        ]);

        MessageReportModeration::create([
            'message_report_id' => $this->report->id,
            'user_id' => Auth::id(),
            'action' => 'reopened',
        ]);

        $this->loadReport();

        session()->flash('status', 'Message report returned to pending.');
    }

    private function loadReport(): void
    {
        $this->report = $this->report->fresh([
            'message.sender.memberProfile',
            'message.recipient.memberProfile',
            'reporter.memberProfile',
            'moderations.user',
        ]);
    }
};
?>

<section class="bg-zinc-50">

    <div class="mx-auto max-w-5xl px-6 py-16 lg:px-8 lg:py-20">

        {{-- Back --}}
        <div class="mb-8">
            <a
                href="{{ route('admin.message-reports.index') }}"
                class="text-sm font-medium text-irdi-green hover:underline"
            >
                ← Back to Message Reports
            </a>
        </div>

        {{-- Flash --}}
        @if (session('status'))

            <div class="mb-6 rounded-xl border border-green-200 bg-green-50 px-5 py-4 text-sm font-medium text-green-800">
                {{ session('status') }}
            </div>

        @endif

        @php
            $message = $report->message;

            $reporter = $report->reporter;
            $reporterProfile = $reporter?->memberProfile;

            $reportedUser = null;

            if ($message) {
                $reportedUser =
                    $message->sender_id === $report->reporter_id
                        ? $message->recipient
                        : $message->sender;
            }

            $reportedProfile = $reportedUser?->memberProfile;
        @endphp

        {{-- Header --}}
        <div class="mb-8 flex flex-col gap-5 sm:flex-row sm:items-start sm:justify-between">

            <div>

                <div class="flex flex-wrap items-center gap-3">

                    <h1 class="text-3xl font-bold tracking-tight text-irdi-green">
                        Message Report #{{ $report->id }}
                    </h1>

                    @if ($report->status === 'pending')

                        <flux:badge color="amber">
                            Pending
                        </flux:badge>

                    @elseif ($report->status === 'reviewed')

                        <flux:badge color="green">
                            Reviewed
                        </flux:badge>

                    @else

                        <flux:badge color="zinc">
                            Dismissed
                        </flux:badge>

                    @endif

                </div>

                <p class="mt-3 text-sm text-zinc-500">
                    Reported {{ $report->created_at->format('F j, Y \a\t g:i A') }}
                </p>

            </div>

            {{-- Moderation actions --}}
            <div class="flex flex-wrap gap-2">

                @if ($report->status !== 'reviewed')

                    <flux:button
                        wire:click="markReviewed"
                        variant="primary"
                        icon="check"
                    >
                        Mark Reviewed
                    </flux:button>

                @endif

                @if ($report->status !== 'dismissed')

                    <flux:button
                        wire:click="dismissReport"
                        variant="outline"
                        icon="x-mark"
                    >
                        Dismiss
                    </flux:button>

                @endif

                @if ($report->status !== 'pending')

                    <flux:button
                        wire:click="reopenReport"
                        variant="ghost"
                    >
                        Return to Pending
                    </flux:button>

                @endif

            </div>

        </div>

        <div class="space-y-6">

            {{-- Report information --}}
            <flux:card>

                <div class="p-6">

                    <h2 class="text-lg font-semibold text-irdi-green">
                        Report Information
                    </h2>

                    <div class="mt-6 grid gap-6 md:grid-cols-2">

                        {{-- Reporter --}}
                        <div>

                            <p class="text-xs font-semibold uppercase tracking-wide text-zinc-500">
                                Reporter
                            </p>

                            <p class="mt-2 font-semibold text-zinc-900">
                                {{ $reporterProfile?->profile_name ?? $reporter?->name ?? 'Unknown member' }}
                            </p>

                            @if ($reporterProfile)

                                <a
                                    href="{{ route('member-profiles.show', $reporterProfile) }}"
                                    class="mt-1 inline-block text-sm text-irdi-green hover:underline"
                                >
                                    {{ '@' . $reporterProfile->username }}
                                </a>

                            @endif

                            @if ($reporter)

                                <p class="mt-2 text-sm text-zinc-500">
                                    User ID: {{ $reporter->id }}
                                </p>

                            @endif

                        </div>

                        {{-- Reported member --}}
                        <div>

                            <p class="text-xs font-semibold uppercase tracking-wide text-zinc-500">
                                Reported Member
                            </p>

                            <p class="mt-2 font-semibold text-zinc-900">
                                {{ $reportedProfile?->profile_name ?? $reportedUser?->name ?? 'Unknown member' }}
                            </p>

                            @if ($reportedProfile)

                                <a
                                    href="{{ route('member-profiles.show', $reportedProfile) }}"
                                    class="mt-1 inline-block text-sm text-irdi-green hover:underline"
                                >
                                    {{ '@' . $reportedProfile->username }}
                                </a>

                            @endif

                            @if ($reportedUser)

                                <p class="mt-2 text-sm text-zinc-500">
                                    User ID: {{ $reportedUser->id }}
                                </p>

                            @endif

                        </div>

                    </div>

                    <div class="mt-6 border-t border-zinc-200 pt-6">

                        <p class="text-xs font-semibold uppercase tracking-wide text-zinc-500">
                            Reason
                        </p>

                        <p class="mt-2 font-medium text-zinc-900">
                            {{ $report->reason }}
                        </p>

                    </div>

                    @if ($report->details)

                        <div class="mt-6">

                            <p class="text-xs font-semibold uppercase tracking-wide text-zinc-500">
                                Additional Details
                            </p>

                            <div class="mt-2 whitespace-pre-wrap rounded-lg bg-zinc-50 p-4 text-sm leading-6 text-zinc-700">{{ $report->details }}</div>

                        </div>

                    @endif

                </div>

            </flux:card>

            {{-- Original message --}}
            <flux:card>

                <div class="border-b border-zinc-200 p-6">

                    <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">

                        <div>

                            <h2 class="text-lg font-semibold text-irdi-green">
                                Original Message
                            </h2>

                            <p class="mt-1 text-sm text-zinc-500">
                                This is the complete stored message associated with the report.
                            </p>

                        </div>

                        @if ($message)

                            <span class="text-sm text-zinc-500">
                                Message #{{ $message->id }}
                            </span>

                        @endif

                    </div>

                </div>

                @if ($message)

                    <div class="p-6">

                        <div class="grid gap-6 md:grid-cols-2">

                            {{-- Sender --}}
                            <div>

                                <p class="text-xs font-semibold uppercase tracking-wide text-zinc-500">
                                    From
                                </p>

                                <p class="mt-2 font-semibold text-zinc-900">
                                    {{ $message->sender?->memberProfile?->profile_name ?? $message->sender?->name ?? 'Unknown member' }}
                                </p>

                                @if ($message->sender?->memberProfile)

                                    <p class="mt-1 text-sm text-zinc-500">
                                        {{ '@' . $message->sender->memberProfile->username }}
                                    </p>

                                @endif

                            </div>

                            {{-- Recipient --}}
                            <div>

                                <p class="text-xs font-semibold uppercase tracking-wide text-zinc-500">
                                    To
                                </p>

                                <p class="mt-2 font-semibold text-zinc-900">
                                    {{ $message->recipient?->memberProfile?->profile_name ?? $message->recipient?->name ?? 'Unknown member' }}
                                </p>

                                @if ($message->recipient?->memberProfile)

                                    <p class="mt-1 text-sm text-zinc-500">
                                        {{ '@' . $message->recipient->memberProfile->username }}
                                    </p>

                                @endif

                            </div>

                        </div>

                        <div class="mt-6 border-t border-zinc-200 pt-6">

                            <p class="text-xs font-semibold uppercase tracking-wide text-zinc-500">
                                Subject
                            </p>

                            <p class="mt-2 font-semibold text-zinc-900">
                                {{ $message->subject }}
                            </p>

                        </div>

                        <div class="mt-6">

                            <p class="text-xs font-semibold uppercase tracking-wide text-zinc-500">
                                Message
                            </p>

                            <div class="mt-3 whitespace-pre-wrap rounded-xl border border-zinc-200 bg-white p-5 text-sm leading-7 text-zinc-700">{{ $message->body }}</div>

                        </div>

                        <div class="mt-6 grid gap-4 border-t border-zinc-200 pt-6 sm:grid-cols-3">

                            <div>

                                <p class="text-xs font-semibold uppercase tracking-wide text-zinc-500">
                                    Sent
                                </p>

                                <p class="mt-1 text-sm text-zinc-700">
                                    {{ $message->created_at->format('F j, Y g:i A') }}
                                </p>

                            </div>

                            <div>

                                <p class="text-xs font-semibold uppercase tracking-wide text-zinc-500">
                                    Sender Copy
                                </p>

                                @if ($message->sender_deleted_at)

                                    <p class="mt-1 text-sm font-medium text-red-700">
                                        Deleted
                                    </p>

                                    <p class="mt-1 text-xs text-zinc-500">
                                        {{ $message->sender_deleted_at->format('F j, Y g:i A') }}
                                    </p>

                                @else

                                    <p class="mt-1 text-sm font-medium text-green-700">
                                        Available
                                    </p>

                                @endif

                            </div>

                            <div>

                                <p class="text-xs font-semibold uppercase tracking-wide text-zinc-500">
                                    Recipient Copy
                                </p>

                                @if ($message->recipient_deleted_at)

                                    <p class="mt-1 text-sm font-medium text-red-700">
                                        Deleted
                                    </p>

                                    <p class="mt-1 text-xs text-zinc-500">
                                        {{ $message->recipient_deleted_at->format('F j, Y g:i A') }}
                                    </p>

                                @else

                                    <p class="mt-1 text-sm font-medium text-green-700">
                                        Available
                                    </p>

                                @endif

                            </div>

                        </div>

                    </div>

                @else

                    <div class="p-10 text-center">

                        <flux:icon.exclamation-triangle class="mx-auto size-8 text-amber-500" />

                        <h3 class="mt-4 font-semibold text-zinc-900">
                            Original message unavailable
                        </h3>

                        <p class="mt-2 text-sm text-zinc-500">
                            The message associated with this report could not be found.
                        </p>

                    </div>

                @endif

            </flux:card>

            {{-- Moderation history --}}
            <flux:card>

                <div class="border-b border-zinc-200 p-6">

                    <h2 class="text-lg font-semibold text-irdi-green">
                        Moderation History
                    </h2>

                    <p class="mt-1 text-sm text-zinc-500">
                        Administrative actions taken on this message report.
                    </p>

                </div>

                @forelse ($report->moderations->sortByDesc('created_at') as $moderation)

                    <div class="border-b border-zinc-200 px-6 py-5 last:border-b-0">

                        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">

                            <div class="flex items-center gap-3">

                                @if ($moderation->action === 'reviewed')

                                    <flux:badge color="green">
                                        Reviewed
                                    </flux:badge>

                                @elseif ($moderation->action === 'dismissed')

                                    <flux:badge color="zinc">
                                        Dismissed
                                    </flux:badge>

                                @elseif ($moderation->action === 'reopened')

                                    <flux:badge color="amber">
                                        Returned to Pending
                                    </flux:badge>

                                @endif

                                <div>

                                    <p class="font-medium text-zinc-900">
                                        {{ $moderation->user?->name ?? 'Unknown administrator' }}
                                    </p>

                                    @if ($moderation->user)

                                        <p class="mt-1 text-xs text-zinc-500">
                                            Admin User ID: {{ $moderation->user->id }}
                                        </p>

                                    @endif

                                </div>

                            </div>

                            <div class="text-sm text-zinc-500 sm:text-right">

                                <p>
                                    {{ $moderation->created_at->format('F j, Y') }}
                                </p>

                                <p class="mt-1">
                                    {{ $moderation->created_at->format('g:i A') }}
                                </p>

                            </div>

                        </div>

                    </div>

                @empty

                    <div class="px-6 py-10 text-center">

                        <p class="text-sm text-zinc-500">
                            No moderation actions have been recorded yet.
                        </p>

                    </div>

                @endforelse

            </flux:card>

        </div>

    </div>

</section>
