<?php

use App\Models\MemberProfile;
use Illuminate\Support\Facades\Storage;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;

new
#[Layout('components.layouts.public')]
#[Title('IRDI Member Directory | Responsible Detectorists')]

class extends Component
{
    use WithPagination;

    public string $search = '';
    public string $country = '';
    public string $stateProvince = '';
    public string $city = '';
    public string $sort = 'name_asc';
    public string $designation = 'all';

    public function getHasActiveFiltersProperty(): bool
    {
        return $this->search !== ''
            || $this->country !== ''
            || $this->stateProvince !== ''
            || $this->city !== ''
            || $this->designation !== 'all';
    }

    public function getProfilesProperty()
    {
        return MemberProfile::query()
            ->with('user')
            ->publicDirectory()
            ->when($this->search, function ($query) {
                $query->where(function ($query) {
                    $query
                        ->where('profile_name', 'like', '%' . $this->search . '%')
                        ->orWhere('username', 'like', '%' . $this->search . '%');
                });
            })
            ->when($this->country, function ($query) {
                $query->where('country_code', $this->country);
            })
            ->when($this->stateProvince, function ($query) {
                $query->where('state_province', $this->stateProvince);
            })
            ->when($this->city, function ($query) {
                $query->where('city', $this->city);
            })
            ->when($this->designation === 'charter', function ($query) {
                $query->whereHas('user', function ($query) {
                    $query->where('is_charter_member', true);
                });
            })
            ->when($this->designation === 'admin', function ($query) {
                $query->whereHas('user', function ($query) {
                    $query->where('is_admin', true);
                });
            })
            ->when($this->designation === 'moderator', function ($query) {
                $query->whereHas('user', function ($query) {
                    $query->where('is_moderator', true);
                });
            })
            ->when($this->sort === 'name_asc', function ($query) {
                $query
                    ->orderBy('profile_name')
                    ->orderBy('id');
            })
            ->when($this->sort === 'name_desc', function ($query) {
                $query
                    ->orderByDesc('profile_name')
                    ->orderByDesc('id');
            })
            ->when($this->sort === 'newest', function ($query) {
                $query
                    ->orderByDesc(
                        App\Models\User::select('member_since')
                            ->whereColumn('users.id', 'member_profiles.user_id')
                    )
                    ->orderBy('profile_name')
                    ->orderBy('id');
            })
            ->when($this->sort === 'longest', function ($query) {
                $query
                    ->orderBy(
                        App\Models\User::select('member_since')
                            ->whereColumn('users.id', 'member_profiles.user_id')
                    )
                    ->orderBy('profile_name')
                    ->orderBy('id');
            })
            ->paginate(24);
    }

    public function getCountriesProperty()
    {
        return MemberProfile::query()
            ->publicDirectory()
            ->whereNotNull('country_code')
            ->where('country_code', '!=', '')
            ->whereNotNull('country')
            ->where('country', '!=', '')
            ->select(['country_code', 'country'])
            ->distinct()
            ->orderBy('country')
            ->pluck('country', 'country_code');
    }

    public function getStatesProvincesProperty()
    {
        return MemberProfile::query()
            ->publicDirectory()
            ->when($this->country, function ($query) {
                $query->where('country_code', $this->country);
            })
            ->whereNotNull('state_province')
            ->where('state_province', '!=', '')
            ->distinct()
            ->orderBy('state_province')
            ->pluck('state_province');
    }

    public function getCitiesProperty()
    {
        return MemberProfile::query()
            ->publicDirectory()
            ->when($this->country, function ($query) {
                $query->where('country_code', $this->country);
            })
            ->when($this->stateProvince, function ($query) {
                $query->where('state_province', $this->stateProvince);
            })
            ->whereNotNull('city')
            ->where('city', '!=', '')
            ->distinct()
            ->orderBy('city')
            ->pluck('city');
    }

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function updatedCountry(): void
    {
        $this->stateProvince = '';
        $this->city = '';
        $this->resetPage();
    }

    public function updatedStateProvince(): void
    {
        $this->city = '';
        $this->resetPage();
    }

    public function updatedCity(): void
    {
        $this->resetPage();
    }

    public function updatedSort(): void
    {
        $this->resetPage();
    }

    public function updatedDesignation(): void
    {
        $this->resetPage();
    }

    public function clearFilters(): void
    {
        $this->reset([
            'search',
            'country',
            'stateProvince',
            'city',
        ]);

        $this->designation = 'all';

        $this->resetPage();
    }
}
?>

