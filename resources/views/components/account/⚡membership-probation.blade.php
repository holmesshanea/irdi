
<?php

use App\Models\MembershipEnforcement;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

new class extends Component
{
    public string $response = '';

    public function submitResponse(int $probationId): void
    {
        $probation = $this->activeProbation($probationId);

        if (
            $probation->member_response !== null
            && $probation->member_response !== ''
        ) {
            $this->addError(
                'response',
                __('You have already submitted a response to this probation.')
            );

            return;
        }

        $validated = $this->validate([
            'response' => ['required', 'string', 'min:10', 'max:2000'],
        ]);

        $probation->update([
            'member_response' => $validated['response'],
            'member_responded_at' => now(),
        ]);

        $this->reset('response');

        session()->flash(
            'probation-status',
            __('Your response has been submitted.')
        );
    }

    public function requestReview(int $probationId): void
    {
        $probation = $this->activeProbation($probationId);

        if (
            $probation->eligible_for_review_at !== null
            && now()->lt($probation->eligible_for_review_at)
        ) {
            $this->addError(
                'reviewRequest',
                __('This probation is not yet eligible for formal review.')
            );

            return;
        }

        if ($probation->review_requested_at !== null) {
            $this->addError(
                'reviewRequest',
                __('You have already requested formal review of this probation.')
            );

            return;
        }

        $probation->update([
            'review_requested_at' => now(),
        ]);

        session()->flash(
            'probation-status',
            __('Your request for formal review has been submitted.')
        );
    }

    private function activeProbation(int $probationId): MembershipEnforcement
    {
        return MembershipEnforcement::query()
            ->whereKey($probationId)
            ->where('user_id', Auth::id())
            ->where('action', 'probation')
            ->where('status', 'active')
            ->whereNull('resolved_at')
            ->where(function ($query): void {
                $query
                    ->whereNull('starts_at')
                    ->orWhere('starts_at', '<=', now());
            })
            ->firstOrFail();
    }

    /**
     * @return array<string, mixed>
     */
    public function with(): array
    {
        $probation = MembershipEnforcement::query()
            ->where('user_id', Auth::id())
            ->where('action', 'probation')
            ->where('status', 'active')
            ->whereNull('resolved_at')
            ->where(function ($query): void {
                $query
                    ->whereNull('starts_at')
                    ->orWhere('starts_at', '<=', now());
            })
            ->latest('starts_at')
            ->first();

        return [
            'probation' => $probation,
        ];
    }
};
?>

