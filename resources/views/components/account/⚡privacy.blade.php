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
            $this->addError('hidePersonalInformation', __('privacy.error'));

            return;
        }

        session()->flash('status', __('privacy.success'));
        $this->redirectRoute('account');
    }
};
?>

<flux:card class="mt-10 p-6">
    <flux:heading size="lg">{{ __('privacy.title') }}</flux:heading>
    <form wire:submit="savePrivacy" class="mt-4 space-y-4">
        <flux:switch wire:model="hidePersonalInformation" label="{{ __('privacy.hide') }}" />
        <p class="text-sm text-zinc-600">{{ __('privacy.summary') }}</p>
        <p class="text-sm text-zinc-600">{{ __('privacy.contact_controls') }}</p>
        <p class="text-sm text-zinc-600">{{ __('privacy.limits') }}</p>
        @if (auth()->user()->memberProfile?->encrypted_profile_image)
            <a href="{{ route('account.profiles.private-image', auth()->user()->memberProfile) }}" class="text-sm underline" target="_blank" rel="noopener">{{ __('privacy.photo') }}</a>
        @endif
        <flux:error name="hidePersonalInformation" />
        <flux:button type="submit" variant="primary">{{ __('privacy.save') }}</flux:button>
    </form>
</flux:card>
