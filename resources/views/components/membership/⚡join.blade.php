<?php

use Illuminate\Support\Facades\Cache;
use Livewire\Attributes\Layout;
use Livewire\Component;

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
    }

    public function activateMembership(): void
    {
        $user = auth()->user();

        if ($user->membership_status === 'active') {
            session()->flash(
                'status',
                'Your IRDI membership is already active.'
            );

            $this->redirectRoute('account');

            return;
        }

        if (in_array($user->membership_status, ['suspended', 'banned'], true)) {
            session()->flash(
                'status',
                'This membership cannot be activated from the membership page.'
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
                'Your membership could not be activated.'
            );

            $this->redirectRoute('account');

            return;
        }

        session()->flash(
            'status',
            $user->is_charter_member
                ? 'Your IRDI membership is now active. Welcome, Charter Member!'
                : 'Your IRDI membership is now active.'
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
                        Membership already activated!
                    </div>
                @endif

                <h1 class="text-3xl font-bold tracking-tight text-irdi-green sm:text-4xl">
                    Become an IRDI Member
                </h1>

                <div class="mx-auto mt-4 h-1 w-20 bg-irdi-gold"></div>

                <p class="mt-6 text-lg leading-8 text-zinc-600">
                    Review and agree to the IRDI Code of Ethics and Best Practices before completing your membership.
                </p>

            </div>

        </div>
    </section>

    <section class="bg-white">
        <div class="mx-auto max-w-7xl px-6 py-12 lg:px-8">

            <div class="mx-auto max-w-3xl">

                {{-- Membership Requirements --}}
                <flux:card class="p-6">

                    <h2 class="text-xl font-semibold text-irdi-green">
                        Membership Requirements
                    </h2>

                    <p class="mt-4 text-zinc-600">
                        To become an active IRDI Member, review each member document and record your agreement at the bottom of that page.
                    </p>

                </flux:card>

                {{-- Code of Ethics --}}
                <flux:card class="mt-6 p-6">

                    <div class="flex flex-col gap-5 sm:flex-row sm:items-start sm:justify-between">

                        <div>
                            <h2 class="text-xl font-semibold text-irdi-green">
                                IRDI Code of Ethics
                            </h2>

                            <p class="mt-3 text-zinc-600">
                                Review the ethical principles and responsibilities expected of every IRDI member.
                            </p>
                        </div>

                        @if ($ethicsAgreed)
                            <flux:badge
                                color="green"
                                icon="check-circle"
                            >
                                Agreed
                            </flux:badge>
                        @else
                            <flux:badge color="zinc">
                                Agreement Required
                            </flux:badge>
                        @endif

                    </div>

                    <div class="mt-6">
                        <flux:button
                            href="{{ route('code-of-ethics') }}"
                            variant="outline"
                            icon="book-open"
                        >
                            {{ $ethicsAgreed ? 'Review Code of Ethics' : 'Read Code of Ethics' }}
                        </flux:button>
                    </div>

                </flux:card>

                {{-- Best Practices --}}
                <flux:card class="mt-6 p-6">

                    <div class="flex flex-col gap-5 sm:flex-row sm:items-start sm:justify-between">

                        <div>
                            <h2 class="text-xl font-semibold text-irdi-green">
                                IRDI Best Practices
                            </h2>

                            <p class="mt-3 text-zinc-600">
                                Review IRDI's practical guidance for responsible, respectful, and lawful metal detecting.
                            </p>
                        </div>

                        @if ($bestPracticesAgreed)
                            <flux:badge
                                color="green"
                                icon="check-circle"
                            >
                                Agreed
                            </flux:badge>
                        @else
                            <flux:badge color="zinc">
                                Agreement Required
                            </flux:badge>
                        @endif

                    </div>

                    <div class="mt-6">
                        <flux:button
                            href="{{ route('best-practices') }}"
                            variant="outline"
                            icon="book-open"
                        >
                            {{ $bestPracticesAgreed ? 'Review Best Practices' : 'Read Best Practices' }}
                        </flux:button>
                    </div>

                </flux:card>

                {{-- Complete Membership --}}
                <flux:card class="mt-6 p-6">

                    <h2 class="text-xl font-semibold text-irdi-green">
                        Complete Your Membership
                    </h2>

                    @if (auth()->user()->membership_status === 'active')

                        <p class="mt-4 text-zinc-600">
                            Your IRDI membership has already been activated.
                        </p>

                    @elseif (! $ethicsAgreed || ! $bestPracticesAgreed)

                        <p class="mt-4 text-zinc-600">
                            You must agree to both the Code of Ethics and Best Practices before activating your membership.
                        </p>

                    @else

                        <p class="mt-4 text-zinc-600">
                            You have completed both membership requirements and are ready to become an IRDI Member.
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
                            Become an IRDI Member
                        </flux:button>

                    </div>

                </flux:card>

            </div>

        </div>
    </section>

</div>
