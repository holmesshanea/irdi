<?php

use Livewire\Component;

new class extends Component
{
    public bool $agreed = false;

    public function mount(): void
    {
        $this->agreed = auth()->user()->ethics_agreed_at !== null;
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
    }
};
?>

<div class="mt-8 border-t border-zinc-200 pt-6">

    <flux:checkbox
        wire:model.live="agreed"
        label="I have read and agree to follow the IRDI Code of Ethics."
        :disabled="auth()->user()->membership_status === 'active'"
    />

    @if ($agreed)
        <p class="mt-3 text-sm text-green-700">
            Your agreement has been saved.
        </p>
    @endif

</div>
