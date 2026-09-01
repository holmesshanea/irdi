<?php

use App\Models\MemberProfile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Livewire\Component;
use tbQuar\Facades\Quar;

new

class extends Component
{
    public MemberProfile $profile;

    public string $qrCode = '';

    public string $embedCode = '';

    public int $propertyReviewCount = 0;

    public int $wouldAllowReturnCount = 0;

    public ?float $propertyReviewRating = null;

    public function mount(MemberProfile $profile): void
    {
        if ($profile->user->membership_status !== 'active') {
            abort(404);
        }

        if (
            ! $profile->directory_visible
            && $profile->user_id !== auth()->id()
        ) {
            abort(404);
        }

        $this->profile = $profile;

        $this->qrCode = Quar::size(160)
            ->generate(url('/directory/' . $profile->username));

        $this->embedCode = '<iframe src="' .
            route('member-profiles.card', $profile) .
            '" width="100%" height="320" style="max-width:700px;border:0;" loading="lazy"></iframe>';

        $reviewStats = $profile->propertyReviews()
            ->visible()
            ->selectRaw('
                COUNT(*) as review_count,
                SUM(CASE WHEN would_allow_return = 1 THEN 1 ELSE 0 END) as return_count,
                AVG(
                    (
                        respect_for_property
                        + communication_courtesy
                        + care_of_property
                    ) / 3.0
                ) as average_rating
            ')
            ->first();

        $this->propertyReviewCount = (int) $reviewStats->review_count;

        $this->wouldAllowReturnCount = (int) $reviewStats->return_count;

        if ($this->propertyReviewCount >= 3) {
            $this->propertyReviewRating = round(
                (float) $reviewStats->average_rating,
                1
            );
        }
    }

    public function render()
    {
        $location = collect([
            $this->profile->city,
            $this->profile->state_province,
            $this->profile->country,
        ])->filter()->implode(', ');

        $description = 'View ' . $this->profile->profile_name . '\'s IRDI member profile';

        if ($location !== '') {
            $description .= ' in ' . $location;
        }

        $description .= ', including membership information, profile details, and property owner feedback.';

        return $this->view()
            ->layout('components.layouts.public', [
                'description' => $description,
                'canonical' => route('member-profiles.show', [
                    'profile' => $this->profile->username,
                ]),
            ])
            ->title($this->profile->profile_name . ' | IRDI Member');
    }
};
?>

<div>

    <section class="bg-zinc-50">
        <div class="mx-auto max-w-7xl px-6 py-16 lg:px-8 lg:py-20">

            <div class="mx-auto max-w-3xl">

                <div class="text-center">

                    @if ($profile->profile_image)

                        <img
                            src="{{ Storage::disk(config('filesystems.profile_images_disk', 'public'))->url($profile->profile_image) }}"
                            alt="{{ $profile->profile_name }}"
                            class="mx-auto mb-6 h-32 w-32 rounded-full object-cover"
                        >

                    @else

                        <div class="mx-auto mb-6 flex h-32 w-32 items-center justify-center rounded-full bg-zinc-200 text-3xl font-semibold text-irdi-green">
                            {{ strtoupper(substr($profile->profile_name, 0, 1)) }}
                        </div>

                    @endif

                    @if (
                        $profile->user->is_admin
                        || $profile->user->is_moderator
                        || $profile->user->is_charter_member
                    )
                        <div class="flex flex-wrap justify-center gap-2">

                            @if ($profile->user->is_admin)
                                <flux:badge color="red">
                                    Administrator
                                </flux:badge>
                            @elseif ($profile->user->is_moderator)
                                <flux:badge color="blue">
                                    Moderator
                                </flux:badge>
                            @endif

                            @if ($profile->user->is_charter_member)
                                <flux:badge color="amber">
                                    Charter Member
                                </flux:badge>
                            @endif

                        </div>
                    @endif

                    <h1 class="mt-2 text-3xl font-bold tracking-tight text-irdi-green sm:text-4xl">
                        {{ $profile->profile_name }}
                    </h1>

                    <div class="mx-auto mt-4 h-1 w-20 bg-irdi-gold"></div>

                    <p class="mt-4 text-zinc-500">
                        {{ '@' . $profile->username }}
                    </p>

                    @if ($profile->city || $profile->state_province || $profile->country)
                        <p class="mt-3 text-zinc-600">
                            {{ collect([
                                $profile->city,
                                $profile->state_province,
                                $profile->country,
                            ])->filter()->implode(', ') }}
                        </p>
                    @endif

