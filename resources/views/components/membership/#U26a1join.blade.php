<?php

use Illuminate\Support\Facades\Cache;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Flux\Flux;

new
#[Layout('components.layouts.public')]
class extends Component
{
    public bool $ethicsAgreed = false;

    public bool $bestPracticesAgreed = false;

    public function mount(): void
    {
        $user = auth()->user();

        $this->ethicsAgreed = $user->ethics_agreed_at !== null;
        $this->bestPracticesAgreed = $user->best_practices_agreed_at !== null;

        if (session()->pull('email_verified_successfully')) {
            Flux::toast(
                text: __('Your email address has been verified. Next, review and agree to the IRDI Code of Ethics.'),
                heading: __('Email verified!'),
                duration: 10000,
                variant: 'success',
                action: [
                    'label' => __('Review Code of Ethics'),
                    'href' => route('code-of-ethics'),
                    'navigate' => true,
                ],
            );
        }
    }

    public function activateMembership(): void
    {
        $user = auth()->user();

        if ($user->membership_status === 'active') {
            session()->flash(
                'status',
                __('Your IRDI membership is already active.')
            );

            $this->redirectRoute('account');

            return;
        }

        if (in_array($user->membership_status, ['suspended', 'banned'], true)) {
            session()->flash(
                'status',
                __('This membership cannot be activated from the membership page.')
            );

            $this->redirectRoute('account');

            return;
        }

        $user->refresh();

        if (
            $user->ethics_agreed_at === null
            || $user->best_practices_agreed_at === null
        ) {
            $this->ethicsAgreed = $user->ethics_agreed_at !== null;
            $this->bestPracticesAgreed = $user->best_practices_agreed_at !== null;

            return;
        }

        Cache::lock('irdi-charter-member-assignment', 10)->block(
            5,
            function () use ($user): void {
                $user->refresh();

                if ($user->membership_status === 'active') {
                    return;
                }

                if (in_array($user->membership_status, ['suspended', 'banned'], true)) {
                    return;
                }

                $charterMembersAwarded = \App\Models\User::query()
                    ->where('is_charter_member', true)
                    ->count();

                if (
                    ! $user->is_charter_member
                    && $charterMembersAwarded < 1000
                ) {
                    $user->is_charter_member = true;
                }

                $user->membership_status = 'active';

                if ($user->member_since === null) {
                    $user->member_since = today();
                }

                $user->save();
            }
        );

        $user->refresh();

        if ($user->membership_status !== 'active') {
            session()->flash(
                'status',
                __('Your membership could not be activated.')
            );

            $this->redirectRoute('account');

            return;
        }

        session()->flash(
            'membership_activated_successfully',
            $user->is_charter_member
                ? 'charter'
                : 'member'
        );

        $this->redirectRoute('account');
    }
};
?>

