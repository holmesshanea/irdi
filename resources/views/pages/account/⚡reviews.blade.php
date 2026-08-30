<?php

use App\Models\MemberProfile;
use Livewire\Attributes\Layout;
use Livewire\Component;

new
#[Layout('components.layouts.public')]
class extends Component
{
    public function getMemberProfileProperty(): ?MemberProfile
    {
        return MemberProfile::query()
            ->where('user_id', auth()->id())
            ->with([
                'propertyReviewInvitations.review',
            ])
            ->first();
    }
};
?>

<section class="bg-zinc-50">
    <div class="mx-auto max-w-5xl px-6 py-16 lg:px-8 lg:py-20">

        <div class="mb-8">
            <a
                href="{{ route('account') }}"
                class="text-sm font-medium text-irdi-green hover:underline"
            >
                ← Back to Account
            </a>
        </div>

        @if (session('status'))
            <div class="mb-8 rounded-lg bg-green-50 p-4 text-sm text-green-800">
                {{ session('status') }}
            </div>
        @endif

        <div class="mb-10">
            <h1 class="text-3xl font-bold tracking-tight text-zinc-900">
                My Reviews
            </h1>

            <p class="mt-3 max-w-2xl text-zinc-600">
                View property owner review invitations and reviews connected to your IRDI member profile.
            </p>
        </div>

        @if (auth()->user()->membership_status === 'suspended')

            <div class="mb-8 rounded-xl border border-red-300 bg-red-50 p-5">

                <div class="flex gap-4">

                    <div class="shrink-0">
                        <div class="flex size-10 items-center justify-center rounded-full bg-red-100">
                            <flux:icon.exclamation-triangle class="size-5 text-red-700" />
                        </div>
                    </div>

                    <div class="min-w-0">

                        <h2 class="font-semibold text-red-900">
                            Your IRDI membership is suspended
                        </h2>

                        <p class="mt-2 text-sm leading-6 text-red-800">
                            You can continue to view your existing property owner reviews and invitation history,
                            and you may cancel an outstanding invitation. Creating or resending review invitations
                            is unavailable while your membership is suspended.
                        </p>

                        <p class="mt-4 text-sm leading-6 text-red-800">
                            If you have questions about your membership suspension or believe it should be reviewed,
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

        @if (! $this->memberProfile)

            <div class="rounded-xl border border-zinc-200 bg-white p-8">
                <h2 class="text-lg font-semibold text-zinc-900">
                    No Member Profile
                </h2>

                <p class="mt-2 text-sm text-zinc-600">
                    Create your IRDI member profile before requesting property owner reviews.
                </p>
            </div>

        @else

            @php
                $profile = $this->memberProfile;

                $completedCount = $profile->propertyReviewInvitations
                    ->filter(fn ($invitation) => $invitation->isUsed())
                    ->count();

                $cancelledCount = $profile->propertyReviewInvitations
                    ->filter(fn ($invitation) => $invitation->isCancelled())
                    ->count();

                $expiredCount = $profile->propertyReviewInvitations
                    ->filter(
                        fn ($invitation) =>
                            ! $invitation->isUsed()
                            && ! $invitation->isCancelled()
                            && $invitation->isExpired()
                    )
                    ->count();

                $pendingCount = $profile->propertyReviewInvitations
                    ->filter(fn ($invitation) => $invitation->isAvailable())
                    ->count();
            @endphp

            <div class="rounded-xl border border-zinc-200 bg-white p-6">

                <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">

                    <div>
                        <h2 class="text-xl font-semibold text-zinc-900">
                            {{ $profile->profile_name }}
                        </h2>

                        <p class="text-sm text-zinc-500">
                            {{ '@' . $profile->username }}
                        </p>
                    </div>

                    <span class="inline-flex w-fit rounded-full bg-zinc-100 px-3 py-1 text-xs font-medium text-zinc-700">
                        {{ $profile->propertyReviewInvitations->count() }}
                        {{ Str::plural('Invitation', $profile->propertyReviewInvitations->count()) }}
                    </span>

                </div>

                <div class="mt-6 flex flex-wrap gap-2">

                    <span class="inline-flex rounded-full bg-green-100 px-3 py-1 text-xs font-medium text-green-800">
                        {{ $completedCount }} Completed
                    </span>

                    <span class="inline-flex rounded-full bg-amber-100 px-3 py-1 text-xs font-medium text-amber-800">
                        {{ $pendingCount }} Pending
                    </span>

                    <span class="inline-flex rounded-full bg-zinc-100 px-3 py-1 text-xs font-medium text-zinc-700">
                        {{ $expiredCount }} Expired
                    </span>

                    <span class="inline-flex rounded-full bg-red-100 px-3 py-1 text-xs font-medium text-red-800">
                        {{ $cancelledCount }} Cancelled
                    </span>

                </div>

                <div class="mt-6">

                    @if ($profile->propertyReviewInvitations->isEmpty())

                        <div class="rounded-lg bg-zinc-50 p-6 text-center">
                            <p class="text-sm text-zinc-600">
                                You have not created any property owner review invitations yet.
                            </p>
                        </div>

                    @else

                        <div class="space-y-4">

                            @foreach (
                                $profile->propertyReviewInvitations
                                    ->sortByDesc('created_at')
                                as $invitation
                            )

                                <div class="rounded-lg border border-zinc-200 p-5">

                                    <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">

                                        <div>

                                            <p class="font-medium text-zinc-900">
                                                {{ $invitation->reviewer_email }}
                                            </p>

                                            <p class="mt-1 text-sm text-zinc-500">
                                                Sent {{ $invitation->created_at->format('F j, Y') }}
                                            </p>

                                            @if ($invitation->isCancelled())
                                                <p class="mt-1 text-xs text-zinc-500">
                                                    Cancelled {{ $invitation->cancelled_at->format('F j, Y') }}
                                                </p>
                                            @endif

                                        </div>

                                        <div class="flex flex-wrap items-center gap-2">

                                            @if ($invitation->isUsed())

                                                <span class="inline-flex rounded-full bg-green-100 px-3 py-1 text-xs font-medium text-green-800">
                                                    Completed
                                                </span>

                                            @elseif ($invitation->isCancelled())

                                                <span class="inline-flex rounded-full bg-red-100 px-3 py-1 text-xs font-medium text-red-800">
                                                    Cancelled
                                                </span>

                                            @elseif ($invitation->isExpired())

                                                <span class="inline-flex rounded-full bg-zinc-100 px-3 py-1 text-xs font-medium text-zinc-700">
                                                    Expired
                                                </span>

                                            @else

                                                <span class="inline-flex rounded-full bg-amber-100 px-3 py-1 text-xs font-medium text-amber-800">
                                                    Pending
                                                </span>

                                            @endif

                                            @if (
                                                ! $invitation->isUsed()
                                                && ! $invitation->isCancelled()
                                            )

                                                @if (auth()->user()->membership_status === 'active')

                                                    <form
                                                        action="{{ route('account.review-invitations.resend', $invitation) }}"
                                                        method="POST"
                                                    >
                                                        @csrf

                                                        <flux:button
                                                            type="submit"
                                                            variant="outline"
                                                            size="sm"
                                                            icon="arrow-path"
                                                        >
                                                            Resend
                                                        </flux:button>
                                                    </form>

                                                @endif

                                                <form
                                                    action="{{ route('account.review-invitations.cancel', $invitation) }}"
                                                    method="POST"
                                                    onsubmit="return confirm('Cancel this property owner review invitation?');"
                                                >
                                                    @csrf

                                                    <flux:button
                                                        type="submit"
                                                        variant="danger"
                                                        size="sm"
                                                        icon="x-mark"
                                                    >
                                                        Cancel
                                                    </flux:button>
                                                </form>

                                            @endif

                                        </div>

                                    </div>

                                    @if ($invitation->review)

                                        <div class="mt-5 border-t border-zinc-100 pt-5">

                                            @if ($invitation->review->hidden_at)

                                                <p class="text-sm text-zinc-500">
                                                    This review is currently unavailable.
                                                </p>

                                            @else

                                                <div class="space-y-4">

                                                    <div>
                                                        <p class="text-sm font-medium text-zinc-900">
                                                            Review received
                                                        </p>

                                                        <p class="mt-1 text-sm text-zinc-600">
                                                            Submitted {{ $invitation->review->created_at->format('F j, Y') }}
                                                        </p>
                                                    </div>

                                                    <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">

                                                        <div class="rounded-lg bg-zinc-50 p-4">

                                                            <p class="text-xs font-medium uppercase tracking-wide text-zinc-500">
                                                                Respect for Property
                                                            </p>

                                                            <p class="mt-2 text-lg font-semibold text-zinc-900">
                                                                {{ $invitation->review->respect_for_property }}/5
                                                            </p>

                                                        </div>

                                                        <div class="rounded-lg bg-zinc-50 p-4">

                                                            <p class="text-xs font-medium uppercase tracking-wide text-zinc-500">
                                                                Communication & Courtesy
                                                            </p>

                                                            <p class="mt-2 text-lg font-semibold text-zinc-900">
                                                                {{ $invitation->review->communication_courtesy }}/5
                                                            </p>

                                                        </div>

                                                        <div class="rounded-lg bg-zinc-50 p-4">

                                                            <p class="text-xs font-medium uppercase tracking-wide text-zinc-500">
                                                                Care of Property
                                                            </p>

                                                            <p class="mt-2 text-lg font-semibold text-zinc-900">
                                                                {{ $invitation->review->care_of_property }}/5
                                                            </p>

                                                        </div>

                                                        <div class="rounded-lg bg-zinc-50 p-4">

                                                            <p class="text-xs font-medium uppercase tracking-wide text-zinc-500">
                                                                Would Allow Return
                                                            </p>

                                                            <p class="mt-2 text-lg font-semibold text-zinc-900">
                                                                {{ $invitation->review->would_allow_return ? 'Yes' : 'No' }}
                                                            </p>

                                                        </div>

                                                    </div>

                                                    @if ($invitation->review->comments)

                                                        <div>

                                                            <p class="text-sm font-medium text-zinc-900">
                                                                Property Owner Comments
                                                            </p>

                                                            <p class="mt-2 whitespace-pre-line text-sm leading-6 text-zinc-600">
                                                                {{ $invitation->review->comments }}
                                                            </p>

                                                        </div>

                                                    @endif

                                                </div>

                                            @endif

                                        </div>

                                    @endif

                                </div>

                            @endforeach

                        </div>

                    @endif

                </div>

            </div>

        @endif

    </div>
</section>