                    @if ($profile->user->member_since)
                        <p class="mt-2 text-sm text-zinc-500">
                            IRDI Member Since {{ $profile->user->member_since->format('F Y') }}
                        </p>
                    @endif

                    @if (
                        auth()->check()
                        && auth()->user()->membership_status === 'active'
                        && auth()->id() !== $profile->user_id
                        && $profile->allow_member_messages
                    )

                        <div class="mt-6 flex justify-center">

                            <flux:button
                                :href="route('messages.create', $profile)"
                                variant="primary"
                                icon="envelope"
                            >
                                Message Member
                            </flux:button>

                        </div>

                    @endif

                </div>

            </div>

        </div>
    </section>

    <section class="bg-white">
        <div class="mx-auto max-w-7xl px-6 py-12 lg:px-8">

            <div class="mx-auto max-w-3xl">

                @if ($profile->bio || $profile->website)

                    <flux:card class="p-6">

                        @if ($profile->bio)

                            <div>

                                <h2 class="text-lg font-semibold text-irdi-green">
                                    Bio
                                </h2>

                                <p class="mt-4 whitespace-pre-line leading-7 text-zinc-700">
                                    {{ $profile->bio }}
                                </p>

                            </div>

                        @endif

                        @if ($profile->website)

                            <div class="@if($profile->bio) mt-6 border-t border-zinc-200 pt-6 @endif">

                                <h2 class="text-lg font-semibold text-irdi-green">
                                    Website
                                </h2>

                                <p class="mt-3">
                                    <a
                                        href="{{ $profile->website }}"
                                        target="_blank"
                                        rel="noopener noreferrer"
                                        class="text-irdi-green underline decoration-irdi-gold underline-offset-4 hover:text-irdi-gold"
                                    >
                                        {{ $profile->website }}
                                    </a>
                                </p>

                            </div>

                        @endif

                    </flux:card>

                @endif

                <flux:card class="@if($profile->bio || $profile->website) mt-6 @endif p-6">

                    <div class="flex flex-col gap-5 sm:flex-row sm:items-start sm:justify-between">

                        <div>

                            <h2 class="text-lg font-semibold text-irdi-green">
                                Property Owner Feedback
                            </h2>

                            <p class="mt-2 text-sm leading-6 text-zinc-600">
                                Feedback is submitted through individual IRDI review invitations and requires email verification.
                                IRDI does not independently verify property ownership.
                            </p>

                        </div>

                        <flux:badge color="green">
                            {{ $propertyReviewCount }}
                            {{ Str::plural('Review', $propertyReviewCount) }}
                        </flux:badge>

                    </div>

                    @if ($propertyReviewCount === 0)

                        <div class="mt-6 rounded-lg bg-zinc-50 p-5">

                            <p class="text-sm text-zinc-600">
                                This member has not received any property owner feedback yet.
                            </p>

                        </div>

                    @else

                        <div class="mt-6 grid gap-4 sm:grid-cols-2">

                            <div class="rounded-lg border border-zinc-200 bg-zinc-50 p-5">

                                <p class="text-sm font-medium text-zinc-500">
                                    Property Owner Rating
                                </p>

                                @if ($propertyReviewRating !== null)

                                    <p class="mt-2 text-3xl font-bold text-irdi-green">
                                        {{ number_format($propertyReviewRating, 1) }}
                                        <span class="text-base font-medium text-zinc-500">
                                            / 5
                                        </span>
                                    </p>

                                    <p class="mt-2 text-sm text-zinc-500">
                                        Based on {{ $propertyReviewCount }}
                                        {{ Str::plural('review', $propertyReviewCount) }}.
                                    </p>

                                @else

                                    <p class="mt-2 text-lg font-semibold text-irdi-green">
                                        {{ $propertyReviewCount }}
                                        {{ Str::plural('Property Owner Review', $propertyReviewCount) }}
                                    </p>

                                    <p class="mt-2 text-sm leading-6 text-zinc-500">
                                        More feedback is needed before a public rating is displayed.
                                    </p>

                                @endif

                            </div>

                            <div class="rounded-lg border border-zinc-200 bg-zinc-50 p-5">

                                <p class="text-sm font-medium text-zinc-500">
                                    Would Allow This Member to Return
                                </p>

                                <p class="mt-2 text-3xl font-bold text-irdi-green">
                                    {{ $wouldAllowReturnCount }}
                                    <span class="text-base font-medium text-zinc-500">
                                        of {{ $propertyReviewCount }}
                                    </span>
                                </p>

                                @if ($propertyReviewCount > 0)

