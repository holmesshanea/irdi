<?php

use App\Models\MemberProfile;
use Livewire\Attributes\Layout;
use Livewire\Component;

new
#[Layout('components.layouts.public')]
class extends Component
{
    public string $profileName = '';
    public string $username = '';

    protected array $messages = [
        'profileName.required' => 'Please enter your name.',
        'profileName.min' => 'Your name must be at least 5 characters.',
        'profileName.max' => 'Your name may not be longer than 255 characters.',
        'username.required' => 'Please choose a username.',
        'username.min' => 'Your username must be at least 5 characters.',
        'username.max' => 'Your username may not be longer than 50 characters.',
        'username.alpha_dash' => 'Your username may only contain letters, numbers, dashes, and underscores.',
        'username.unique' => 'That username is already taken. Please choose another one.',
    ];

    public function mount(): void
    {
        $user = auth()->user();

        if ($user->membership_status !== 'active') {
            session()->flash(
                'status',
                'You must have an active IRDI membership before creating a profile.'
            );

            $this->redirectRoute('account');

            return;
        }

        if ($user->memberProfile()->exists()) {
            session()->flash(
                'status',
                'You have already created your IRDI member profile.'
            );

            $this->redirectRoute('account');

            return;
        }

        $this->profileName = $user->name;
    }

    public function updatedUsername(string $value): void
    {
        $this->username = strtolower($value);
    }

    public function saveProfile(): void
    {
        $this->username = strtolower($this->username);

        if (auth()->user()->memberProfile()->exists()) {
            session()->flash(
                'status',
                'You have already created your IRDI member profile.'
            );

            $this->redirectRoute('account');

            return;
        }

        $validated = $this->validate([
            'profileName' => ['required', 'string', 'min:5', 'max:255'],
            'username' => [
                'required',
                'string',
                'min:5',
                'max:50',
                'alpha_dash',
                'unique:member_profiles,username',
            ],
        ]);

        $profile = MemberProfile::create([
            'user_id' => auth()->id(),
            'profile_name' => $validated['profileName'],
            'username' => $validated['username'],
            'directory_visible' => false,
        ]);

        session()->flash(
            'status',
            'Your IRDI member profile has been created. You can now complete your profile details.'
        );

        $this->redirectRoute('account.profiles.edit', [
            'profile' => $profile,
        ]);
    }
};
?>

<div>

    <section class="bg-zinc-50">
        <div class="mx-auto max-w-7xl px-6 py-16 lg:px-8 lg:py-20">

            <div class="mx-auto max-w-3xl text-center">

                <h1 class="text-3xl font-bold tracking-tight text-irdi-green sm:text-4xl">
                    Create Your IRDI Member Profile
                </h1>

                <div class="mx-auto mt-4 h-1 w-20 bg-irdi-gold"></div>

                <p class="mt-6 text-lg leading-8 text-zinc-600">
                    Create your public IRDI member profile and choose the username
                    that will identify you in the Member Directory.
                </p>

            </div>

        </div>
    </section>

    <section class="bg-white">
        <div class="mx-auto max-w-7xl px-6 py-12 lg:px-8">

            <div class="mx-auto max-w-3xl">

                <form wire:submit="saveProfile">

                    <flux:card class="p-6">

                        <h2 class="text-xl font-semibold text-irdi-green">
                            Profile Information
                        </h2>

                        <p class="mt-4 text-zinc-600">
                            Enter the basic information for your member profile.
                            You can add your profile image, location, bio, website,
                            and directory visibility after creating it.
                        </p>

                        <div class="mt-6">
                            <flux:input
                                wire:model="profileName"
                                label="Name"
                                placeholder="Enter your name"
                            />
                        </div>

                        <div class="mt-6">
                            <flux:input
                                wire:model.live="username"
                                label="Username"
                                placeholder="relic-man"
                                description="This becomes your unique public @username and is used in your IRDI profile URL."
                            />
                        </div>

                    </flux:card>

                    <div class="mt-6 flex justify-end">
                        <flux:button
                            type="submit"
                            variant="primary"
                            wire:loading.attr="disabled"
                            wire:target="saveProfile"
                        >
                            Create Profile
                        </flux:button>
                    </div>

                </form>

            </div>

        </div>
    </section>

</div>
