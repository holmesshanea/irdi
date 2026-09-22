<?php

use App\Models\MemberProfile;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

new
#[Layout('components.layouts.public')]
#[Title('My Reviews | IRDI')]
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
                {{ __('← Back to Account') }}
            </a>
        </div>

        @if (session('status'))
            <div
                role="status"
                aria-live="polite"
                aria-atomic="true"
                class="mb-8 rounded-lg bg-green-50 p-4 text-sm text-green-800"
            >
                {{ session('status') }}
            </div>
        @endif

        <div class="mb-10">
            <h1 class="text-3xl font-bold tracking-tight text-zinc-900">
                {{ __('My Reviews') }}
            </h1>

            <p class="mt-3 max-w-2xl text-zinc-600">
                {{ __('Manage property owner feedback connected to your IRDI member profile and review the public feedback that helps provide real-world context for your membership record.') }}
            </p>
        </div>

        <div class="mb-8 rounded-xl border border-irdi-gold/40 bg-irdi-gold/10 p-5 sm:p-6">
            <div class="flex items-start gap-3">
                <flux:icon.shield-check class="mt-0.5 size-5 shrink-0 text-irdi-green" />

                <div>
                    <h2 class="font-semibold text-irdi-green">
                        {{ __('Your Property Owner Feedback Record') }}
                    </h2>

                    <p class="mt-2 text-sm leading-6 text-zinc-700">
                        {{ __('Property owner feedback adds first-hand experience to your public IRDI profile and helps support trust and accountability. Public ratings, answers, and comments may appear on or contribute to your public Property Owner Feedback record.') }}
                    </p>

                    <p class="mt-2 text-sm leading-6 text-zinc-600">
                        {{ __('Private concerns submitted to IRDI are not shown here, on your public profile, or elsewhere in the member-facing review experience. They are available only to authorized IRDI staff for review.') }}
                    </p>
                </div>
            </div>
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
                            {{ __('Your IRDI membership is suspended') }}
                        </h2>

                        <p class="mt-2 text-sm leading-6 text-red-800">
                            {{ __('You can continue to view your existing property owner reviews and invitation history, and you may cancel an outstanding invitation. Creating or resending review invitations is unavailable while your membership is suspended.') }}
                        </p>

                        <p class="mt-4 text-sm leading-6 text-red-800">
                            {{ __('If you have questions about your membership suspension or believe it should be reviewed, please contact IRDI.') }}
                        </p>

                        <div class="mt-4">
                            <flux:button
                                href="{{ url('/contact') }}"
                                variant="outline"
                                size="sm"
                                wire:navigate
                            >
                                {{ __('Contact IRDI') }}
                            </flux:button>
                        </div>

                    </div>

                </div>

            </div>

        @endif

        @if (! $this->memberProfile)

            <div class="rounded-xl border border-zinc-200 bg-white p-8">
                <h2 class="text-lg font-semibold text-zinc-900">
                    {{ __('No Member Profile') }}
                </h2>

                <p class="mt-2 text-sm text-zinc-600">
                    {{ __('Create your IRDI member profile before requesting property owner reviews.') }}
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

            @if ((auth()->user()->isInGoodStanding() && ! auth()->user()->memberProfile?->personal_information_hidden))

                <div class="mb-8 rounded-xl border border-irdi-green/20 bg-white p-6 shadow-sm sm:p-8">

                    <div class="flex flex-col gap-6 lg:flex-row lg:items-end lg:justify-between">

                        <div class="max-w-2xl">

                            <div class="flex items-center gap-3">
                                <div class="flex size-10 shrink-0 items-center justify-center rounded-full bg-irdi-green/10">
                                    <flux:icon.envelope class="size-5 text-irdi-green" />
                                </div>

                                <h2 class="text-xl font-semibold text-zinc-900">
                                    {{ __('Request Property Owner Review') }}
                                </h2>
                            </div>

                            <p class="mt-3 text-sm leading-6 text-zinc-600">
                                {{ __('IRDI will email the property owner a private, single-use invitation to provide feedback about their experience with you.') }}
                            </p>

                            <p class="mt-1 text-sm text-zinc-500">
                                {{ $profile->profile_name }} · {{ '@' . $profile->username }}
                            </p>

                        </div>

                        <form
                            action="{{ route('account.review-invitations.store', $profile) }}"
                            method="POST"
                            class="w-full lg:max-w-md"
                        >
                            @csrf

                            <label
                                for="reviews_reviewer_email_{{ $profile->id }}"
                                class="block text-sm font-medium text-zinc-700"
                            >
                                {{ __('Property Owner Email') }}
                            </label>

                            <div class="mt-2 flex flex-col gap-3 sm:flex-row">

                                <input
                                    id="reviews_reviewer_email_{{ $profile->id }}"
                                    name="reviewer_email"
                                    type="email"
                                    required
                                    autocomplete="email"
                                    value="{{ old('reviewer_email') }}"
                                    placeholder="{{ __('owner@example.com') }}"
                                    class="block min-w-0 flex-1 rounded-lg border border-zinc-300 px-3 py-2 text-sm text-zinc-900"
                                >

                                <flux:button
                                    type="submit"
                                    variant="primary"
                                    icon="envelope"
                                    class="shrink-0"
                                >
                                    {{ __('Send Invitation') }}
                                </flux:button>

                            </div>

                            @error('reviewer_email')
                            <p role="alert" class="mt-2 text-sm text-red-600">
                                {{ $message }}
                            </p>
                            @enderror

                            @error('reviewer_email_' . $profile->id)
                            <p role="alert" class="mt-2 text-sm text-red-600">
                                {{ $message }}
                            </p>
                            @enderror

                        </form>

                    </div>

                </div>

            @endif

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
                        {{ __(':count Completed', ['count' => $completedCount]) }}
                    </span>

                    <span class="inline-flex rounded-full bg-amber-100 px-3 py-1 text-xs font-medium text-amber-800">
                        {{ __(':count Pending', ['count' => $pendingCount]) }}
                    </span>

                    <span class="inline-flex rounded-full bg-zinc-100 px-3 py-1 text-xs font-medium text-zinc-700">
                        {{ __(':count Expired', ['count' => $expiredCount]) }}
                    </span>

                    <span class="inline-flex rounded-full bg-red-100 px-3 py-1 text-xs font-medium text-red-800">
                        {{ __(':count Cancelled', ['count' => $cancelledCount]) }}
                    </span>

                </div>

                <div class="mt-6">

                    @if ($profile->propertyReviewInvitations->isEmpty())

                        <div class="rounded-lg bg-zinc-50 p-6 text-center">
                            <p class="text-sm text-zinc-600">
                                {{ __('You have not created any property owner review invitations yet.') }}
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
                                                {{ __('Sent :date', ['date' => $invitation->created_at->translatedFormat('F j, Y')]) }}
                                            </p>

                                            @if ($invitation->isCancelled())
                                                <p class="mt-1 text-xs text-zinc-500">
                                                    {{ __('Cancelled :date', ['date' => $invitation->cancelled_at->translatedFormat('F j, Y')]) }}
                                                </p>
                                            @endif

                                        </div>

                                        <div class="flex flex-wrap items-center gap-2">

                                            @if ($invitation->isUsed())

                                                <span class="inline-flex rounded-full bg-green-100 px-3 py-1 text-xs font-medium text-green-800">
                                                    {{ __('Completed') }}
                                                </span>

                                            @elseif ($invitation->isCancelled())

                                                <span class="inline-flex rounded-full bg-red-100 px-3 py-1 text-xs font-medium text-red-800">
                                                    {{ __('Cancelled') }}
                                                </span>

                                            @elseif ($invitation->isExpired())

                                                <span class="inline-flex rounded-full bg-zinc-100 px-3 py-1 text-xs font-medium text-zinc-700">
                                                    {{ __('Expired') }}
                                                </span>

                                            @else

                                                <span class="inline-flex rounded-full bg-amber-100 px-3 py-1 text-xs font-medium text-amber-800">
                                                    {{ __('Pending') }}
                                                </span>

                                            @endif

                                            @if (
                                                ! $invitation->isUsed()
                                                && ! $invitation->isCancelled()
                                            )

                                                @if ((auth()->user()->isInGoodStanding() && ! auth()->user()->memberProfile?->personal_information_hidden))

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
                                                            {{ __('Resend') }}
                                                        </flux:button>
                                                    </form>

                                                @endif

                                                <form
                                                    action="{{ route('account.review-invitations.cancel', $invitation) }}"
                                                    method="POST"
                                                    onsubmit="return confirm(@js(__('Cancel this property owner review invitation?')));"
                                                >
                                                    @csrf

                                                    <flux:button
                                                        type="submit"
                                                        variant="danger"
                                                        size="sm"
                                                        icon="x-mark"
                                                    >
                                                        {{ __('Cancel') }}
                                                    </flux:button>
                                                </form>

                                            @endif

                                        </div>

                                    </div>

                                    @if ($invitation->review)

                                        <div class="mt-5 border-t border-zinc-100 pt-5">

                                            @if ($invitation->review->hidden_at)

                                                <p class="text-sm text-zinc-500">
                                                    {{ __('This review is currently unavailable.') }}
                                                </p>

                                            @else

                                                <div class="space-y-4">

                                                    <div>
                                                        <p class="text-sm font-medium text-zinc-900">
                                                            {{ __('Review received') }}
                                                        </p>

                                                        <p class="mt-1 text-sm text-zinc-600">
                                                            {{ __('Submitted :date', ['date' => $invitation->review->created_at->translatedFormat('F j, Y')]) }}
                                                        </p>
                                                    </div>

                                                    @if ($invitation->review->overall_experience !== null)

                                                        <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-5">

                                                            <div class="rounded-lg bg-zinc-50 p-4">
                                                                <p class="text-xs font-medium uppercase tracking-wide text-zinc-500">
                                                                    {{ __('Overall Experience') }}
                                                                </p>
                                                                <p class="mt-2 text-lg font-semibold text-zinc-900">
                                                                    {{ $invitation->review->overall_experience }}/5
                                                                </p>
                                                            </div>

                                                            <div class="rounded-lg bg-zinc-50 p-4">
                                                                <p class="text-xs font-medium uppercase tracking-wide text-zinc-500">
                                                                    {{ __('Respect for Property') }}
                                                                </p>
                                                                <p class="mt-2 text-lg font-semibold text-zinc-900">
                                                                    {{ $invitation->review->respect_for_property }}/5
                                                                </p>
                                                            </div>

                                                            <div class="rounded-lg bg-zinc-50 p-4">
                                                                <p class="text-xs font-medium uppercase tracking-wide text-zinc-500">
                                                                    {{ __('Communication & Courtesy') }}
                                                                </p>
                                                                <p class="mt-2 text-lg font-semibold text-zinc-900">
                                                                    {{ $invitation->review->communication_courtesy }}/5
                                                                </p>
                                                            </div>

                                                            <div class="rounded-lg bg-zinc-50 p-4">
                                                                <p class="text-xs font-medium uppercase tracking-wide text-zinc-500">
                                                                    {{ __('Property Left Satisfactorily') }}
                                                                </p>
                                                                <p class="mt-2 text-lg font-semibold text-zinc-900">
                                                                    {{ $invitation->review->property_left_satisfactorily ? __('Yes') : __('No') }}
                                                                </p>
                                                            </div>

                                                            <div class="rounded-lg bg-zinc-50 p-4">
                                                                <p class="text-xs font-medium uppercase tracking-wide text-zinc-500">
                                                                    {{ __('Would Allow Return') }}
                                                                </p>
                                                                <p class="mt-2 text-lg font-semibold text-zinc-900">
                                                                    {{ $invitation->review->would_allow_return ? __('Yes') : __('No') }}
                                                                </p>
                                                            </div>

                                                        </div>

                                                    @else

                                                        <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">

                                                            <div class="rounded-lg bg-zinc-50 p-4">
                                                                <p class="text-xs font-medium uppercase tracking-wide text-zinc-500">
                                                                    {{ __('Respect for Property') }}
                                                                </p>
                                                                <p class="mt-2 text-lg font-semibold text-zinc-900">
                                                                    {{ $invitation->review->respect_for_property }}/5
                                                                </p>
                                                            </div>

                                                            <div class="rounded-lg bg-zinc-50 p-4">
                                                                <p class="text-xs font-medium uppercase tracking-wide text-zinc-500">
                                                                    {{ __('Communication & Courtesy') }}
                                                                </p>
                                                                <p class="mt-2 text-lg font-semibold text-zinc-900">
                                                                    {{ $invitation->review->communication_courtesy }}/5
                                                                </p>
                                                            </div>

                                                            <div class="rounded-lg bg-zinc-50 p-4">
                                                                <p class="text-xs font-medium uppercase tracking-wide text-zinc-500">
                                                                    {{ __('Care of Property') }}
                                                                </p>
                                                                <p class="mt-2 text-lg font-semibold text-zinc-900">
                                                                    {{ $invitation->review->care_of_property }}/5
                                                                </p>
                                                            </div>

                                                            <div class="rounded-lg bg-zinc-50 p-4">
                                                                <p class="text-xs font-medium uppercase tracking-wide text-zinc-500">
                                                                    {{ __('Would Allow Return') }}
                                                                </p>
                                                                <p class="mt-2 text-lg font-semibold text-zinc-900">
                                                                    {{ $invitation->review->would_allow_return ? __('Yes') : __('No') }}
                                                                </p>
                                                            </div>

                                                        </div>

                                                    @endif

                                                    @if ($invitation->review->comments)

                                                        <div>

                                                            <p class="text-sm font-medium text-zinc-900">
                                                                {{ __('Property Owner Comments') }}
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