                                    <p class="mt-2 text-sm text-zinc-500">
                                        {{ round(($wouldAllowReturnCount / $propertyReviewCount) * 100) }}%
                                        of reviewers would allow this member to return.
                                    </p>

                                @endif

                            </div>

                        </div>

                    @endif

                </flux:card>

                @if (auth()->check() && $profile->user_id === auth()->id())

                    <div id="member-card" class="mt-10">

                        <div class="mx-auto max-w-2xl overflow-hidden rounded-2xl border border-zinc-200 bg-white shadow-sm">

                            <div class="border-t-4 border-irdi-gold p-6">

                                <div class="flex items-start justify-between gap-6">

                                    <div class="flex min-w-0 items-center gap-4">

                                        @if ($profile->profile_image)

                                            <img
                                                src="{{ Storage::disk(config('filesystems.profile_images_disk', 'public'))->url($profile->profile_image) }}"
                                                alt="{{ $profile->profile_name }}"
                                                class="h-20 w-20 shrink-0 rounded-full object-cover"
                                            >

                                        @else

                                            <div class="flex h-20 w-20 shrink-0 items-center justify-center rounded-full bg-zinc-100 text-2xl font-semibold text-irdi-green">
                                                {{ strtoupper(substr($profile->profile_name, 0, 1)) }}
                                            </div>

                                        @endif

                                        <div class="min-w-0">

                                            @if (
                                                $profile->user->is_admin
                                                || $profile->user->is_moderator
                                                || $profile->user->is_charter_member
                                            )
                                                <div class="flex flex-wrap items-center gap-2">

                                                    @if ($profile->user->is_admin)
                                                        <flux:badge
                                                            size="sm"
                                                            color="red"
                                                        >
                                                            Administrator
                                                        </flux:badge>
                                                    @elseif ($profile->user->is_moderator)
                                                        <flux:badge
                                                            size="sm"
                                                            color="blue"
                                                        >
                                                            Moderator
                                                        </flux:badge>
                                                    @endif

                                                    @if ($profile->user->is_charter_member)
                                                        <flux:badge
                                                            size="sm"
                                                            color="amber"
                                                        >
                                                            Charter Member
                                                        </flux:badge>
                                                    @endif

                                                </div>
                                            @endif

                                            <h2 class="mt-2 text-xl font-bold text-irdi-green">
                                                {{ $profile->profile_name }}
                                            </h2>

                                            <p class="mt-1 text-sm text-zinc-500">
                                                {{ '@' . $profile->username }}
                                            </p>

                                            @if ($profile->user->member_since)
                                                <p class="mt-2 text-sm text-zinc-500">
                                                    Member Since {{ $profile->user->member_since->format('F Y') }}
                                                </p>
                                            @endif

                                        </div>

                                    </div>

                                    <div class="shrink-0 text-center">

                                        {!! $qrCode !!}

                                        <p class="mt-2 text-xs font-semibold uppercase tracking-wide text-irdi-green">
                                            Verify Membership
                                        </p>

                                    </div>

                                </div>

                                <div class="mt-5 border-t border-zinc-200 pt-4">

                                    <p class="text-xs font-medium text-zinc-500">
                                        International Responsible Detectorist Institute
                                    </p>

                                </div>

                            </div>

                        </div>

                        <div class="mx-auto mt-4 max-w-2xl text-center">

                            <p class="text-sm text-zinc-500">
                                Only you can see this card.
                            </p>

                            <div
                                class="mt-4 flex flex-wrap items-center justify-center gap-3"
                                x-data="{ copied: false }"
                            >

                                <textarea
                                    x-ref="embedCode"
                                    class="sr-only"
                                    readonly
                                >{{ $embedCode }}</textarea>

                                <flux:button
                                    :href="route('account.profiles.edit', $profile)"
                                    variant="outline"
                                    icon="pencil-square"
                                >
                                    Edit Profile
                                </flux:button>

                                <button
                                    type="button"
                                    class="inline-flex items-center justify-center rounded-lg bg-irdi-green px-4 py-2 text-sm font-semibold text-white transition hover:opacity-90"
                                    x-on:click="
                                        $refs.embedCode.select();
                                        document.execCommand('copy');
                                        copied = true;
                                        setTimeout(() => {
                                            copied = false;
                                        }, 2000);
                                    "
                                    x-text="copied ? 'Copied!' : 'Copy Embed Code'"
                                >
                                </button>

                            </div>

                        </div>

                    </div>

                @endif

            </div>

        </div>
    </section>

</div>
