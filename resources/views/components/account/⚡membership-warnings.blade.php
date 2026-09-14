
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

    public function submitResponse(int $warningId): void
    {
        $user = auth()->user();

        abort_unless($user, 403);

        $warning = MembershipEnforcement::query()
            ->whereKey($warningId)
            ->where('user_id', $user->id)
            ->where('action', 'warning')
            ->where('status', 'active')
            ->whereNull('resolved_at')
            ->where(function ($query): void {
                $query
                    ->whereNull('starts_at')
                    ->orWhere('starts_at', '<=', now());
            })
            ->firstOrFail();

        if ($warning->member_response !== null) {
            $this->addError(
                "responses.$warningId",
                __('You have already submitted a response to this warning.')
            );

            return;
        }

        $validated = $this->validate([
            "responses.$warningId" => [
                'required',
                'string',
                'min:10',
                'max:2000',
            ],
        ], [
            "responses.$warningId.required" => __('Please enter your response.'),
            "responses.$warningId.min" => __('Your response must be at least 10 characters.'),
            "responses.$warningId.max" => __('Your response may not be greater than 2,000 characters.'),
        ]);

        $warning->update([
            'member_response' => $validated['responses'][$warningId],
            'member_responded_at' => now(),
        ]);

        unset($this->responses[$warningId]);

        session()->flash(
            'warning-status',
            __('Your response has been submitted to IRDI.')
        );
    }

    /**
     * @return array{activeWarnings: Collection<int, MembershipEnforcement>}
     */
    public function with(): array
    {
        $user = auth()->user();

        abort_unless($user, 403);

        return [
            'activeWarnings' => MembershipEnforcement::query()
                ->with('admin')
                ->where('user_id', $user->id)
                ->where('action', 'warning')
                ->where('status', 'active')
                ->whereNull('resolved_at')
                ->where(function ($query): void {
                    $query
                        ->whereNull('starts_at')
                        ->orWhere('starts_at', '<=', now());
                })
                ->latest('starts_at')
                ->latest('id')
                ->get(),
        ];
    }
};
?>

<div>
    @if ($activeWarnings->isNotEmpty())
        <section class="mt-10">
            <div class="mb-6">
                <div class="flex flex-wrap items-center gap-3">
                    <h2 class="text-2xl font-bold tracking-tight text-irdi-green">
                        {{ __('Formal Membership Warning') }}
                    </h2>

                    <flux:badge color="amber">
                        {{ __('Private Account Notice') }}
                    </flux:badge>
                </div>

                <div class="mt-2 h-1 w-16 bg-irdi-gold"></div>

                <p class="mt-4 text-sm leading-6 text-zinc-600">
                    {{ __('A formal warning is a corrective or educational membership action. It does not suspend your IRDI membership, remove your Member in Good Standing status, or appear on your public member profile.') }}
                </p>
            </div>

            @if (session('warning-status'))
                <div
                    role="status"
                    aria-live="polite"
                    class="mb-6 rounded-lg border border-green-200 bg-green-50 p-4 text-sm font-medium text-green-800"
                >
                    {{ session('warning-status') }}
                </div>
            @endif

            <div class="space-y-6">
                @foreach ($activeWarnings as $warning)
                    <flux:card class="border-amber-200 p-6">
                        <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
                            <div>
                                <div class="flex flex-wrap items-center gap-2">
                                    <h3 class="text-lg font-semibold text-zinc-900">
                                        {{ __('Formal Warning') }}
                                    </h3>

                                    <flux:badge color="amber">
                                        {{ __('Active') }}
                                    </flux:badge>
                                </div>

                                <p class="mt-2 text-sm text-zinc-500">
                                    {{ __('Issued :date', [
                                        'date' => ($warning->starts_at ?? $warning->created_at)->format('F j, Y'),
                                    ]) }}
                                </p>
                            </div>
                        </div>

                        <div class="mt-6">
                            <p class="text-sm font-medium text-zinc-500">
                                {{ __('Reason') }}
                            </p>

                            <p class="mt-2 whitespace-pre-line text-sm leading-6 text-zinc-800">
                                {{ $warning->reason }}
                            </p>
                        </div>

                        @if (filled($warning->restoration_requirements))
                            <div class="mt-6 rounded-lg border border-amber-200 bg-amber-50 p-4">
                                <p class="text-sm font-semibold text-amber-900">
                                    {{ __('Corrective Action or Training Requested') }}
                                </p>

                                <p class="mt-2 whitespace-pre-line text-sm leading-6 text-amber-800">
                                    {{ $warning->restoration_requirements }}
                                </p>
                            </div>
                        @endif

                        @if ($warning->member_response !== null)
                            <div class="mt-6 border-t border-zinc-200 pt-6">
                                <div class="flex flex-wrap items-center justify-between gap-3">
                                    <p class="text-sm font-semibold text-zinc-900">
                                        {{ __('Your Response') }}
                                    </p>

                                    @if ($warning->member_responded_at)
                                        <p class="text-xs text-zinc-500">
                                            {{ __('Submitted :date', [
                                                'date' => $warning->member_responded_at->format('F j, Y'),
                                            ]) }}
                                        </p>
                                    @endif
                                </div>

                                <p class="mt-3 whitespace-pre-line text-sm leading-6 text-zinc-700">
                                    {{ $warning->member_response }}
                                </p>

                                <p class="mt-3 text-xs leading-5 text-zinc-500">
                                    {{ __('Your response is part of the private IRDI enforcement record and is not displayed publicly.') }}
                                </p>
                            </div>
                        @else
                            <form
                                wire:submit="submitResponse({{ $warning->id }})"
                                class="mt-6 border-t border-zinc-200 pt-6"
                            >
                                <flux:textarea
                                    wire:model="responses.{{ $warning->id }}"
                                    label="{{ __('Your Response') }}"
                                    description="{{ __('You may provide a response for the IRDI enforcement record. Your response is private and will not appear on your public profile.') }}"
                                    rows="5"
                                    maxlength="2000"
                                />

                                <flux:error name="responses.{{ $warning->id }}" />

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
                    </flux:card>
                @endforeach
            </div>
        </section>
    @endif
</div>
