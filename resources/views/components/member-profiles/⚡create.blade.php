<?php

use Livewire\Attributes\Layout;
use Livewire\Component;
use App\Models\MemberProfile;

new
#[Layout('components.layouts.public')]
class extends Component
{
    public string $profileType = '';
    public string $profileName = '';
    public string $username = '';
    public array $existingProfileTypes = [];

    protected array $messages = [
        'profileType.required' => 'Please select a profile type.',
        'profileName.required' => 'Please enter a name for this profile.',
        'profileName.min' => 'The profile name must be at least 5 characters.',
        'profileName.max' => 'The profile name may not be longer than 255 characters.',
        'username.required' => 'Please choose a username.',
        'username.min' => 'Your username must be at least 5 characters.',
        'username.max' => 'Your username may not be longer than 50 characters.',
        'username.alpha_dash' => 'Your username may only contain letters, numbers, dashes, and underscores.',
        'username.unique' => 'That username is already taken. Please choose another one.',
    ];

    public function updatedUsername(string $value): void
    {
        $this->username = strtolower($value);
    }

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

        $this->existingProfileTypes = $user
            ->memberProfiles()
            ->pluck('profile_type')
            ->all();

        if (count($this->existingProfileTypes) >= 3) {
            session()->flash(
                'status',
                'You have already created all three available IRDI profile types.'
            );

            $this->redirectRoute('account');

            return;
        }
    }

    public function updatedProfileType(string $value): void
    {
        if ($value === 'detectorist') {
            $this->profileName = auth()->user()->name;

            return;
        }

        $this->profileName = '';
    }

    public function saveProfile(): void
    {
        $this->username = strtolower($this->username);

        $validated = $this->validate([
            'profileType' => ['required', 'in:detectorist,vendor,organization'],
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

        $profileTypeExists = auth()->user()
            ->memberProfiles()
            ->where('profile_type', $validated['profileType'])
            ->exists();

        if ($profileTypeExists) {
            $this->addError(
                'profileType',
                'You already have a profile of this type.'
            );

            return;
        }

        $profile = MemberProfile::create([
            'user_id' => auth()->id(),
            'profile_type' => $validated['profileType'],
            'profile_name' => $validated['profileName'],
            'username' => $validated['username'],
            'directory_visible' => false,
        ]);

        session()->flash(
            'status',
            'Your IRDI profile has been created. You can now complete your profile details.'
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
                    Create an IRDI Profile
                </h1>

                <div class="mx-auto mt-4 h-1 w-20 bg-irdi-gold"></div>

                <p class="mt-6 text-lg leading-8 text-zinc-600">
                    Create a profile that represents your role within the
                    IRDI community.
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
                            Profile Type
                        </h2>

                        <p class="mt-4 text-zinc-600">
                            Choose the type of profile you would like to create.
                            You can create up to three profiles, with one profile
                            for each type: Detectorist, Organization, and Vendor.
                        </p>

                        <div class="mt-6">
                            <flux:radio.group
                                wire:model.live="profileType"
                                label="Select a profile type"
                            >
                                @if (! in_array('detectorist', $existingProfileTypes))
                                    <flux:radio
                                        value="detectorist"
                                        label="Detectorist"
                                    />
                                @endif

                                @if (! in_array('organization', $existingProfileTypes))
                                    <flux:radio
                                        value="organization"
                                        label="Organization"
                                    />
                                @endif

                                @if (! in_array('vendor', $existingProfileTypes))
                                    <flux:radio
                                        value="vendor"
                                        label="Vendor"
                                    />
                                @endif
                            </flux:radio.group>
                        </div>

                    </flux:card>

                    <flux:card class="mt-6 p-6">

                        <h2 class="text-xl font-semibold text-irdi-green">
                            Profile Information
                        </h2>

                        <p class="mt-4 text-zinc-600">
                            Enter the basic information for this profile. You can add your profile image,
                            location, bio, website, and directory visibility after creating it.
                        </p>

                        <div class="mt-6">
                            <flux:input
                                wire:model="profileName"
                                :disabled="$profileType === ''"
                                :label="match ($profileType) {
                                    'vendor' => 'Business Name',
                                    'organization' => 'Organization Name',
                                    'detectorist' => 'Name',
                                    default => 'Profile Name',
                                }"
                                :placeholder="match ($profileType) {
                                    'vendor' => 'Enter business name',
                                    'organization' => 'Enter organization name',
                                    'detectorist' => 'Enter your name',
                                    default => 'Select a profile type first',
                                }"
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
