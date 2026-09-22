<?php

use App\Models\MemberProfile;
use Livewire\Attributes\Layout;
use Livewire\Component;
use tbQuar\Facades\Quar;

new

#[Layout('components.layouts.embed')]
class extends Component
{
    public MemberProfile $profile;

    public string $qrCode = '';

    public function mount(MemberProfile $profile): void
    {
        if ($profile->user->membership_status !== 'active') {
            abort(404);
        }

        if (
            ! $profile->directory_visible
            && ! $profile->personal_information_hidden
            && $profile->user_id !== auth()->id()
        ) {
            abort(404);
        }

        $this->profile = $profile;

        $this->qrCode = Quar::size(116)
            ->generate(route('member-profiles.show', $profile));
    }
};
?>

<div class="bg-white p-3">
    @include('partials.member-profiles.membership-card', [
        'profile' => $profile,
        'qrCode' => $qrCode,
    ])
</div>
