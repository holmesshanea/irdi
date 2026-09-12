<?php

use App\Models\MemberProfile;
use Illuminate\Support\Facades\Storage;
use Livewire\Component;
use Symfony\Component\Intl\Countries;
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

        $this->qrCode = Quar::size(116)
            ->generate(route('member-profiles.show', $profile));

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

        $description = __('View :name\'s IRDI member profile', ['name' => $this->profile->profile_name]);

        if ($location !== '') {
            $description .= __(' in :location', ['location' => $location]);
        }

        $description .= __(', including membership information, profile details, and property owner feedback.');

        return $this->view()
            ->layout('components.layouts.public', [
                'description' => $description,
                'canonical' => route('member-profiles.show', [
                    'profile' => $this->profile->username,
                ]),
            ])
            ->title(__('Profile title: :name | IRDI Member', ['name' => $this->profile->profile_name]));
    }
};
?>

<div>

    <section class="bg-zinc-50">
        <div class="mx-auto max-w-7xl px-6 py-12 lg:px-8 lg:py-16">

            <div class="mx-auto max-w-4xl">

                <div class="rounded-2xl border border-irdi-gold/70 bg-white px-6 py-12 text-center shadow-sm sm:px-10">

                    @if ($profile->profile_image)

                        <img
                            src="{{ Storage::disk(config('filesystems.profile_images_disk', 'public'))->url($profile->profile_image) }}"
                            alt="{{ $profile->profile_name }}"
                            class="mx-auto h-32 w-32 rounded-full object-cover ring-4 ring-white shadow-sm"
                        >

                    @else

                        <div class="mx-auto flex h-32 w-32 items-center justify-center rounded-full bg-zinc-200 text-3xl font-semibold text-irdi-green ring-4 ring-white shadow-sm">
                            {{ strtoupper(substr($profile->profile_name, 0, 1)) }}
                        </div>

                    @endif

                    @if (
                        $profile->user->is_super_admin
                        || $profile->user->is_admin
                        || $profile->user->is_moderator
                        || $profile->user->is_charter_member
                    )
                        <div class="mt-6 flex flex-wrap justify-center gap-2">

                            @if ($profile->user->is_super_admin)
                                <flux:badge color="red">
                                    {{ __('Super Administrator') }}
                                </flux:badge>
                            @elseif ($profile->user->is_admin)
                                <flux:badge color="red">
                                    {{ __('Administrator') }}
                                </flux:badge>
                            @elseif ($profile->user->is_moderator)
                                <flux:badge color="blue">
                                    {{ __('Moderator') }}
                                </flux:badge>
                            @endif

                            @if ($profile->user->is_charter_member)
                                <flux:badge color="amber">
                                    {{ __('Charter Member') }}
                                </flux:badge>
                            @endif

                        </div>
                    @endif

                    <h1 class="mt-4 text-3xl font-bold tracking-tight text-irdi-green sm:text-4xl">
                        {{ $profile->profile_name }}
                    </h1>

                    <p class="mt-2 text-zinc-500">
                        {{ '@' . $profile->username }}
                    </p>

                    <div class="mt-5 flex justify-center">
                        <div class="inline-flex items-center gap-2 rounded-full border border-green-200 bg-green-50 px-3 py-1.5 text-sm font-semibold text-green-800">
                            <span class="h-2.5 w-2.5 rounded-full bg-green-500"></span>
                            {{ __('Active IRDI Member') }}
                        </div>
                    </div>

                    @if ($profile->user->member_since)
                        <p class="mt-3 text-sm text-zinc-500">
                            {{ __('IRDI Member Since :date', ['date' => $profile->user->member_since->translatedFormat('F Y')]) }}
                        </p>
                    @endif

                    @if ($profile->city || $profile->state_province || $profile->country)
                        <div class="mt-3 flex items-center justify-center gap-2 text-zinc-600">

                            @if ($profile->country_code)
                                <flux:flag
                                    country="{{ $profile->country_code }}"
                                    size="xs"
                                    class="shrink-0"
                                />
                            @endif

                            <span>
                                {{ collect([
                                    $profile->city,
                                    $profile->state_province,
                                    ($profile->country_code ? Countries::getName($profile->country_code, app()->getLocale()) : $profile->country),
                                ])->filter()->implode(', ') }}
                            </span>

                        </div>
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
                                {{ __('Message Member') }}
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

                <flux:card class="p-6">
                    <div class="flex items-start gap-3">
                        <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-irdi-gold/10 text-irdi-green">
                            <flux:icon.user-circle class="size-5" />
                        </div>

                        <div class="min-w-0 flex-1">
                            <h2 class="text-lg font-semibold text-irdi-green">
                                {{ __('About') }}
                            </h2>

                            @if ($profile->bio)
                                <p class="mt-3 whitespace-pre-line leading-7 text-zinc-700">
                                    {{ $profile->bio }}
                                </p>
                            @else
                                <p class="mt-3 text-sm text-zinc-500">
                                    {{ __('This member has not added an About section yet.') }}
                                </p>
                            @endif

                            @if ($profile->website)
                                <div class="mt-6 border-t border-zinc-200 pt-5">
                                    <p class="text-sm font-medium text-zinc-500">
                                        {{ __('Website') }}
                                    </p>

                                    <a
                                        href="{{ $profile->website }}"
                                        target="_blank"
                                        rel="noopener noreferrer"
                                        class="mt-2 inline-flex items-center gap-2 break-all text-irdi-green underline decoration-irdi-gold underline-offset-4 hover:text-irdi-gold"
                                    >
                                        <flux:icon.globe-alt class="size-4 shrink-0" />
                                        <span>{{ $profile->website }}</span>
                                    </a>
                                </div>
                            @endif
                        </div>
                    </div>
                </flux:card>

                <flux:card class="mt-6 p-6">
                    <div class="flex flex-col gap-5 sm:flex-row sm:items-start sm:justify-between">
                        <div class="flex items-start gap-3">
                            <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-irdi-gold/10 text-irdi-green">
                                <flux:icon.shield-check class="size-5" />
                            </div>

                            <div>
                                <h2 class="text-lg font-semibold text-irdi-green">
                                    {{ __('Property Owner Feedback') }}
                                </h2>

                                <p class="mt-2 max-w-2xl text-sm leading-6 text-zinc-600">
                                    {{ __('Verified feedback from property owners who received an individual IRDI review invitation from this member. Reviewers must verify their email address before submitting feedback.') }}
                                </p>
                            </div>
                        </div>

                        <flux:badge color="green" class="shrink-0">
                            {{ $propertyReviewCount }}
                            {{ $propertyReviewCount === 1 ? __('Review') : __('Reviews') }}
                        </flux:badge>
                    </div>

                    @if ($propertyReviewCount === 0)
                        <div class="mt-6 rounded-xl border border-zinc-200 bg-zinc-50 p-5">
                            <div class="flex items-start gap-3">
                                <flux:icon.chat-bubble-left-right class="mt-0.5 size-5 shrink-0 text-zinc-400" />

                                <div>
                                    <p class="font-medium text-zinc-700">
                                        {{ __('No property owner feedback yet') }}
                                    </p>

                                    <p class="mt-1 text-sm leading-6 text-zinc-500">
                                        {{ __('This member has not received any visible property owner reviews yet.') }}
                                    </p>
                                </div>
                            </div>
                        </div>
                    @else
                        <div class="mt-6 grid gap-4 sm:grid-cols-2">

                            <div class="rounded-xl border border-zinc-200 bg-zinc-50 p-5">
                                <div class="flex items-center gap-2 text-sm font-medium text-zinc-500">
                                    <flux:icon.star class="size-4" />
                                    <span>{{ __('Property Owner Rating') }}</span>
                                </div>

                                @if ($propertyReviewRating !== null)
                                    <p class="mt-3 text-3xl font-bold text-irdi-green">
                                        {{ number_format($propertyReviewRating, 1) }}
                                        <span class="text-base font-medium text-zinc-500">
                                            / 5
                                        </span>
                                    </p>

                                    <p class="mt-2 text-sm text-zinc-500">
                                        {{ $propertyReviewCount === 1
                                            ? __('Based on :count review.', ['count' => $propertyReviewCount])
                                            : __('Based on :count reviews.', ['count' => $propertyReviewCount]) }}
                                    </p>
                                @else
                                    <p class="mt-3 text-lg font-semibold text-irdi-green">
                                        {{ $propertyReviewCount }}
                                        {{ $propertyReviewCount === 1 ? __('Property Owner Review') : __('Property Owner Reviews') }}
                                    </p>

                                    <p class="mt-2 text-sm leading-6 text-zinc-500">
                                        {{ __('A public rating is displayed after at least three visible reviews.') }}
                                    </p>
                                @endif
                            </div>

                            <div class="rounded-xl border border-zinc-200 bg-zinc-50 p-5">
                                <div class="flex items-center gap-2 text-sm font-medium text-zinc-500">
                                    <flux:icon.arrow-uturn-left class="size-4" />
                                    <span>{{ __('Would Allow This Member to Return') }}</span>
                                </div>

                                <p class="mt-3 text-3xl font-bold text-irdi-green">
                                    {{ __(':count of :total', ['count' => $wouldAllowReturnCount, 'total' => $propertyReviewCount]) }}
                                </p>

                                <p class="mt-2 text-sm text-zinc-500">
                                    {{ __(':percent% of reviewers would allow this member to return.', ['percent' => round(($wouldAllowReturnCount / $propertyReviewCount) * 100)]) }}
                                </p>
                            </div>

                        </div>
                    @endif

                    <p class="mt-5 text-xs leading-5 text-zinc-500">
                        {{ __('IRDI verifies the reviewer’s email address but does not independently verify property ownership or the contents of a review.') }}
                    </p>
                </flux:card>

                @if (auth()->check() && $profile->user_id === auth()->id())

                    <div id="member-card" class="mt-10">

                        @include('partials.member-profiles.membership-card', [
                            'profile' => $profile,
                            'qrCode' => $qrCode,
                        ])

                        <div class="mx-auto mt-4 max-w-xl text-center">

                            <p class="text-sm text-zinc-500">
                                {{ __('Only you can see this card.') }}
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
                                    {{ __('Edit Profile') }}
                                </flux:button>

                                <flux:button
                                    :href="route('member-profiles.card', $profile)"
                                    variant="outline"
                                    icon="identification"
                                    target="_blank"
                                >
                                    {{ __('View Standalone Card') }}
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
                                    x-text="copied ? @js(__('Copied!')) : @js(__('Copy Embed Code'))"
                                >
                                </button>

                            </div>

                        </div>

                    </div>

                @endif

            </div>

        </div>
    </section>
