<?php

use Flux\Flux;
use Livewire\Component;

new class extends Component
{
    public bool $agreed = false;

    public function mount(): void
    {
        $this->agreed = auth()->user()->best_practices_agreed_at !== null;

        if (! $this->agreed && auth()->user()->membership_status !== 'active') {
            Flux::toast(
                text: 'Please read the entire IRDI Best Practices. When finished, check "I have read and agree to follow the IRDI Best Practices." at the bottom of this page.',
                heading: 'Review the Best Practices',
                duration: 10000,
                variant: 'warning',
            );
        }
    }

    public function updatedAgreed(bool $value): void
    {
        $user = auth()->user();

        if ($user->membership_status === 'active') {
            $this->agreed = true;

            return;
        }

        $user->update([
            'best_practices_agreed_at' => $value ? now() : null,
        ]);

        if ($value) {
            Flux::toast(
                text: 'You have agreed to follow the IRDI Best Practices. You have now completed both membership requirements and are ready to activate your IRDI membership.',
                heading: 'Best Practices complete!',
                duration: 10000,
                variant: 'success',
                action: [
                    'label' => 'Complete Membership',
                    'href' => route('membership.join'),
                    'navigate' => true,
                ],
            );
        }
    }
};
?>

<div class="mt-8 border-t border-zinc-200 pt-6">

    <flux:checkbox
        wire:model.live="agreed"
        label="I have read and agree to follow the IRDI Best Practices."
        :disabled="auth()->user()->membership_status === 'active'"
    />

    @if ($agreed)
        <p class="mt-3 text-sm text-green-700">
            Your agreement has been saved.
        </p>
    @endif

</div>
