<?php

use App\Models\MembershipEnforcement;
use Livewire\Component;

new class extends Component
{
    public string $petitionStatement = '';

    public function requestReinstatement(int $banId): void
    {
        $user = auth()->user();

        abort_unless($user, 403);

        if ($user->membership_status !== 'banned') {
            abort(404);
        }

        $ban = MembershipEnforcement::query()
            ->whereKey($banId)
            ->where('user_id', $user->id)
            ->where('action', 'banned')
            ->where('status', 'active')
            ->whereNull('resolved_at')
            ->firstOrFail();

        if ($ban->permanently_ineligible) {
            $this->addError(
                'petitionStatement',
                __('This ban is permanently ineligible for reinstatement.')
            );

            return;
        }

        if (
            $ban->reinstatement_eligible_at === null
            || now()->lt($ban->reinstatement_eligible_at)
        ) {
            $this->addError(
                'petitionStatement',
                __('This ban is not currently eligible for a reinstatement petition.')
            );

            return;
        }

        if ($ban->review_requested_at !== null) {
            $this->addError(
                'petitionStatement',
                __('A reinstatement petition has already been submitted for this ban.')
            );

            return;
        }

        $validated = $this->validate([
            'petitionStatement' => ['required', 'string', 'min:20', 'max:2000'],
        ], [
            'petitionStatement.required' => __('Please provide a statement with your reinstatement petition.'),
            'petitionStatement.min' => __('Your petition statement must be at least 20 characters.'),
            'petitionStatement.max' => __('Your petition statement may not be greater than 2,000 characters.'),
        ]);

        $ban->update([
            'member_response' => $validated['petitionStatement'],
            'member_responded_at' => now(),
            'review_requested_at' => now(),
        ]);

        $this->petitionStatement = '';

        session()->flash(
            'ban-status',
            __('Your reinstatement petition has been submitted to IRDI for Super Administrator review.')
        );
    }

    public function with(): array
    {
        $user = auth()->user();

        abort_unless($user, 403);

        $activeBan = null;
        $petitionDenied = false;

        if ($user->membership_status === 'banned') {
            $activeBan = MembershipEnforcement::query()
                ->where('user_id', $user->id)
                ->where('action', 'banned')
                ->where('status', 'active')
                ->whereNull('resolved_at')
                ->latest('id')
                ->first();

            if ($activeBan?->review_requested_at) {
                $petitionDenied = MembershipEnforcement::query()
                    ->where('user_id', $user->id)
                    ->where('action', 'reinstatement_denied')
                    ->where('created_at', '>=', $activeBan->review_requested_at)
                    ->exists();
            }
        }

        return [
            'activeBan' => $activeBan,
            'petitionDenied' => $petitionDenied,
        ];
    }
};
?>

<div>
    @if ($activeBan)
        <section class="mt-10">
            <div class="mb-6">
                <div class="flex flex-wrap items-center gap-3">
                    <h2 class="text-2xl font-bold tracking-tight text-irdi-green">
                        {{ __('Membership Status') }}
                    </h2>

                    <flux:badge color="red">
                        {{ __('Banned') }}
                    </flux:badge>
                </div>

                <div class="mt-2 h-1 w-16 bg-irdi-gold"></div>

                <p class="mt-4 text-sm leading-6 text-zinc-600">
                    {{ __('Your IRDI membership is not active. Your account remains available for status information and, when eligible, a reinstatement petition.') }}
                </p>
            </div>

            @if (session('ban-status'))
                <div
                    role="status"
                    aria-live="polite"
                    class="mb-6 rounded-lg border border-green-200 bg-green-50 p-4 text-sm font-medium text-green-800"
                >
                    {{ session('ban-status') }}
                </div>
            @endif

            <flux:card class="border-red-200 p-6">
                <h3 class="text-lg font-semibold text-zinc-900">
                    {{ __('IRDI Membership Ban') }}
                </h3>

                <p class="mt-2 text-sm text-zinc-500">
                    {{ __('Effective :date', [
                        'date' => ($activeBan->starts_at ?? $activeBan->created_at)->format('F j, Y'),
                    ]) }}
                </p>

                <div class="mt-6">
                    <p class="text-sm font-medium text-zinc-500">
                        {{ __('Reason') }}
                    </p>

                    <p class="mt-2 whitespace-pre-line text-sm leading-6 text-zinc-800">
                        {{ $activeBan->reason }}
                    </p>
                </div>

                <div class="mt-6 rounded-lg border border-zinc-200 bg-zinc-50 p-4">
                    <p class="text-sm font-semibold text-zinc-900">
                        {{ __('Reinstatement Eligibility') }}
                    </p>

                    @if ($activeBan->permanently_ineligible)
                        <p class="mt-2 text-sm leading-6 text-zinc-700">
                            {{ __('This ban is permanently ineligible for reinstatement.') }}
                        </p>
                    @elseif ($activeBan->reinstatement_eligible_at)
                        <p class="mt-2 text-sm leading-6 text-zinc-700">
                            {{ __('A reinstatement petition may be submitted on or after :date. Reinstatement is not automatic and remains a Super Administrator decision.', [
                                'date' => $activeBan->reinstatement_eligible_at->format('F j, Y'),
                            ]) }}
                        </p>
                    @else
                        <p class="mt-2 text-sm leading-6 text-zinc-700">
                            {{ __('No reinstatement eligibility date is currently established.') }}
                        </p>
                    @endif
                </div>

                @if ($activeBan->review_requested_at)
                    <div class="mt-6 rounded-lg border {{ $petitionDenied ? 'border-zinc-300 bg-zinc-100' : 'border-blue-200 bg-blue-50' }} p-4">
                        <p class="text-sm font-semibold {{ $petitionDenied ? 'text-zinc-900' : 'text-blue-900' }}">
                            {{ $petitionDenied ? __('Reinstatement petition denied') : __('Reinstatement petition submitted') }}
                        </p>

                        <p class="mt-1 text-sm {{ $petitionDenied ? 'text-zinc-700' : 'text-blue-800' }}">
                            {{ __('Submitted :date', [
                                'date' => $activeBan->review_requested_at->format('F j, Y'),
                            ]) }}
                        </p>

                        @if ($activeBan->member_response)
                            <p class="mt-3 whitespace-pre-line border-t pt-3 text-sm leading-6 {{ $petitionDenied ? 'border-zinc-300 text-zinc-700' : 'border-blue-200 text-blue-800' }}">
                                {{ $activeBan->member_response }}
                            </p>
                        @endif
                    </div>
                @elseif (
                    ! $activeBan->permanently_ineligible
                    && $activeBan->reinstatement_eligible_at
                    && now()->gte($activeBan->reinstatement_eligible_at)
                )
                    <form
                        wire:submit="requestReinstatement({{ $activeBan->id }})"
                        class="mt-6 border-t border-zinc-200 pt-6"
                    >
                        <flux:textarea
                            wire:model="petitionStatement"
                            label="{{ __('Reinstatement Petition Statement') }}"
                            description="{{ __('Explain why you are requesting reinstatement and what has changed since the ban. A petition does not guarantee reinstatement.') }}"
                            rows="6"
                            maxlength="2000"
                        />

                        <flux:error name="petitionStatement" />

                        <div class="mt-4 flex justify-end">
                            <flux:button
                                type="submit"
                                variant="primary"
                                wire:confirm="{{ __('Submit this reinstatement petition for Super Administrator review?') }}"
                            >
                                {{ __('Submit Reinstatement Petition') }}
                            </flux:button>
                        </div>
                    </form>
                @endif
            </flux:card>
        </section>
    @endif
</div>

