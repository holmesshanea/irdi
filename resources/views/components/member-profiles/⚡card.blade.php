<?php

use App\Models\MemberProfile;
use Illuminate\Support\Facades\Storage;
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
        if (
            ! $profile->directory_visible
            && $profile->user_id !== auth()->id()
        ) {
            abort(404);
        }

        $this->profile = $profile;

        $this->qrCode = Quar::size(140)
            ->generate(route('member-profiles.show', $profile));
    }
};
?>

<div class="bg-white p-4">

    <div class="mx-auto max-w-2xl overflow-hidden rounded-2xl border border-zinc-200 bg-white shadow-sm">

        <div class="border-t-4 border-irdi-gold p-6">

            <div class="flex flex-col gap-6 sm:flex-row sm:items-start sm:justify-between">

                <div class="flex min-w-0 items-center gap-4">

                    @if ($profile->profile_image)

                        <img
                            src="{{ Storage::disk(config('filesystems.profile_images_disk', 'public'))->url($profile->profile_image) }}"
                            alt="{{ $profile->profile_name }}"
                            class="h-20 w-20 shrink-0 rounded-full object-cover"
                        >

                    @else

                        <div class="flex h-20 w-20 shrink-0 items-center justify-center rounded-full bg-zinc-100 text-2xl font-semibold text-irdi-green">
                            {{ strtoupper(substr($profile->profile_name, 0, 1)) }}
                        </div>

                    @endif

                    <div class="min-w-0">

                        <p class="text-xs font-semibold uppercase tracking-widest text-irdi-green">
                            IRDI Member
                        </p>

                        <h2 class="mt-2 text-xl font-bold text-irdi-green">
                            {{ $profile->profile_name }}
                        </h2>

                        <p class="mt-1 text-sm text-zinc-500">
                            {{ '@' . $profile->username }}
                        </p>

                        @if ($profile->user->member_since)
                            <p class="mt-2 text-sm text-zinc-500">
                                Member Since {{ $profile->user->member_since->format('F Y') }}
                            </p>
                        @endif

                    </div>

                </div>

                <div class="shrink-0 text-center sm:ml-auto">

                    {!! $qrCode !!}

                    <p class="mt-2 text-xs font-semibold uppercase tracking-wide text-irdi-green">
                        Verify Membership
                    </p>

                </div>

            </div>

            <div class="mt-5 border-t border-zinc-200 pt-4">
                <a
                    href="{{ route('member-profiles.show', $profile) }}"
                    target="_blank"
                    rel="noopener noreferrer"
                    class="text-xs font-medium text-irdi-green hover:underline"
                >
                    International Responsible Detectorist Institute
                </a>
            </div>

        </div>

    </div>

</div>