<div>
    @if ($probation)
        <section class="mb-8 rounded-xl border border-amber-300 bg-amber-50 p-6">
            <div class="flex flex-col gap-6">
                <div>
                    <div class="flex flex-wrap items-center gap-3">
                        <h2 class="text-xl font-semibold text-zinc-900">
                            {{ __('Membership Probation') }}
                        </h2>

                        <span class="rounded-full bg-amber-100 px-3 py-1 text-xs font-semibold uppercase tracking-wide text-amber-800">
                            {{ __('Probationary Standing') }}
                        </span>
                    </div>

                    <p class="mt-3 text-sm leading-6 text-zinc-700">
                        {{ __('Your IRDI membership remains active while you are on probation, but you are not currently considered an IRDI Member in Good Standing.') }}
                    </p>
                </div>

                @if (session('probation-status'))
                    <div class="rounded-lg border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-800">
                        {{ session('probation-status') }}
                    </div>
                @endif

                <div class="grid gap-5 sm:grid-cols-2">
                    <div>
                        <p class="text-sm font-medium text-zinc-500">
                            {{ __('Probation Began') }}
                        </p>

                        <p class="mt-1 text-sm text-zinc-900">
                            {{ $probation->starts_at?->format('F j, Y') ?? __('Not specified') }}
                        </p>
                    </div>

                    <div>
                        <p class="text-sm font-medium text-zinc-500">
                            {{ __('Eligible for Formal Review') }}
                        </p>

                        <p class="mt-1 text-sm text-zinc-900">
                            {{ $probation->eligible_for_review_at?->format('F j, Y') ?? __('Not specified') }}
                        </p>
                    </div>
                </div>

                <div>
                    <p class="text-sm font-medium text-zinc-500">
                        {{ __('Reason') }}
                    </p>

                    <p class="mt-1 whitespace-pre-line text-sm leading-6 text-zinc-900">
                        {{ $probation->reason }}
                    </p>
                </div>

                @if ($probation->restoration_requirements)
                    <div>
                        <p class="text-sm font-medium text-zinc-500">
                            {{ __('Requirements for Restoring Good Standing') }}
                        </p>

                        <p class="mt-1 whitespace-pre-line text-sm leading-6 text-zinc-900">
                            {{ $probation->restoration_requirements }}
                        </p>
                    </div>
                @endif

                <div class="border-t border-amber-200 pt-5">
                    <h3 class="font-semibold text-zinc-900">
                        {{ __('Your Private Response') }}
                    </h3>

                    <p class="mt-2 text-sm leading-6 text-zinc-600">
                        {{ __('You may submit one private response regarding this probation. Your response is retained with the enforcement record and is available to authorized IRDI staff.') }}
                    </p>

                    @if ($probation->member_response)
                        <div class="mt-4 rounded-lg border border-zinc-200 bg-white p-4">
                            <p class="whitespace-pre-line text-sm leading-6 text-zinc-800">
                                {{ $probation->member_response }}
                            </p>

                            @if ($probation->member_responded_at)
                                <p class="mt-3 text-xs text-zinc-500">
                                    {{ __('Submitted :date', [
                                        'date' => $probation->member_responded_at->format('F j, Y'),
                                    ]) }}
                                </p>
                            @endif
                        </div>
                    @else
                        <form
                            wire:submit="submitResponse({{ $probation->id }})"
                            class="mt-4 space-y-4"
                        >
                            <div>
                                <flux:textarea
                                    wire:model="response"
                                    :label="__('Response')"
                                    rows="5"
                                />

                                @error('response')
                                <p class="mt-2 text-sm text-red-600">
                                    {{ $message }}
                                </p>
                                @enderror
                            </div>

                            <flux:button
                                type="submit"
                                variant="primary"
                            >
                                {{ __('Submit Private Response') }}
                            </flux:button>
                        </form>
                    @endif
                </div>

                <div class="border-t border-amber-200 pt-5">
                    <h3 class="font-semibold text-zinc-900">
                        {{ __('Formal Review') }}
                    </h3>

                    @if ($probation->review_requested_at)
                        <div class="mt-3 rounded-lg border border-blue-200 bg-blue-50 p-4">
                            <p class="text-sm font-medium text-blue-900">
                                {{ __('Formal review requested') }}
                            </p>

                            <p class="mt-1 text-sm text-blue-800">
                                {{ __('Your request was submitted on :date. IRDI staff will review the probation and its restoration requirements.', [
                                    'date' => $probation->review_requested_at->format('F j, Y'),
                                ]) }}
                            </p>
                        </div>
                    @elseif (
                        $probation->eligible_for_review_at === null
                        || now()->gte($probation->eligible_for_review_at)
                    )
                        <p class="mt-2 text-sm leading-6 text-zinc-600">
                            {{ __('This probation is eligible for formal review. Requesting review does not automatically end probation. Authorized IRDI staff must formally determine the outcome.') }}
                        </p>

                        @error('reviewRequest')
                        <p class="mt-3 text-sm text-red-600">
                            {{ $message }}
                        </p>
                        @enderror

                        <flux:button
                            type="button"
                            variant="primary"
                            class="mt-4"
                            wire:click="requestReview({{ $probation->id }})"
                        >
                            {{ __('Request Formal Review') }}
                        </flux:button>
                    @else
                        <p class="mt-2 text-sm leading-6 text-zinc-600">
                            {{ __('You may request formal review beginning :date. Probation does not end automatically on that date.', [
                                'date' => $probation->eligible_for_review_at->format('F j, Y'),
                            ]) }}
                        </p>
                    @endif
                </div>

                <p class="border-t border-amber-200 pt-5 text-xs leading-5 text-zinc-500">
                    {{ __('Probation is an active membership accountability status. It remains in effect until IRDI formally restores Good Standing, continues probation, suspends membership, or takes another authorized action.') }}
                </p>
            </div>
        </section>
    @endif
</div>
