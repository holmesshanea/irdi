<?php

use App\Models\MemberProfile;
use Illuminate\Support\Facades\Storage;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use Livewire\WithFileUploads;

new
#[Layout('components.layouts.public')]
class extends Component
{
    use WithFileUploads;

    protected array $messages = [
        'profileImage.max' => 'The profile image must be 5 MB or smaller.',
        'profileImage.image' => 'The profile image must be a valid image file.',
    ];

    public MemberProfile $profile;

    public string $profileName = '';

    public bool $directoryVisible = false;

    public string $city = '';

    public string $stateProvince = '';

    public string $country = '';

    public string $bio = '';

    public string $website = '';

    public ?TemporaryUploadedFile $profileImage = null;

    public function mount(MemberProfile $profile): void
    {
        if ($profile->user_id !== auth()->id()) {
            abort(403);
        }

        $this->profile = $profile;

        $this->profileName = $profile->profile_name;
        $this->directoryVisible = (bool) $profile->directory_visible;
        $this->city = $profile->city ?? '';
        $this->stateProvince = $profile->state_province ?? '';
        $this->country = $profile->country ?? '';
        $this->bio = $profile->bio ?? '';
        $this->website = $profile->website ?: 'https://';
    }

    public function removeProfileImage(): void
    {
        if ($this->profile->profile_image) {
            Storage::disk('public')->delete($this->profile->profile_image);

            $this->profile->update([
                'profile_image' => null,
            ]);
        }

        $this->profileImage = null;
    }

    public function missingProfileFields(): array
    {
        return collect([
            'profile image' => filled($this->profileImage) || filled($this->profile->profile_image),
            'city' => filled($this->city),
            'state/province' => filled($this->stateProvince),
            'country' => filled($this->country),
            'bio' => filled($this->bio),
        ])
            ->reject()
            ->keys()
            ->all();
    }

    public function profileIsComplete(): bool
    {
        return empty($this->missingProfileFields());
    }

    public function save(): void
    {
        $validated = $this->validate([
            'profileName' => ['required', 'string', 'min:5', 'max:255'],
            'directoryVisible' => ['boolean'],
            'city' => ['required', 'string', 'max:100'],
            'stateProvince' => ['required', 'string', 'max:100'],
            'country' => ['required', 'string', 'max:100'],
            'bio' => ['nullable', 'string', 'max:1000'],
            'website' => ['nullable', 'url', 'max:255'],
            'profileImage' => ['nullable', 'image', 'max:5120'],
        ]);

        if ($this->profileImage) {
            if ($this->profile->profile_image) {
                Storage::disk('public')->delete($this->profile->profile_image);
            }

            $imagePath = $this->profileImage->store('profile-images', 'public');

            $this->profile->profile_image = $imagePath;
            $this->profile->save();
        }

        $this->profile->update([
            'profile_name' => $validated['profileName'],
            'city' => $validated['city'],
            'state_province' => $validated['stateProvince'],
            'country' => $validated['country'],
            'bio' => $validated['bio'],
            'website' => $validated['website'] ?: null,
            'directory_visible' => $validated['directoryVisible'],
        ]);

        session()->flash('status', 'Your profile has been updated.');

        $this->redirectRoute('account');
    }
};
?>

