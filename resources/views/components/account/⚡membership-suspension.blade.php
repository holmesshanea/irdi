<?php

use App\Models\MembershipEnforcement;
use Illuminate\Database\Eloquent\Collection;
use Livewire\Component;

new class extends Component
{
    /**
     * @var array<int, string>
     */
    public array $responses = [];

    public function submitResponse(int $suspensionId): void
    {
        $user = auth()->user();

        abort_unless($user, 403);

        $suspension = $this->activeSuspensionQuery($user->id)
            ->whereKey($suspensionId)
            ->firstOrFail();

        if ($suspension->member_response !== null) {
            $this->addError(
                "responses.$suspensionId",
                __('You have already submitted a response to this suspension.')
            );

            return;
        }

        $validated = $this->validate([
            "responses.$suspensionId" => [
                'required',
                'string',
                'min:10',
                'max:2000',
            ],
        ], [
            "responses.$suspensionId.required" => __('Please enter your response.'),
            "responses.$suspensionId.min" => __('Your response must be at least 10 characters.'),
            "responses.$suspensionId.max" => __('Your response may not be greater than 2,000 characters.'),
        ]);

        $suspension->update([
            'member_response' => $validated['responses'][$suspensionId],
            'member_responded_at' => now(),
        ]);

        unset($this->responses[$suspensionId]);

        session()->flash(
            'suspension-status',
            __('Your response has been submitted to IRDI.')
        );
    }

    public function requestReview(int $suspensionId): void
    {
        $user = auth()->user();

        abort_unless($user, 403);

        $suspension = $this->activeSuspensionQuery($user->id)
            ->whereKey($suspensionId)
            ->firstOrFail();

        if ($suspension->review_requested_at !== null) {
            $this->addError(
                "review.$suspensionId",
                __('You have already requested a formal review of this suspension.')
            );

            return;
        }

        if (
            $suspension->eligible_for_review_at === null
            || now()->lt($suspension->eligible_for_review_at)
        ) {
            $this->addError(
                "review.$suspensionId",
                __('This suspension is not currently eligible for formal review.')
            );

            return;
        }

        $suspension->update([
            'review_requested_at' => now(),
        ]);

        session()->flash(
            'suspension-status',
            __('Your formal review request has been submitted to IRDI.')
        );
    }

    /**
     * @return \Illuminate\Database\Eloquent\Builder<MembershipEnforcement>
     */
    private function activeSuspensionQuery(int $userId)
    {
        return MembershipEnforcement::query()
            ->where('user_id', $userId)
            ->where('action', 'suspended')
            ->where('status', 'active')
            ->whereNull('resolved_at')
            ->where(function ($query): void {
                $query
                    ->whereNull('starts_at')
                    ->orWhere('starts_at', '<=', now());
            });
    }

    /**
     * @return array{activeSuspensions: Collection<int, MembershipEnforcement>}
     */
    public function with(): array
    {
        $user = auth()->user();

        abort_unless($user, 403);

        if ($user->membership_status !== 'suspended') {
            return [
                'activeSuspensions' => new Collection,
            ];
        }

        return [
            'activeSuspensions' => $this->activeSuspensionQuery($user->id)
                ->with('admin')
                ->latest('starts_at')
                ->latest('id')
                ->get(),
        ];
    }
};
?>

