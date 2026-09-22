<?php

use App\Models\MemberProfile;
use App\Support\ProfilePrivacy;
use Livewire\Component;

new class extends Component
{
    public bool $hidePersonalInformation = false;

    public function mount(): void
    {
        $this->hidePersonalInformation = (bool) auth()->user()?->memberProfile?->personal_information_hidden;
    }

    public function savePrivacy(): void
    {
        abort_unless(auth()->check() && auth()->user()->hasVerifiedEmail(), 403);
        $this->validate(['hidePersonalInformation' => ['boolean']]);
        $profile = MemberProfile::query()->where('user_id', auth()->id())->firstOrFail();

        try {
            app(ProfilePrivacy::class)->set($profile, $this->hidePersonalInformation);
        } catch (\RuntimeException $exception) {
            report($exception);
            $this->addError('hidePersonalInformation', __('Privacy could not be updated. Please try again or contact IRDI.'));

            return;
        }

        session()->flash('status', __('Your personal-information privacy setting has been updated.'));
        $this->redirectRoute('account');
    }
};
?>

<flux:card class="mt-10 p-6">
    <flux:heading size="lg">{{ __('Personal Information Privacy') }}</flux:heading>
    <form wire:submit="savePrivacy" class="mt-4 space-y-4">
        <flux:switch wire:model="hidePersonalInformation" label="{{ __('Hide My Personal Information') }}" />
        <p class="text-sm text-zinc-600">{{ __('Hide your name, photo, location, biography, and website from other members and public pages. IRDI staff retain access for account administration. Your public username, membership verification, and published rating summaries remain available.') }}</p>
        <p class="text-sm text-zinc-600">{{ __('Your username may identify you. Contact IRDI if you need an alias change. New incoming messages and review invitations are unavailable while privacy is enabled. Existing unused review invitations are cancelled and will not reopen automatically.') }}</p>
        <p class="text-sm text-zinc-600">{{ __('Information already shared in messages, delivered emails, downloaded images, or external search caches cannot be recalled by this setting.') }}</p>
        @if (auth()->user()->memberProfile?->encrypted_profile_image)
            <a href="{{ route('account.profiles.private-image', auth()->user()->memberProfile) }}" class="text-sm underline" target="_blank" rel="noopener">{{ __('View your private profile image') }}</a>
        @endif
        <flux:error name="hidePersonalInformation" />
        <flux:button type="submit" variant="primary">{{ __('Save Privacy Setting') }}</flux:button>
    </form>
</flux:card>
