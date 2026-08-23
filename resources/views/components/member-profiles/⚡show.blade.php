<?php

use App\Models\MemberProfile;
use Livewire\Attributes\Layout;
use Livewire\Component;
use tbQuar\Facades\Quar;

new
#[Layout('components.layouts.public')]
class extends Component
{
    public MemberProfile $profile;
    public string $qrCode = '';
    public string $embedCode = '';

    public function mount(MemberProfile $profile): void
    {
        if (
            (
                ! $profile->directory_visible
                || $profile->user->membership_status !== 'active'
            )
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
                            src="{{ asset('storage/' . $profile->profile_image) }}"
                            alt="{{ $profile->profile_name }}"
                            class="mx-auto mb-6 h-32 w-32 rounded-full object-cover"
                        >

                    @else

                        <div class="mx-auto mb-6 flex h-32 w-32 items-center justify-center rounded-full bg-zinc-200 text-3xl font-semibold text-irdi-green">
                            {{ strtoupper(substr($profile->profile_name, 0, 1)) }}
                        </div>

                    @endif

                        <div class="flex flex-wrap items-center justify-center gap-2">

                            <flux:badge
                                :color="match ($profile->profile_type) {
            'detectorist' => 'green',
            'organization' => 'blue',
            'vendor' => 'amber',
            default => 'zinc',
        }"
                            >
                                {{ ucfirst($profile->profile_type) }}
                            </flux:badge>

                            @if ($profile->user->membership_status === 'active')
                                <flux:badge color="green" icon="check-circle">
                                    Active IRDI Member
                                </flux:badge>
                            @endif

                        </div>

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

                </div>

            </div>

        </div>
    </section>

    <section class="bg-white">
        <div
            @class([
                'mx-auto max-w-7xl px-6 lg:px-8',
                'py-12' => $profile->bio || $profile->website,
                'pt-4 pb-12' => ! $profile->bio && ! $profile->website,
            ])
        >

            <div class="mx-auto max-w-3xl">

                @if ($profile->bio || $profile->website)

                <flux:card class="p-6">

                    @if ($profile->bio)
                        <div>
                            <h2 class="text-lg font-semibold text-irdi-green">
                                {{ match ($profile->profile_type) {
                                    'vendor' => 'About the Business',
                                    'organization' => 'About the Organization',
                                    'detectorist' => 'Bio',
                                    default => 'About',
                                } }}
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

                    @if (auth()->check() && $profile->user_id === auth()->id())

                        <div id="member-card" class="mt-10">

                            <div class="mx-auto max-w-2xl overflow-hidden rounded-2xl border border-zinc-200 bg-white shadow-sm">

                                <div class="border-t-4 border-irdi-gold p-6">

                                    <div class="flex items-start justify-between gap-6">

                                        <div class="flex min-w-0 items-center gap-4">

                                            @if ($profile->profile_image)

                                                <img
                                                    src="{{ asset('storage/' . $profile->profile_image) }}"
                                                    alt="{{ $profile->profile_name }}"
                                                    class="h-20 w-20 shrink-0 rounded-full object-cover"
                                                >

                                            @else

                                                <div class="flex h-20 w-20 shrink-0 items-center justify-center rounded-full bg-zinc-100 text-2xl font-semibold text-irdi-green">
                                                    {{ strtoupper(substr($profile->profile_name, 0, 1)) }}
                                                </div>

                                            @endif

                                            <div class="min-w-0">

                                                <div class="flex flex-wrap items-center gap-2">

                                                    <flux:badge
                                                        size="sm"
                                                        :color="match ($profile->profile_type) {
            'detectorist' => 'green',
            'organization' => 'blue',
            'vendor' => 'amber',
            default => 'zinc',
        }"
                                                    >
                                                        IRDI {{ ucfirst($profile->profile_type) }}
                                                    </flux:badge>

                                                    @if ($profile->user->membership_status === 'active')
                                                        <flux:badge
                                                            size="sm"
                                                            color="green"
                                                            icon="check-circle"
                                                        >
                                                            Active Member
                                                        </flux:badge>
                                                    @endif

                                                </div>

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
                                        }, 2000); "
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