<div>

    <section class="bg-zinc-50">
        <div class="mx-auto max-w-7xl px-6 py-16 lg:px-8 lg:py-20">

            <div class="mx-auto max-w-3xl text-center">

                @if (auth()->user()->membership_status === 'active')
                    <div class="mb-8 rounded-lg bg-green-50 p-4 text-sm font-medium text-green-800">
                        {{ __('Membership already activated!') }}
                    </div>
                @endif

                <h1 class="text-3xl font-bold tracking-tight text-irdi-green sm:text-4xl">
                    {{ __('Become an IRDI Member') }}
                </h1>

                <div class="mx-auto mt-4 h-1 w-20 bg-irdi-gold"></div>

                <p class="mt-6 text-lg leading-8 text-zinc-600">
                    {{ __('Review and agree to the IRDI Code of Ethics and Best Practices before completing your membership.') }}
                </p>

            </div>

        </div>
    </section>

    <section class="bg-white">
        <div class="mx-auto max-w-7xl px-6 py-12 lg:px-8">

            <div class="mx-auto max-w-3xl">

                {{-- Membership Progress --}}
                @php
                    $user = auth()->user();

                    $membershipSteps = [
                        [
                            'label' => __('Account'),
                            'complete' => true,
                        ],
                        [
                            'label' => __('Email'),
                            'complete' => $user->hasVerifiedEmail(),
                        ],
                        [
                            'label' => __('Ethics'),
                            'complete' => $ethicsAgreed,
                        ],
                        [
                            'label' => __('Best Practices'),
                            'complete' => $bestPracticesAgreed,
                        ],
                        [
                            'label' => __('Active'),
                            'complete' => $user->membership_status === 'active',
                        ],
                    ];

                    $membershipStepsCompleted = collect($membershipSteps)
                        ->where('complete', true)
                        ->count();
                @endphp

                <flux:card class="mb-6 p-6">

                    <div class="flex items-center justify-between gap-4">

                        <div>
                            <h2 class="text-xl font-semibold text-irdi-green">
                                {{ __('Membership Progress') }}
                            </h2>

                            <p class="mt-1 text-sm text-zinc-600">
                                {{ __('Complete each step to activate your IRDI membership.') }}
                            </p>
                        </div>

                        <div class="shrink-0 text-sm font-semibold text-blue-600">
                            {{ $membershipStepsCompleted }} {{ __('of :total', ['total' => 5]) }}
                        </div>

                    </div>

                    <div class="mt-6">

                        <flux:progress
                            :value="$membershipStepsCompleted"
                            max="5"
                            color="blue"
                        />

                    </div>

                    <div class="mt-5 grid grid-cols-5 gap-2">

                        @foreach ($membershipSteps as $step)

                            <div class="text-center">

                                @if ($step['complete'])

                                    <flux:icon.check-circle
                                        class="mx-auto size-5 text-green-600"
                                    />

                                    <p class="mt-2 text-xs font-medium text-green-700">
                                        {{ $step['label'] }}
                                    </p>

                                @else

                                    <div class="mx-auto flex size-5 items-center justify-center rounded-full border-2 border-zinc-300">
                                        <div class="size-1.5 rounded-full bg-zinc-300"></div>
                                    </div>

                                    <p class="mt-2 text-xs font-medium text-zinc-500">
                                        {{ $step['label'] }}
                                    </p>

                                @endif

                            </div>

                        @endforeach

                    </div>

                </flux:card>

                {{-- Membership Requirements --}}
                <flux:card class="p-6">

                    <h2 class="text-xl font-semibold text-irdi-green">
                        {{ __('Membership Requirements') }}
                    </h2>

                    <p class="mt-4 text-zinc-600">
                        {{ __('To become an active IRDI Member, review each member document and record your agreement at the bottom of that page.') }}
                    </p>

                </flux:card>

                {{-- Code of Ethics --}}
                <flux:card class="mt-6 p-6">

                    <div class="flex flex-col gap-5 sm:flex-row sm:items-start sm:justify-between">

                        <div>
                            <h2 class="text-xl font-semibold text-irdi-green">
                                {{ __('IRDI Code of Ethics') }}
                            </h2>

                            <p class="mt-3 text-zinc-600">
                                {{ __('Review the ethical principles and responsibilities expected of every IRDI member.') }}
                            </p>
                        </div>

                        @if ($ethicsAgreed)
                            <flux:badge
                                color="green"
                                icon="check-circle"
                            >
                                {{ __('Agreed') }}
                            </flux:badge>
                        @else
                            <flux:badge color="zinc">
                                {{ __('Agreement Required') }}
                            </flux:badge>
                        @endif

                    </div>

                    <div class="mt-6">
                        <flux:button
                            href="{{ route('code-of-ethics') }}"
                            variant="outline"
                            icon="book-open"
                        >
                            {{ $ethicsAgreed ? __('Review Code of Ethics') : __('Read Code of Ethics') }}
                        </flux:button>
                    </div>

                </flux:card>

                {{-- Best Practices --}}
                <flux:card class="mt-6 p-6">

                    <div class="flex flex-col gap-5 sm:flex-row sm:items-start sm:justify-between">

                        <div>
                            <h2 class="text-xl font-semibold text-irdi-green">
                                {{ __('IRDI Best Practices') }}
                            </h2>

                            <p class="mt-3 text-zinc-600">
                                {{ __('Review IRDI\'s practical guidance for responsible, respectful, and lawful metal detecting.') }}
                            </p>
                        </div>

                        @if ($bestPracticesAgreed)
                            <flux:badge
                                color="green"
                                icon="check-circle"
                            >
                                {{ __('Agreed') }}
                            </flux:badge>
                        @else
                            <flux:badge color="zinc">
                                {{ __('Agreement Required') }}
                            </flux:badge>
                        @endif

                    </div>

                    <div class="mt-6">
                        <flux:button
                            href="{{ route('best-practices') }}"
                            variant="outline"
                            icon="book-open"
                        >
                            {{ $bestPracticesAgreed ? __('Review Best Practices') : __('Read Best Practices') }}
                        </flux:button>
                    </div>

                </flux:card>

                {{-- Complete Membership --}}
                <flux:card class="mt-6 p-6">

                    <h2 class="text-xl font-semibold text-irdi-green">
                        {{ __('Complete Your Membership') }}
                    </h2>

                    @if (auth()->user()->membership_status === 'active')

                        <p class="mt-4 text-zinc-600">
                            {{ __('Your IRDI membership has already been activated.') }}
                        </p>

                    @elseif (! $ethicsAgreed || ! $bestPracticesAgreed)

                        <p class="mt-4 text-zinc-600">
                            {{ __('You must agree to both the Code of Ethics and Best Practices before activating your membership.') }}
                        </p>

                    @else

                        <p class="mt-4 text-zinc-600">
                            {{ __('You have completed both membership requirements and are ready to become an IRDI Member.') }}
                        </p>

                    @endif

                    <div class="mt-6">

                        <flux:button
                            variant="primary"
                            wire:click="activateMembership"
                            :disabled="
                                auth()->user()->membership_status === 'active'
                                || ! $ethicsAgreed
                                || ! $bestPracticesAgreed
                            "
                        >
                            {{ __('Become an IRDI Member') }}
                        </flux:button>

                    </div>

                </flux:card>

            </div>

        </div>
    </section>

</div>
