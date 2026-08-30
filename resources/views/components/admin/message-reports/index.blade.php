<?php

use App\Models\MessageReport;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithPagination;

new
#[Layout('components.layouts.public')]
class extends Component
{
    use WithPagination;

    public string $status = 'pending';

    public function updatedStatus(): void
    {
        $this->resetPage();
    }

    public function getReportsProperty()
    {
        return MessageReport::query()
            ->with([
                'message.sender.memberProfile',
                'message.recipient.memberProfile',
                'reporter.memberProfile',
            ])
            ->when(
                $this->status !== 'all',
                fn ($query) => $query->where('status', $this->status)
            )
            ->latest()
            ->paginate(20);
    }

    public function getPendingCountProperty(): int
    {
        return MessageReport::query()
            ->where('status', 'pending')
            ->count();
    }

    public function getReviewedCountProperty(): int
    {
        return MessageReport::query()
            ->where('status', 'reviewed')
            ->count();
    }

    public function getDismissedCountProperty(): int
    {
        return MessageReport::query()
            ->where('status', 'dismissed')
            ->count();
    }

    public function getTotalCountProperty(): int
    {
        return MessageReport::query()->count();
    }
};
?>

<section class="bg-zinc-50">

    <div class="mx-auto max-w-7xl px-6 py-16 lg:px-8 lg:py-20">

        {{-- Header --}}
        <div class="mb-8">
            <p class="text-sm font-medium uppercase tracking-wide text-irdi-green">
                Administration
            </p>

            <h1 class="mt-3 text-3xl font-bold tracking-tight text-zinc-950 sm:text-4xl">
                Message Reports
            </h1>

            <p class="mt-3 text-sm text-zinc-600">
                Review messages reported by IRDI members and manage moderation status.
            </p>
        </div>

        {{-- Status summary --}}
        <div class="mx-auto grid max-w-5xl gap-4 sm:grid-cols-2 lg:grid-cols-4">

            <button
                type="button"
                wire:click="$set('status', 'pending')"
                class="rounded-xl border bg-white p-5 text-left shadow-sm transition hover:border-irdi-green
                    {{ $status === 'pending' ? 'border-irdi-green ring-1 ring-irdi-green' : 'border-zinc-200' }}"
            >
                <p class="text-sm font-medium text-zinc-500">
                    Pending
                </p>

                <p class="mt-2 text-2xl font-bold text-amber-700">
                    {{ $this->pendingCount }}
                </p>
            </button>

            <button
                type="button"
                wire:click="$set('status', 'reviewed')"
                class="rounded-xl border bg-white p-5 text-left shadow-sm transition hover:border-irdi-green
                    {{ $status === 'reviewed' ? 'border-irdi-green ring-1 ring-irdi-green' : 'border-zinc-200' }}"
            >
                <p class="text-sm font-medium text-zinc-500">
                    Reviewed
                </p>

                <p class="mt-2 text-2xl font-bold text-green-700">
                    {{ $this->reviewedCount }}
                </p>
            </button>

            <button
                type="button"
                wire:click="$set('status', 'dismissed')"
                class="rounded-xl border bg-white p-5 text-left shadow-sm transition hover:border-irdi-green
                    {{ $status === 'dismissed' ? 'border-irdi-green ring-1 ring-irdi-green' : 'border-zinc-200' }}"
            >
                <p class="text-sm font-medium text-zinc-500">
                    Dismissed
                </p>

                <p class="mt-2 text-2xl font-bold text-zinc-700">
                    {{ $this->dismissedCount }}
                </p>
            </button>

            <button
                type="button"
                wire:click="$set('status', 'all')"
                class="rounded-xl border bg-white p-5 text-left shadow-sm transition hover:border-irdi-green
                    {{ $status === 'all' ? 'border-irdi-green ring-1 ring-irdi-green' : 'border-zinc-200' }}"
            >
                <p class="text-sm font-medium text-zinc-500">
                    All Reports
                </p>

                <p class="mt-2 text-2xl font-bold text-irdi-green">
                    {{ $this->totalCount }}
                </p>
            </button>

        </div>

        {{-- Reports --}}
        <div class="mx-auto mt-8 max-w-5xl">

            <flux:card class="overflow-hidden">

                <div class="border-b border-zinc-200 px-6 py-5">

                    <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">

                        <div>
                            <h2 class="text-lg font-semibold text-irdi-green">
                                Reports
                            </h2>

                            <p class="mt-1 text-sm text-zinc-500">
                                Showing
                                {{ $status === 'all' ? 'all' : ucfirst($status) }}
                                message reports.
                            </p>
                        </div>

                        <flux:select wire:model.live="status" class="sm:w-44">
                            <flux:select.option value="pending">
                                Pending
                            </flux:select.option>

                            <flux:select.option value="reviewed">
                                Reviewed
                            </flux:select.option>

                            <flux:select.option value="dismissed">
                                Dismissed
                            </flux:select.option>

                            <flux:select.option value="all">
                                All Reports
                            </flux:select.option>
                        </flux:select>

                    </div>

                </div>

                @forelse ($this->reports as $report)

                    @php
                        $message = $report->message;

                        $reporterProfile = $report->reporter?->memberProfile;

                        $reportedUser = null;

                        if ($message) {
                            $reportedUser =
                                $message->sender_id === $report->reporter_id
                                    ? $message->recipient
                                    : $message->sender;
                        }

                        $reportedProfile = $reportedUser?->memberProfile;
                    @endphp

                    <div class="border-b border-zinc-200 px-6 py-6 last:border-b-0">

                        <div class="flex flex-col gap-6 lg:flex-row lg:items-start lg:justify-between">

                            <div class="min-w-0 flex-1">

                                {{-- Status --}}
                                <div class="flex flex-wrap items-center gap-2">

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

                                    <span class="text-sm text-zinc-500">
                                        Report #{{ $report->id }}
                                    </span>

                                </div>

                                {{-- Members --}}
                                <div class="mt-5 grid gap-5 sm:grid-cols-2">

                                    <div>

                                        <p class="text-xs font-semibold uppercase tracking-wide text-zinc-500">
                                            Reporter
                                        </p>

                                        <p class="mt-1 font-semibold text-zinc-900">
                                            {{ $reporterProfile?->profile_name ?? $report->reporter?->name ?? 'Unknown member' }}
                                        </p>

                                        @if ($reporterProfile)

                                            <p class="mt-1 text-sm text-zinc-500">
                                                {{ '@' . $reporterProfile->username }}
                                            </p>

                                        @endif

                                    </div>

                                    <div>

                                        <p class="text-xs font-semibold uppercase tracking-wide text-zinc-500">
                                            Reported Member
                                        </p>

                                        <p class="mt-1 font-semibold text-zinc-900">
                                            {{ $reportedProfile?->profile_name ?? $reportedUser?->name ?? 'Unknown member' }}
                                        </p>

                                        @if ($reportedProfile)

                                            <p class="mt-1 text-sm text-zinc-500">
                                                {{ '@' . $reportedProfile->username }}
                                            </p>

                                        @endif

                                    </div>

                                </div>

                                {{-- Reason --}}
                                <div class="mt-5">

                                    <p class="text-xs font-semibold uppercase tracking-wide text-zinc-500">
                                        Reason
                                    </p>

                                    <p class="mt-1 text-sm font-medium text-zinc-900">
                                        {{ $report->reason }}
                                    </p>

                                </div>

                                {{-- Message subject --}}
                                @if ($message)

                                    <div class="mt-5">

                                        <p class="text-xs font-semibold uppercase tracking-wide text-zinc-500">
                                            Message
                                        </p>

                                        <p class="mt-1 truncate text-sm text-zinc-700">
                                            {{ $message->subject }}
                                        </p>

                                    </div>

                                @endif

                            </div>

                            {{-- Date / actions --}}
                            <div class="shrink-0 lg:text-right">

                                <p class="text-sm text-zinc-500">
                                    {{ $report->created_at->format('F j, Y') }}
                                </p>

                                <p class="mt-1 text-sm text-zinc-500">
                                    {{ $report->created_at->format('g:i A') }}
                                </p>

                                <div class="mt-4">

                                    <flux:button
                                        :href="route('admin.message-reports.show', $report)"
                                        variant="outline"
                                        size="sm"
                                        icon="eye"
                                    >
                                        Review Report
                                    </flux:button>

                                </div>

                            </div>

                        </div>

                    </div>

                @empty

                    <div class="px-6 py-16 text-center">

                        <flux:icon.check-circle class="mx-auto size-8 text-zinc-400" />

                        <h3 class="mt-4 font-semibold text-zinc-900">
                            No reports found
                        </h3>

                        <p class="mt-2 text-sm text-zinc-500">
                            There are no
                            {{ $status === 'all' ? '' : $status }}
                            message reports.
                        </p>

                    </div>

                @endforelse

            </flux:card>

            @if ($this->reports->hasPages())

                <div class="mt-8">
                    {{ $this->reports->links() }}
                </div>

            @endif

        </div>

    </div>

</section>
