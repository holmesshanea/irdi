<?php

use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;
use Livewire\Component;

new class extends Component
{
    public string $currentPassword = '';

    public string $password = '';

    public string $password_confirmation = '';

    public function updatePassword(): void
    {
        $validated = $this->validate([
            'currentPassword' => [
                'required',
                'string',
                'current_password',
            ],
            'password' => [
                'required',
                'string',
                Password::defaults(),
                'confirmed',
            ],
        ], [
            'currentPassword.current_password' => 'The current password is incorrect.',
            'password.confirmed' => 'The new password confirmation does not match.',
        ]);

        auth()->user()->update([
            'password' => Hash::make($validated['password']),
        ]);

        $this->reset([
            'currentPassword',
            'password',
            'password_confirmation',
        ]);

        session()->flash(
            'password-status',
            'Your password has been updated successfully.'
        );
    }
};
?>

<div>
    <div class="rounded-xl border border-zinc-200 bg-white p-6">

        <h2 class="text-lg font-semibold text-irdi-green">
            Password
        </h2>

        <p class="mt-2 text-sm text-zinc-600">
            Update the password used to sign in to your IRDI account.
        </p>

        @if (session('password-status'))
            <div class="mt-4 rounded-lg bg-green-50 p-4 text-sm text-green-800">
                {{ session('password-status') }}
            </div>
        @endif

        <form wire:submit="updatePassword" class="mt-6 space-y-4">

            <flux:field>
                <flux:label>Current Password</flux:label>

                <flux:input
                    wire:model="currentPassword"
                    type="password"
                    autocomplete="current-password"
                    viewable
                />

                <flux:error name="currentPassword" />
            </flux:field>

            <flux:field>
                <flux:label>New Password</flux:label>

                <flux:input
                    wire:model="password"
                    type="password"
                    autocomplete="new-password"
                    viewable
                />

                <flux:description>
                    Use at least 12 characters with uppercase and lowercase letters, a number, and a symbol.
                </flux:description>

                <flux:error name="password" />
            </flux:field>

            <flux:field>
                <flux:label>Confirm New Password</flux:label>

                <flux:input
                    wire:model="password_confirmation"
                    type="password"
                    autocomplete="new-password"
                    viewable
                />

                <flux:error name="password_confirmation" />
            </flux:field>

            <div>
                <flux:button
                    type="submit"
                    variant="primary"
                >
                    Change Password
                </flux:button>
            </div>

        </form>

    </div>
</div>
