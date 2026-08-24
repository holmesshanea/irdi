<x-layouts.public>

    <section class="bg-zinc-50">
        <div class="mx-auto max-w-7xl px-6 py-16 lg:px-8 lg:py-20">

            <div class="mx-auto max-w-3xl text-center">

                @if (session('status'))
                    <div class="mb-8 rounded-lg bg-green-50 p-4 text-sm text-green-800">
                        {{ session('status') }}
                    </div>
                @endif

                <h1 class="text-3xl font-bold tracking-tight text-irdi-green sm:text-4xl">
                    Your IRDI Account
                </h1>

                <div class="mx-auto mt-4 h-1 w-20 bg-irdi-gold"></div>

                <p class="mt-6 text-lg text-zinc-600">
                    Welcome, {{ auth()->user()->name }}.
                </p>

            </div>

        </div>
    </section>

    <section class="bg-white">
        <div class="mx-auto max-w-7xl px-6 py-12 lg:px-8">

            <div class="mx-auto max-w-3xl">

                {{-- Account Overview --}}
                <section>

                    <div class="mb-6">
                        <h2 class="text-2xl font-bold tracking-tight text-irdi-green">
                            Account Overview
                        </h2>

                        <div class="mt-2 h-1 w-16 bg-irdi-gold"></div>

                        <p class="mt-4 text-sm text-zinc-600">
                            Review your IRDI account information and membership details.
                        </p>
                    </div>

                    {{-- Account Information --}}
                    <flux:card class="p-6">

                        <h3 class="text-lg font-semibold text-irdi-green">
                            Account Information
                        </h3>

                        <div class="mt-6 space-y-4">

                            <div>
                                <p class="text-sm font-medium text-zinc-500">
                                    Name
                                </p>

                                <p class="mt-1 text-zinc-900">
                                    {{ auth()->user()->name }}
                                </p>
                            </div>

                            <div>
                                <p class="text-sm font-medium text-zinc-500">
                                    Email Address
                                </p>

                                <p class="mt-1 text-zinc-900">
                                    {{ auth()->user()->email }}
                                </p>
                            </div>

                            <div>
                                <p class="text-sm font-medium text-zinc-500">
                                    Email Status
                                </p>

                                <div class="mt-2">
                                    @if (auth()->user()->hasVerifiedEmail())
                                        <flux:badge color="green">
                                            Verified
                                        </flux:badge>
                                    @else
                                        <flux:badge color="amber">
                                            Verification Required
                                        </flux:badge>
                                    @endif
                                </div>
                            </div>

                        </div>

                    </flux:card>

                    {{-- Membership --}}
                    <flux:card class="mt-6 p-6">

                        <h3 class="text-lg font-semibold text-irdi-green">
                            Membership
                        </h3>

                        <div class="mt-6">

                            <p class="text-sm font-medium text-zinc-500">
                                Membership Status
                            </p>

                            <div class="mt-2">
                                @if (auth()->user()->membership_status === 'active')
                                    <flux:badge color="green">
                                        Active
                                    </flux:badge>
                                @else
                                    <flux:badge color="zinc">
                                        Inactive
                                    </flux:badge>
                                @endif
                            </div>

                            @if (
                                auth()->user()->membership_status === 'active'
                                && auth()->user()->member_since
                            )
                                <div class="mt-6">
                                    <p class="text-sm font-medium text-zinc-500">
                                        Member Since
                                    </p>

                                    <p class="mt-1 text-zinc-900">
                                        {{ auth()->user()->member_since->format('F j, Y') }}
                                    </p>
                                </div>
                            @endif

                        </div>

                        <div class="mt-6 border-t border-zinc-200 pt-6">

                            @if (auth()->user()->membership_status === 'active')

                                <p class="text-sm text-zinc-600">
                                    Your IRDI membership is active.
                                </p>

                            @else

                                <p class="text-sm text-zinc-600">
                                    Complete the membership process to activate your IRDI membership.
                                </p>

                                <div class="mt-4">
                                    <flux:button
                                        href="{{ route('membership.join') }}"
                                        variant="primary"
                                    >
                                        Become an IRDI Member
                                    </flux:button>
                                </div>

                            @endif

                        </div>

                    </flux:card>

                </section>

                {{-- Account Settings --}}
                <section class="mt-10">

                    <div class="mb-6">
                        <h2 class="text-2xl font-bold tracking-tight text-irdi-green">
                            Account Settings
                        </h2>

                        <div class="mt-2 h-1 w-16 bg-irdi-gold"></div>

                        <p class="mt-4 text-sm text-zinc-600">
                            Manage the email address and password associated with your IRDI account.
                            Your account name cannot be changed after registration.
                        </p>
                    </div>

                    <div class="space-y-6">
                        <livewire:account.change-email />

                        <livewire:account.change-password />
                    </div>

                </section>

                {{-- Member Resources --}}
                @if (auth()->user()->membership_status === 'active')
                    <section class="mt-10">

                        <div class="mb-6">
                            <h2 class="text-2xl font-bold tracking-tight text-irdi-green">
                                Member Resources
                            </h2>

                            <div class="mt-2 h-1 w-16 bg-irdi-gold"></div>

                            <p class="mt-4 text-sm text-zinc-600">
                                Review the IRDI standards and guidance that support responsible metal detecting.
                            </p>
                        </div>

                        <div class="grid gap-6 md:grid-cols-2">

                            <flux:card class="p-6">

                                <h3 class="text-lg font-semibold text-irdi-green">
                                    Code of Ethics
                                </h3>

                                <p class="mt-3 text-sm leading-6 text-zinc-600">
                                    Review the ethical principles and responsibilities expected of IRDI members.
                                </p>

                                @if (auth()->user()->ethics_agreed_at)
                                    <p class="mt-3 text-sm font-medium text-green-700">
                                        <span
                                            x-data
                                            x-text="new Intl.DateTimeFormat(undefined, {
        year: 'numeric',
        month: 'long',
        day: 'numeric'
    }).format(new Date('{{ auth()->user()->ethics_agreed_at->toIso8601String() }}'))"
                                        ></span>
                                    </p>
                                @endif

                                <div class="mt-5">
                                    <flux:button
                                        href="{{ route('code-of-ethics') }}"
                                        variant="outline"
                                        icon="book-open"
                                    >
                                        View Code of Ethics
                                    </flux:button>
                                </div>

                            </flux:card>

                            <flux:card class="p-6">

                                <h3 class="text-lg font-semibold text-irdi-green">
                                    Best Practices
                                </h3>

                                <p class="mt-3 text-sm leading-6 text-zinc-600">
                                    Review practical guidance for responsible, respectful, and lawful detecting.
                                </p>

                                @if (auth()->user()->best_practices_agreed_at)
                                    <p class="mt-3 text-sm font-medium text-green-700">
                                        <span
                                            x-data
                                            x-text="new Intl.DateTimeFormat(undefined, {
        year: 'numeric',
        month: 'long',
        day: 'numeric'
    }).format(new Date('{{ auth()->user()->best_practices_agreed_at->toIso8601String() }}'))"
                                        ></span>
                                    </p>
                                @endif

                                <div class="mt-5">
                                    <flux:button
                                        href="{{ route('best-practices') }}"
                                        variant="outline"
                                        icon="book-open"
                                    >
                                        View Best Practices
                                    </flux:button>
                                </div>

                            </flux:card>

                        </div>

                    </section>
                @endif

                {{-- Member Profiles --}}
                <section class="mt-10">

                    <div class="mb-6">
                        <h2 class="text-2xl font-bold tracking-tight text-irdi-green">
                            Member Profiles
                        </h2>

                        <div class="mt-2 h-1 w-16 bg-irdi-gold"></div>

                        <p class="mt-4 text-sm text-zinc-600">
                            Create and manage your Detectorist, Organization, and Vendor profiles.
                        </p>
                    </div>

                    <flux:card class="p-6">

                        @if (auth()->user()->memberProfiles->isNotEmpty())

                            <div class="space-y-4">

                                @foreach (auth()->user()->memberProfiles as $profile)
                                    @php
                                        $missingProfileFields = collect([
                                            'profile image' => filled($profile->profile_image),
                                            'city' => filled($profile->city),
                                            'state/province' => filled($profile->state_province),
                                            'country' => filled($profile->country),
                                            'bio' => filled($profile->bio),
                                        ])->reject();

                                        $profileComplete = $missingProfileFields->isEmpty();
                                    @endphp

                                    <div class="relative rounded-xl border border-zinc-200 p-5">

                                        @if ($profile->profile_type === 'detectorist')

                                            <div
                                                class="z-50 mb-4 lg:absolute lg:right-5 lg:top-5 lg:mb-0"
                                                x-data="{ open: false }"
                                            >

                                                <div class="text-right">

                                                    <button
                                                        type="button"
                                                        class="text-sm font-medium text-irdi-green hover:underline"
                                                        x-on:click="open = ! open"
                                                    >
                                                        Request Property Owner Review
                                                    </button>

                                                </div>

                                                <div
                                                    x-show="open"
                                                    x-cloak
                                                    class="relative z-50 mt-3 w-full rounded-lg border border-zinc-200 bg-white p-4 shadow-lg lg:w-80"
                                                >

                                                    <form
                                                        action="{{ route('account.review-invitations.store', $profile) }}"
                                                        method="POST"
                                                    >
                                                        @csrf

                                                        <label
                                                            for="reviewer_email_{{ $profile->id }}"
                                                            class="block text-sm font-medium text-zinc-700"
                                                        >
                                                            Property Owner Email
                                                        </label>

                                                        <p class="mt-1 text-xs leading-5 text-zinc-500">
                                                            IRDI will email the property owner a private, single-use feedback invitation.
                                                        </p>

                                                        <input
                                                            id="reviewer_email_{{ $profile->id }}"
                                                            name="reviewer_email"
                                                            type="email"
                                                            required
                                                            placeholder="owner@example.com"
                                                            class="mt-3 block w-full rounded-lg border border-zinc-300 px-3 py-2 text-sm text-zinc-900"
                                                        >

                                                        @error('reviewer_email_' . $profile->id)
                                                        <p class="mt-2 text-sm text-red-600">
                                                            {{ $message }}
                                                        </p>
                                                        @enderror

                                                        <div class="mt-4 flex justify-end gap-2">

                                                            <flux:button
                                                                type="button"
                                                                variant="ghost"
                                                                size="sm"
                                                                x-on:click="open = false"
                                                            >
                                                                Cancel
                                                            </flux:button>

                                                            <flux:button
                                                                type="submit"
                                                                variant="primary"
                                                                size="sm"
                                                                icon="envelope"
                                                            >
                                                                Send Invitation
                                                            </flux:button>

                                                        </div>

                                                    </form>

                                                </div>

                                            </div>

                                        @endif

                                        <div class="space-y-5">

                                            {{-- Profile information --}}
                                            <div class="flex min-w-0 items-start gap-4 lg:pr-64">

                                                {{-- Profile image / fallback avatar --}}
                                                <div class="flex h-14 w-14 shrink-0 items-center justify-center overflow-hidden rounded-full bg-irdi-green text-lg font-semibold text-white">

                                                    @if ($profile->profile_image)

                                                        <img
                                                            src="{{ asset('storage/' . $profile->profile_image) }}"
                                                            alt="{{ $profile->profile_name }}"
                                                            class="h-full w-full object-cover"
                                                        >

                                                    @else

                                                        {{ strtoupper(substr($profile->profile_name, 0, 1)) }}

                                                    @endif

                                                </div>

                                                <div class="min-w-0">

                                                    {{-- Profile type --}}
                                                    <flux:badge
                                                        size="sm"
                                                        :color="match ($profile->profile_type) {
                                                            'detectorist' => 'green',
                                                            'organization' => 'blue',
                                                            'vendor' => 'amber',
                                                            default => 'zinc',
                                                        }"
                                                    >
                                                        {{ ucfirst($profile->profile_type) }}
                                                    </flux:badge>

                                                    {{-- Profile name --}}
                                                    <p class="mt-2 text-lg font-semibold text-irdi-green">
                                                        {{ $profile->profile_name }}
                                                    </p>

                                                    {{-- Username --}}
                                                    <p class="mt-1 text-sm text-zinc-600">
                                                        {{ '@' . $profile->username }}
                                                    </p>

                                                    {{-- Location --}}
                                                    @if (
                                                        $profile->city
                                                        || $profile->state_province
                                                        || $profile->country
                                                    )

                                                        <p class="mt-2 text-sm text-zinc-600">
                                                            {{ collect([
                                                                $profile->city,
                                                                $profile->state_province,
                                                                $profile->country,
                                                            ])->filter()->implode(', ') }}
                                                        </p>

                                                    @endif

                                                </div>

                                            </div>

                                            {{-- Profile status and actions --}}
                                            <div>

                                                {{-- Profile status --}}
                                                <div class="mb-3">

                                                    <div class="flex flex-wrap gap-2 lg:justify-end">

                                                        @if ($profile->directory_visible)

                                                            <flux:badge color="green">
                                                                Directory Visible
                                                            </flux:badge>

                                                        @else

                                                            <flux:badge color="zinc">
                                                                Directory Hidden
                                                            </flux:badge>

                                                        @endif

                                                        @if ($profileComplete)

                                                            <flux:badge color="green">
                                                                Profile Complete
                                                            </flux:badge>

                                                        @else

                                                            <flux:badge color="amber">
                                                                Needs Attention
                                                            </flux:badge>

                                                        @endif

                                                    </div>

                                                    @unless ($profileComplete)
                                                        <p class="mt-2 text-sm text-amber-700 lg:text-right">
                                                            Missing: {{ $missingProfileFields->keys()->implode(', ') }}.
                                                        </p>
                                                    @endunless

                                                </div>

                                                {{-- Profile actions --}}
                                                <div class="flex flex-wrap items-center gap-3">

                                                    <flux:button
                                                        href="{{ route('member-profiles.show', ['profile' => $profile->username]) }}"
                                                        variant="ghost"
                                                        size="sm"
                                                        icon="eye"
                                                    >
                                                        View Profile
                                                    </flux:button>

                                                    <flux:button
                                                        :href="route('account.profiles.edit', $profile)"
                                                        variant="outline"
                                                        size="sm"
                                                        icon="pencil-square"
                                                    >
                                                        Edit
                                                    </flux:button>

                                                    <flux:button
                                                        href="{{ route('member-profiles.show', ['profile' => $profile->username]) }}#member-card"
                                                        variant="outline"
                                                        size="sm"
                                                        icon="identification"
                                                    >
                                                        Member Card
                                                    </flux:button>

                                                    @if ($profile->profile_type === 'detectorist')

                                                        <flux:button
                                                            href="{{ route('account.reviews') }}"
                                                            variant="outline"
                                                            size="sm"
                                                            icon="star"
                                                        >
                                                            My Reviews
                                                        </flux:button>

                                                    @endif

                                                    <form
                                                        action="{{ route('account.profiles.destroy', $profile) }}"
                                                        method="POST"
                                                        onsubmit="return confirm('Are you sure you want to permanently delete this profile? This will not delete your IRDI account or membership.');"
                                                    >
                                                        @csrf
                                                        @method('DELETE')

                                                        <flux:button
                                                            type="submit"
                                                            variant="danger"
                                                            size="sm"
                                                            icon="trash"
                                                        >
                                                            Delete
                                                        </flux:button>
                                                    </form>

                                                </div>

                                            </div>

                                        </div>

                                    </div>

                                @endforeach

                            </div>

                        @else

                            <p class="text-sm text-zinc-500">
                                You have not created any IRDI profiles yet.
                            </p>

                        @endif

                        @if (
                            auth()->user()->membership_status === 'active'
                            && auth()->user()->memberProfiles()->count() < 3
                        )

                            @php
                                $existingProfileTypes = auth()->user()
                                    ->memberProfiles()
                                    ->pluck('profile_type')
                                    ->all();

                                $availableProfileTypes = collect([
                                    'detectorist' => 'Detectorist',
                                    'organization' => 'Organization',
                                    'vendor' => 'Vendor',
                                ])->reject(
                                    fn ($label, $type) => in_array($type, $existingProfileTypes)
                                );
                            @endphp

                            <div class="mt-6 border-t border-zinc-200 pt-6">

                                <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">

                                    <div>
                                        <p class="text-sm text-zinc-600">
                                            You can create up to three profiles, but you can only have one of each type:
                                            Detectorist, Organization, and Vendor.
                                        </p>

                                        <div class="mt-3 flex flex-wrap items-center gap-2">

                                            <span class="text-sm font-medium text-zinc-900">
                                                Still available:
                                            </span>

                                            @foreach ($availableProfileTypes as $type => $label)

                                                <flux:badge
                                                    size="sm"
                                                    :color="match ($type) {
                                                        'detectorist' => 'green',
                                                        'organization' => 'blue',
                                                        'vendor' => 'amber',
                                                        default => 'zinc',
                                                    }"
                                                >
                                                    {{ $label }}
                                                </flux:badge>

                                            @endforeach

                                        </div>
                                    </div>

                                    <flux:button
                                        href="{{ route('member-profiles.create') }}"
                                        variant="primary"
                                        icon="plus"
                                    >
                                        Create a Profile
                                    </flux:button>

                                </div>

                            </div>

                        @elseif (auth()->user()->membership_status === 'active')

                            <p class="mt-6 text-sm text-zinc-500">
                                You have created all three available IRDI profile types.
                            </p>

                        @else

                            <p class="mt-6 text-sm text-zinc-500">
                                Activate your IRDI membership before creating a member profile.
                            </p>

                        @endif

                    </flux:card>

                </section>

                {{-- Danger Zone --}}
                <section class="mt-10">

                    <div class="mb-6">
                        <h2 class="text-2xl font-bold tracking-tight text-red-700">
                            Danger Zone
                        </h2>

                        <div class="mt-2 h-1 w-16 bg-red-500"></div>

                        <p class="mt-4 text-sm text-zinc-600">
                            Permanently delete your IRDI account and all associated membership data and profiles.
                        </p>
                    </div>

                    <flux:card class="border-red-200 p-6">

                        <div class="rounded-lg bg-red-50 p-4">
                            <p class="text-sm text-red-800">
                                Deleting your account is permanent. Your IRDI account, membership,
                                and all associated Detectorist, Organization, and Vendor profiles
                                will be deleted.
                            </p>
                        </div>

                        <div class="mt-6">
                            <flux:button
                                href="{{ route('account.delete') }}"
                                variant="danger"
                                icon="trash"
                            >
                                Delete Account
                            </flux:button>
                        </div>

                    </flux:card>

                </section>

            </div>

        </div>
    </section>

</x-layouts.public>