<div>
    @if ($activeSuspensions->isNotEmpty())
        <section class="mt-10">
            <div class="mb-6">
                <div class="flex flex-wrap items-center gap-3">
                    <h2 class="text-2xl font-bold tracking-tight text-irdi-green">
                        {{ __('Membership Suspension') }}
                    </h2>

                    <flux:badge color="red">
                        {{ __('Suspended') }}
                    </flux:badge>
                </div>

                <div class="mt-2 h-1 w-16 bg-irdi-gold"></div>

                <p class="mt-4 text-sm leading-6 text-zinc-600">
                    {{ __('Your IRDI membership is suspended. Your account remains available so you can review the suspension, provide a private response, and request formal review when the suspension becomes eligible.') }}
                </p>
            </div>

            @if (session('suspension-status'))
                <div
                    role="status"
                    aria-live="polite"
                    class="mb-6 rounded-lg border border-green-200 bg-green-50 p-4 text-sm font-medium text-green-800"
                >
                    {{ session('suspension-status') }}
                </div>
            @endif

            <div class="space-y-6">
                @foreach ($activeSuspensions as $suspension)
                    <flux:card class="border-red-200 p-6">
                        <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
                            <div>
                                <div class="flex flex-wrap items-center gap-2">
                                    <h3 class="text-lg font-semibold text-zinc-900">
                                        {{ __('Active Membership Suspension') }}
                                    </h3>

                                    <flux:badge color="red">
                                        {{ __('Suspended') }}
                                    </flux:badge>
                                </div>

                                <p class="mt-2 text-sm text-zinc-500">
                                    {{ __('Suspended since :date', [
                                        'date' => ($suspension->starts_at ?? $suspension->created_at)->format('F j, Y'),
                                    ]) }}
                                </p>
                            </div>
                        </div>

                        <div class="mt-6">
                            <p class="text-sm font-medium text-zinc-500">
                                {{ __('Reason') }}
                            </p>

                            <p class="mt-2 whitespace-pre-line text-sm leading-6 text-zinc-800">
                                {{ $suspension->reason }}
                            </p>
                        </div>

                        <div class="mt-6 rounded-lg border border-zinc-200 bg-zinc-50 p-4">
                            <p class="text-sm font-semibold text-zinc-900">
                                {{ __('Formal Review') }}
                            </p>

                            @if ($suspension->eligible_for_review_at)
                                <p class="mt-2 text-sm leading-6 text-zinc-700">
                                    {{ __('Eligible for formal review on :date.', [
                                        'date' => $suspension->eligible_for_review_at->format('F j, Y'),
                                    ]) }}
                                </p>
                            @else
                                <p class="mt-2 text-sm leading-6 text-zinc-700">
                                    {{ __('This suspension is indefinite and does not currently have a formal review date. IRDI staff must establish a review date before a review can be requested.') }}
                                </p>
                            @endif
                        </div>

                        @if ($suspension->member_response !== null)
                            <div class="mt-6 border-t border-zinc-200 pt-6">
                                <div class="flex flex-wrap items-center justify-between gap-3">
                                    <p class="text-sm font-semibold text-zinc-900">
                                        {{ __('Your Response') }}
                                    </p>

                                    @if ($suspension->member_responded_at)
                                        <p class="text-xs text-zinc-500">
                                            {{ __('Submitted :date', [
                                                'date' => $suspension->member_responded_at->format('F j, Y'),
                                            ]) }}
                                        </p>
                                    @endif
                                </div>

                                <p class="mt-3 whitespace-pre-line text-sm leading-6 text-zinc-700">
                                    {{ $suspension->member_response }}
                                </p>

                                <p class="mt-3 text-xs leading-5 text-zinc-500">
                                    {{ __('Your response is part of the private IRDI enforcement record and is not displayed publicly.') }}
                                </p>
                            </div>
                        @else
                            <form
                                wire:submit="submitResponse({{ $suspension->id }})"
                                class="mt-6 border-t border-zinc-200 pt-6"
                            >
                                <flux:textarea
                                    wire:model="responses.{{ $suspension->id }}"
                                    label="{{ __('Your Response') }}"
                                    description="{{ __('You may provide one private response for the IRDI enforcement record. Your response will not appear publicly.') }}"
                                    rows="5"
                                    maxlength="2000"
                                />

                                <flux:error name="responses.{{ $suspension->id }}" />

                                <div class="mt-4 flex justify-end">
                                    <flux:button
                                        type="submit"
                                        variant="primary"
                                    >
                                        {{ __('Submit Response') }}
                                    </flux:button>
                                </div>
                            </form>
                        @endif

                        <div class="mt-6 border-t border-zinc-200 pt-6">
                            @if ($suspension->review_requested_at)
                                <div class="rounded-lg border border-blue-200 bg-blue-50 p-4 text-sm text-blue-900">
                                    <p class="font-semibold">
                                        {{ __('Formal review requested') }}
                                    </p>

                                    <p class="mt-1 text-blue-800">
                                        {{ __('Requested :date', [
                                            'date' => $suspension->review_requested_at->format('F j, Y'),
                                        ]) }}
                                    </p>
                                </div>
                            @elseif (
                                $suspension->eligible_for_review_at
                                && now()->gte($suspension->eligible_for_review_at)
                            )
                                <div class="rounded-lg border border-green-200 bg-green-50 p-4">
                                    <p class="text-sm font-semibold text-green-900">
                                        {{ __('This suspension is eligible for formal review.') }}
                                    </p>

                                    <p class="mt-2 text-sm leading-6 text-green-800">
                                        {{ __('Requesting review asks IRDI staff to formally consider the current suspension. It does not automatically restore membership or end the suspension.') }}
                                    </p>

                                    <flux:error name="review.{{ $suspension->id }}" />

                                    <div class="mt-4 flex justify-end">
                                        <flux:button
                                            type="button"
                                            variant="primary"
                                            wire:click="requestReview({{ $suspension->id }})"
                                            wire:confirm="{{ __('Request a formal review of this suspension?') }}"
                                        >
                                            {{ __('Request Formal Review') }}
                                        </flux:button>
                                    </div>
                                </div>
                            @elseif ($suspension->eligible_for_review_at)
                                <div class="rounded-lg border border-zinc-200 bg-zinc-50 p-4 text-sm text-zinc-700">
                                    {{ __('A formal review may be requested on or after :date.', [
                                        'date' => $suspension->eligible_for_review_at->format('F j, Y'),
                                    ]) }}
                                </div>
                            @endif
                        </div>
                    </flux:card>
                @endforeach
            </div>
        </section>
    @endif
</div>
