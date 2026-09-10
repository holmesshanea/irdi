<?php

use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Livewire\Attributes\Layout;
use Livewire\Component;

new
#[Layout('components.layouts.public')]
class extends Component
{
    public string $password = '';

    public function deleteAccount(): void
    {
        $user = auth()->user();

        if (! Hash::check($this->password, $user->password)) {
            $this->addError('password', 'The password you entered is incorrect.');

            return;
        }

        if ($user->memberProfile?->profile_image) {
            Storage::disk(config('filesystems.profile_images_disk', 'public'))->delete(
                $user->memberProfile->profile_image
            );
        }

        auth()->logout();

        $user->delete();

        session()->invalidate();
        session()->regenerateToken();

        $this->redirectRoute('home');
    }
};
?>

<div>

    <section class="bg-zinc-50">
        <div class="mx-auto max-w-7xl px-6 py-16 lg:px-8 lg:py-20">

            <div class="mx-auto max-w-2xl">

                <h1 class="text-3xl font-bold tracking-tight text-red-700 sm:text-4xl">
                    Delete Your IRDI Account
                </h1>

                <div class="mt-6 rounded-lg bg-red-50 p-5 text-sm text-red-800">
                    Deleting your account is permanent. Your IRDI account, membership,
                    member profile, and uploaded profile image will be deleted.
                </div>

                <flux:card class="mt-8 p-6">

                    <form wire:submit="deleteAccount">

                        <flux:field>
                            <flux:label>
                                Current Password
                            </flux:label>

                            <flux:input
                                type="password"
                                wire:model="password"
                                autocomplete="current-password"
                            />

                            <flux:error name="password" />
                        </flux:field>

                        <div class="mt-6 flex flex-wrap gap-3">

                            <flux:button
                                type="submit"
                                variant="danger"
                                icon="trash"
                            >
                                Permanently Delete Account
                            </flux:button>

                            <flux:button
                                href="{{ route('account') }}"
                                variant="ghost"
                            >
                                Cancel
                            </flux:button>

                        </div>

                    </form>

                </flux:card>

            </div>

        </div>
    </section>

</div>