<div>


    {{-- Directory Hero --}}
    <section class="relative overflow-hidden bg-irdi-green">

        <picture class="absolute inset-0">
            <source
                media="(max-width: 640px)"
                srcset="{{ asset('images/mobile/mobile4.png') }}"
            >

            <img
                src="{{ asset('images/carousel/carousel4.png') }}"
                alt=""
                class="h-full w-full object-cover"
            >
        </picture>

        <div class="absolute inset-0 bg-irdi-green/75"></div>

        <div class="relative mx-auto max-w-7xl px-6 py-20 lg:px-8 lg:py-28">

            <div class="mx-auto max-w-3xl text-center">

                <p class="text-sm font-semibold uppercase tracking-widest text-irdi-gold">
                    International Responsible Detectorist Institute
                </p>

                <h1 class="mt-4 text-4xl font-bold tracking-tight text-white sm:text-5xl lg:text-6xl">
                    Member Directory
                </h1>

                <p class="mx-auto mt-6 max-w-2xl text-lg leading-8 text-zinc-200">
                    Search for active IRDI Members.
                </p>

            </div>

        </div>
    </section>

    <section class="bg-zinc-50">
        <div class="mx-auto max-w-7xl px-6 py-12 lg:px-8">

            <div class="mb-8 grid grid-cols-1 gap-4 md:grid-cols-2 lg:grid-cols-7">

                <div class="md:col-span-2 lg:col-span-2">
                    <flux:input
                        wire:model.live.debounce.300ms="search"
                        type="search"
                        placeholder="Search by name or username..."
                        icon="magnifying-glass"
                    />
                </div>

                <flux:select
                    wire:model.live="country"
                    variant="listbox"
                    searchable
                    placeholder="All Countries"
                >
                    <flux:select.option value="" label="All Countries">
                        All Countries
                    </flux:select.option>

                    @foreach ($this->countries as $code => $name)
                        <flux:select.option
                            value="{{ $code }}"
                            label="{{ $name }}"
                        >
                            <div class="flex items-center gap-2">
                                <flux:flag country="{{ $code }}" size="xs" />
                                <span>{{ $name }}</span>
                            </div>
                        </flux:select.option>
                    @endforeach
                </flux:select>

                <flux:select wire:model.live="stateProvince">
                    <flux:select.option value="">
                        All States / Provinces
                    </flux:select.option>

                    @foreach ($this->statesProvinces as $stateProvince)
                        <flux:select.option value="{{ $stateProvince }}">
                            {{ $stateProvince }}
                        </flux:select.option>
                    @endforeach
                </flux:select>

                <flux:select wire:model.live="city">
                    <flux:select.option value="">
                        All Cities
                    </flux:select.option>

                    @foreach ($this->cities as $city)
                        <flux:select.option value="{{ $city }}">
                            {{ $city }}
                        </flux:select.option>
                    @endforeach
                </flux:select>

                <flux:select wire:model.live="sort">
                    <flux:select.option value="name_asc">
                        Name A–Z
                    </flux:select.option>

                    <flux:select.option value="name_desc">
                        Name Z–A
                    </flux:select.option>

                    <flux:select.option value="newest">
                        Newest Members
                    </flux:select.option>

                    <flux:select.option value="longest">
                        Longest-Standing Members
                    </flux:select.option>
                </flux:select>

                <flux:select wire:model.live="designation">
                    <flux:select.option value="all">
                        All Members
                    </flux:select.option>

                    <flux:select.option value="charter">
                        Charter Members
                    </flux:select.option>

                    <flux:select.option value="admin">
                        Administrators
                    </flux:select.option>

                    <flux:select.option value="moderator">
                        Moderators
                    </flux:select.option>
                </flux:select>

            </div>

            @if ($this->hasActiveFilters)
                <div class="-mt-4 mb-8 flex justify-end">
                    <flux:button
                        wire:click="clearFilters"
                        variant="ghost"
                        icon="x-mark"
                    >
                        Clear Filters
                    </flux:button>
                </div>
            @endif

            <div
                id="directory-results"
                class="mb-6 flex items-center justify-between gap-4 border-b border-zinc-200 pb-4"
            >

                <div>
                    <flux:heading size="lg">
                        Directory Results
                    </flux:heading>

                    <flux:text class="mt-1">
                        {{ $this->profiles->total() }}
                        {{ Str::plural('member', $this->profiles->total()) }} found
                    </flux:text>
                </div>

                <div
                    wire:loading
                    wire:target="search,country,stateProvince,city,sort,designation,clearFilters"
                    class="text-sm text-zinc-500"
                >
                    Updating results...
                </div>

            </div>

            <div class="grid grid-cols-1 gap-6 sm:grid-cols-2 lg:grid-cols-3">

                @forelse ($this->profiles as $profile)

                    <a
                        href="{{ route('member-profiles.show', ['profile' => $profile->username]) }}"
                        class="block rounded-xl focus:outline-none focus:ring-2 focus:ring-irdi-gold focus:ring-offset-2"
                    >
                        <flux:card class="h-full transition hover:-translate-y-1 hover:shadow-lg">

                            <div class="flex items-start gap-4">

                                <div class="flex h-14 w-14 shrink-0 items-center justify-center overflow-hidden rounded-full bg-irdi-green text-lg font-semibold text-white">

                                    @if ($profile->profile_image)

                                        <img
                                            src="{{ Storage::disk(config('filesystems.profile_images_disk', 'public'))->url($profile->profile_image) }}"
                                            alt="{{ $profile->profile_name }}"
                                            class="h-full w-full object-cover"
                                        >

                                    @else

                                        {{ strtoupper(substr($profile->profile_name, 0, 1)) }}

                                    @endif

                                </div>

                                <div class="min-w-0">

                                    <flux:heading size="lg">
                                        {{ $profile->profile_name }}
                                    </flux:heading>

                                    <div class="mt-1 text-sm font-medium text-irdi-green">
                                        {{ '@' . $profile->username }}
                                    </div>

                                    @if (
                                        $profile->user->is_admin
                                        || $profile->user->is_moderator
                                        || $profile->user->is_charter_member
                                    )
                                        <div class="mt-3 flex flex-wrap gap-2">

                                            @if ($profile->user->is_admin)
                                                <flux:badge
                                                    size="sm"
                                                    color="red"
                                                >
                                                    Administrator
                                                </flux:badge>
                                            @elseif ($profile->user->is_moderator)
                                                <flux:badge
                                                    size="sm"
                                                    color="blue"
                                                >
                                                    Moderator
                                                </flux:badge>
                                            @endif

                                            @if ($profile->user->is_charter_member)
                                                <flux:badge
                                                    size="sm"
                                                    color="amber"
                                                >
                                                    Charter Member
                                                </flux:badge>
                                            @endif

                                        </div>
                                    @endif

                                    @if ($profile->city || $profile->state_province || $profile->country)
                                        <div class="mt-2 flex items-center gap-2 text-sm text-zinc-600">

                                            @if ($profile->country_code)
                                                <flux:flag
                                                    country="{{ $profile->country_code }}"
                                                    size="xs"
                                                    class="shrink-0"
                                                />
                                            @endif

                                            <span>
                                                {{ collect([
                                                    $profile->city,
                                                    $profile->state_province,
                                                    $profile->country,
                                                ])->filter()->implode(', ') }}
                                            </span>

                                        </div>
                                    @endif

                                    @if ($profile->user->member_since)
                                        <div class="mt-2 text-xs text-zinc-500">
                                            IRDI Member Since {{ $profile->user->member_since->format('F Y') }}
                                        </div>
                                    @endif

                                    <div class="mt-4 flex items-center gap-1 text-sm font-medium text-irdi-green">
                                        <span>View Profile</span>
                                        <flux:icon.arrow-right class="size-4" />
                                    </div>

                                </div>

                            </div>

                        </flux:card>
                    </a>

                @empty

                    <div class="col-span-full rounded-xl border border-zinc-200 bg-white px-6 py-12 text-center">

                        <flux:heading size="lg">
                            No matching members found
                        </flux:heading>

                        <flux:text class="mt-2">
                            Try changing your search or filters.
                        </flux:text>

                        @if ($this->hasActiveFilters)
                            <div class="mt-6">
                                <flux:button
                                    wire:click="clearFilters"
                                    variant="primary"
                                >
                                    Clear Filters
                                </flux:button>
                            </div>
                        @endif

                    </div>

                @endforelse

            </div>

            @if ($this->profiles->hasPages())
                <div class="mt-10">
                    {{ $this->profiles->links(data: ['scrollTo' => '#directory-results']) }}
                </div>
            @endif

        </div>
    </section>

</div>
