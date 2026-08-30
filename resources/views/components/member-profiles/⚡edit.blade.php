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

    public bool $allowMemberMessages = false;
    public bool $emailMemberMessageNotifications = true;

    public string $city = '';

    public string $stateProvince = '';

    public string $country = '';

    public string $bio = '';

    public string $website = '';

    public ?TemporaryUploadedFile $profileImage = null;

    public function getMembershipSuspendedProperty(): bool
    {
        return auth()->user()->membership_status === 'suspended';
    }

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
        $this->allowMemberMessages = (bool) $profile->allow_member_messages;
        $this->emailMemberMessageNotifications =
            (bool) auth()->user()->email_member_message_notifications;
    }

    private function profileImageDisk(): string
    {
        return (string) config('filesystems.profile_images_disk', 'public');
    }

    public function removeProfileImage(): void
    {
        if ($this->profile->profile_image) {
            Storage::disk($this->profileImageDisk())
                ->delete($this->profile->profile_image);

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
            'allowMemberMessages' => ['boolean'],
            'emailMemberMessageNotifications' => ['boolean'],
            'city' => ['required', 'string', 'max:100'],
            'stateProvince' => ['required', 'string', 'max:100'],
            'country' => ['required', 'string', 'max:100'],
            'bio' => ['nullable', 'string', 'max:1000'],
            'website' => ['nullable', 'url', 'max:255'],
            'profileImage' => ['nullable', 'image', 'max:5120'],
        ]);

        if ($this->profileImage) {
            $disk = $this->profileImageDisk();

            if ($this->profile->profile_image) {
                Storage::disk($disk)
                    ->delete($this->profile->profile_image);
            }

            $imagePath = $this->profileImage->store(
                'profile-images',
                $disk
            );

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
            'directory_visible' => $this->membershipSuspended
                ? (bool) $this->profile->directory_visible
                : $validated['directoryVisible'],
            'allow_member_messages' => $this->membershipSuspended
                ? (bool) $this->profile->allow_member_messages
                : $validated['allowMemberMessages'],
        ]);

        auth()->user()->update([
            'email_member_message_notifications' =>
                $validated['emailMemberMessageNotifications'],
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
                    Update your IRDI member profile.
                </p>

            </div>

            @if ($this->membershipSuspended)

                <div class="mt-10 rounded-xl border border-red-300 bg-red-50 p-5">

                    <div class="flex gap-4">

                        <div class="shrink-0">
                            <div class="flex size-10 items-center justify-center rounded-full bg-red-100">
                                <flux:icon.exclamation-triangle class="size-5 text-red-700" />
                            </div>
                        </div>

                        <div class="min-w-0">

                            <h2 class="font-semibold text-red-900">
                                Your IRDI membership is suspended
                            </h2>

                            <p class="mt-2 text-sm leading-6 text-red-800">
                                You may continue to update your stored profile information while your membership is suspended.
                                Your public Member Directory listing and member-to-member messaging preferences are temporarily
                                unavailable and will remain unchanged until your membership is restored.
                            </p>

                            <p class="mt-4 text-sm leading-6 text-red-800">
                                If you have questions about your membership suspension or believe it should be reviewed,
                                please contact IRDI.
                            </p>

                            <div class="mt-4">
                                <flux:button
                                    href="{{ url('/contact') }}"
                                    variant="outline"
                                    size="sm"
                                    wire:navigate
                                >
                                    Contact IRDI
                                </flux:button>
                            </div>

                        </div>

                    </div>

                </div>

            @endif

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
                            Add an image to represent you in the IRDI Member Directory and on your public profile page.
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
                                        src="{{ Storage::disk(config('filesystems.profile_images_disk', 'public'))->url($profile->profile_image) }}"
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
                        label="Display Name"
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
                            Add your general location so other members can see where you're based. Do not enter a street address.
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
                        label="Bio"
                        description="This information will be displayed publicly on your profile."
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

                    @if ($this->membershipSuspended)

                        <div class="rounded-lg border border-zinc-200 bg-zinc-50 p-4">
                            <flux:switch
                                wire:model="directoryVisible"
                                label="Show this profile in the Member Directory"
                                description="Unavailable while your IRDI membership is suspended. Your saved preference will be preserved."
                                disabled
                            />
                        </div>

                        <div class="rounded-lg border border-zinc-200 bg-zinc-50 p-4">
                            <flux:switch
                                wire:model.live="allowMemberMessages"
                                label="Allow messages from other IRDI members"
                                description="Unavailable while your IRDI membership is suspended. Your saved preference will be preserved."
                                disabled
                            />
                        </div>

                    @else

                        <flux:switch
                            wire:model="directoryVisible"
                            label="Show this profile in the Member Directory"
                            description="When enabled, your profile can appear in the public IRDI Member Directory and anyone can view your public profile page."
                        />

                        <flux:switch
                            wire:model.live="allowMemberMessages"
                            label="Allow messages from other IRDI members"
                            description="Other active IRDI members can send you private messages through your IRDI profile. Your email address will not be shared."
                        />

                    @endif

                    @if ($allowMemberMessages)

                        <div class="ml-6 border-l-2 border-zinc-200 pl-4">

                            <flux:switch
                                wire:model="emailMemberMessageNotifications"
                                label="Email me when I receive a new IRDI message"
                                description="IRDI will send a notification to your account email address when you receive a new private message. The message itself will not be included in the email."
                            />

                        </div>

                    @endif

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