<section class="bg-zinc-50">
    <div class="mx-auto max-w-7xl px-6 py-16 lg:px-8 lg:py-20">

        <div class="mx-auto max-w-3xl">

            @if (session('status'))
                <div class="mb-8 rounded-lg bg-green-50 p-4 text-sm text-green-800">
                    {{ session('status') }}
                </div>
            @endif

            <div class="text-center">

                <h1 class="text-3xl font-bold tracking-tight text-irdi-green sm:text-4xl">
                    Edit Profile
                </h1>

                <div class="mx-auto mt-4 h-1 w-20 bg-irdi-gold"></div>

                <p class="mt-6 text-lg text-zinc-600">
                    Update your IRDI {{ ucfirst($profile->profile_type) }} profile.
                </p>

            </div>

            <flux:card class="mt-10 p-6">

                <div class="mb-6 rounded-lg border border-zinc-200 bg-zinc-50 p-4">

                    <div class="flex flex-wrap items-center justify-between gap-3">

                        <div>
                            <h2 class="font-semibold text-irdi-green">
                                Profile Completion
                            </h2>

                            <p class="mt-1 text-sm text-zinc-600">
                                Complete the important public fields to make your IRDI profile more useful in the Member Directory.
                            </p>
                        </div>

                        @if ($this->profileIsComplete())

                            <flux:badge color="green">
                                Profile Complete
                            </flux:badge>

                        @else

                            <flux:badge color="amber">
                                Needs Attention
                            </flux:badge>

                        @endif

                    </div>

                    @unless ($this->profileIsComplete())
                        <p class="mt-3 text-sm text-amber-700">
                            Missing: {{ collect($this->missingProfileFields())->implode(', ') }}.
                        </p>
                    @endunless

                </div>

                <form wire:submit="save" class="space-y-6">

                    <div>
                        <p class="text-sm font-medium text-zinc-700">
                            Profile Image
                        </p>

                        <p class="mt-1 mb-4 text-sm text-zinc-500">
                            Add an image to represent this profile in the IRDI Member Directory and on its public profile page.
                        </p>

                        <div class="flex items-start gap-6">

                            <div>
                                @if ($profileImage)

                                    <img
                                        src="{{ $profileImage->temporaryUrl() }}"
                                        alt="New profile image preview"
                                        class="h-32 w-32 rounded-full object-cover"
                                    >

                                    <p class="mt-2 text-center text-xs text-zinc-500">
                                        New image preview
                                    </p>

                                @elseif ($profile->profile_image)

                                    <img
                                        src="{{ asset('storage/' . $profile->profile_image) }}"
                                        alt="{{ $profile->profile_name }}"
                                        class="h-32 w-32 rounded-full object-cover"
                                    >

                                @else

                                    <div class="flex h-32 w-32 items-center justify-center rounded-full bg-zinc-200 text-3xl font-semibold text-irdi-green">
                                        {{ strtoupper(substr($profile->profile_name, 0, 1)) }}
                                    </div>

                                @endif
                            </div>

                            <div class="flex-1">

                                <flux:input
                                    type="file"
                                    wire:model="profileImage"
                                    label="Upload Profile Image"
                                    accept="image/jpeg,image/png,image/webp"
                                />

                                @if ($profile->profile_image && ! $profileImage)
                                    <div class="mt-3">
                                        <flux:button
                                            type="button"
                                            wire:click="removeProfileImage"
                                            wire:confirm="Are you sure you want to remove your profile image?"
                                            variant="danger"
                                            size="sm"
                                        >
                                            Remove Profile Image
                                        </flux:button>
                                    </div>
                                @endif

                            </div>

                        </div>
                    </div>

                    <flux:input
                        wire:model="profileName"
                        :label="match ($profile->profile_type) {
                            'vendor' => 'Business Name',
                            'organization' => 'Organization Name',
                            'detectorist' => 'Display Name',
                            default => 'Profile Name',
                        }"
                        required
                    />

                    <flux:input
                        value="{{ '@' . $profile->username }}"
                        label="Username"
                        description="Your username is your permanent IRDI public handle and cannot be changed."
                        disabled
                    />

                    <div>
                        <h2 class="text-sm font-medium text-zinc-700">
                            Public Location
                        </h2>

                        <p class="mt-1 text-sm text-zinc-500">
                            @switch($profile->profile_type)

                                @case('detectorist')
                                    Add your general location so other members can see where you're based. Do not enter a street address.
                                    @break

                                @case('organization')
                                    Add the general location where your organization is based or primarily operates. Do not enter a street address.
                                    @break

                                @case('vendor')
                                    Add the general location where your business is based or primarily operates. Do not enter a street address.
                                    @break

                                @default
                                    Add the general location you want displayed on this profile. Do not enter a street address.

                            @endswitch
                        </p>

                        <div class="mt-4 grid grid-cols-1 gap-6 sm:grid-cols-2">

                            <flux:input
                                wire:model.live="city"
                                label="City"
                            />

                            <flux:input
                                wire:model.live="stateProvince"
                                label="State / Province"
                            />

                            <div class="sm:col-span-2">
                                <flux:input
                                    wire:model.live="country"
                                    label="Country"
                                />
                            </div>

                        </div>
                    </div>

                    <flux:textarea
                        wire:model.live="bio"
                        :label="match ($profile->profile_type) {
                            'vendor' => 'About the Business',
                            'organization' => 'About the Organization',
                            'detectorist' => 'Bio',
                            default => 'Bio',
                        }"
                        description="This information will be displayed publicly on the profile."
                        rows="5"
                    />

                    <p class="mt-2 text-right text-sm text-zinc-500">
                        {{ strlen($bio ?? '') }} / 1,000 characters
                    </p>

                    <flux:input
                        wire:model="website"
                        label="Website"
                        type="url"
                        placeholder="https://example.com"
                        description="Optional. This website will be displayed publicly on your profile."
                    />

                    <flux:switch
                        wire:model="directoryVisible"
                        label="Show this profile in the Member Directory"
                        description="When enabled, this profile can appear in the public IRDI Member Directory and anyone can view its public profile page."
                    />

                    <div class="flex items-center gap-3">

                        <flux:button
                            type="submit"
                            variant="primary"
                            wire:loading.attr="disabled"
                            wire:target="save"
                        >
                            <span wire:loading.remove wire:target="save">
                                Save Changes
                            </span>

                            <span wire:loading wire:target="save">
                                Saving...
                            </span>
                        </flux:button>

                        <flux:button
                            :href="route('account')"
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
