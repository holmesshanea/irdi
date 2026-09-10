<?php

use Flux\Flux;
use Livewire\Component;

new class extends Component
{
    public bool $agreed = false;

    public function mount(): void
    {
        $this->agreed = auth()->user()->ethics_agreed_at !== null;

        if (! $this->agreed && auth()->user()->membership_status !== 'active') {
            Flux::toast(
                text: __('Please read the entire IRDI Code of Ethics. When finished, check "I have read and agree to follow the IRDI Code of Ethics." at the bottom of this page.'),
                heading: __('Review the Code of Ethics'),
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
            'ethics_agreed_at' => $value ? now() : null,
        ]);

        if ($value) {
            Flux::toast(
                text: __('You have agreed to follow the IRDI Code of Ethics. Continue to the IRDI Best Practices.'),
                heading: __('Code of Ethics complete!'),
                duration: 10000,
                variant: 'success',
                action: [
                    'label' => __('View Best Practices'),
                    'href' => route('best-practices'),
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
        label="{{ __('I have read and agree to follow the IRDI Code of Ethics.') }}"
        :disabled="auth()->user()->membership_status === 'active'"
    />

    @if ($agreed)
        <p class="mt-3 text-sm text-green-700">
            {{ __('Your agreement has been saved.') }}
        </p>
    @endif

</div>
