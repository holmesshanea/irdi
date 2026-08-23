<?php

use Illuminate\Validation\Rule;
use Livewire\Component;

new class extends Component
{
    public string $email = '';

    public function mount(): void
    {
        $this->email = auth()->user()->email;
    }

    public function updateEmail(): void
    {
        $user = auth()->user();

        $validated = $this->validate([
            'email' => [
                'required',
                'string',
                'lowercase',
                'email',
                'max:255',
                Rule::unique('users', 'email')->ignore($user->id),
            ],
        ]);

        $email = strtolower($validated['email']);

        if ($email === strtolower($user->email)) {
            $this->addError('email', 'This is already your current email address.');

            return;
        }

        $user->forceFill([
            'email' => $email,
            'email_verified_at' => null,
        ])->save();

        $user->sendEmailVerificationNotification();

        $this->email = $email;

        session()->flash(
            'email-status',
            'Your email address has been updated. Please check your new email address for a verification link.'
        );
    }
};
?>

<div>
    <div class="rounded-xl border border-zinc-200 bg-white p-6">

        <h2 class="text-lg font-semibold text-irdi-green">
            Email Address
        </h2>

        <p class="mt-2 text-sm text-zinc-600">
            Change the email address associated with your IRDI account.
            Your new email address must be verified.
        </p>

        @if (session('email-status'))
            <div class="mt-4 rounded-lg bg-green-50 p-4 text-sm text-green-800">
                {{ session('email-status') }}
            </div>
        @endif

        <form wire:submit="updateEmail" class="mt-6 space-y-4">

            <flux:field>
                <flux:label>Email Address</flux:label>

                <flux:input
                    wire:model="email"
                    type="email"
                    autocomplete="email"
                />

                <flux:error name="email" />
            </flux:field>

            <div>
                <flux:button
                    type="submit"
                    variant="primary"
                >
                    Change Email Address
                </flux:button>
            </div>

        </form>

    </div>
</div>
