<!DOCTYPE html>

<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <title>{{ $title ?? 'IRDI' }}</title>

    @vite(['resources/css/app.css', 'resources/js/app.js'])

    @livewireStyles

    @fluxAppearance
</head>

<body class="min-h-screen bg-white text-zinc-900 antialiased">

<flux:header
    container
    class="border-b border-zinc-200 bg-white"
>

    {{-- Mobile hamburger menu --}}
    <flux:sidebar.toggle
        class="lg:hidden"
        icon="bars-2"
        inset="left"
    />

    <a
        href="{{ route('home') }}"
        class="flex items-center gap-2"
    >
        <img
            src="{{ asset('irdi-logo.png') }}"
            alt="IRDI logo"
            class="h-9 w-9 rounded-full object-contain lg:h-14 lg:w-14"
        >

        <span class="text-lg font-bold text-irdi-green lg:text-xl">
            IRDI
        </span>
    </a>

    <flux:spacer />

    {{-- Desktop navigation --}}
    <flux:navbar class="-mb-px max-lg:hidden">

        <flux:navbar.item
            href="{{ route('home') }}"
            :current="request()->routeIs('home')"
        >
            Home
        </flux:navbar.item>

        <flux:navbar.item
            href="{{ route('about') }}"
            :current="request()->routeIs('about')"
        >
            About
        </flux:navbar.item>

        <flux:navbar.item
            href="{{ route('membership') }}"
            :current="request()->routeIs('membership')"
        >
            Membership
        </flux:navbar.item>

        <flux:navbar.item
            href="{{ route('directory') }}"
            :current="request()->routeIs('directory')"
        >
            Directory
        </flux:navbar.item>

        <flux:navbar.item
            href="{{ route('faq') }}"
            :current="request()->routeIs('faq')"
        >
            FAQ
        </flux:navbar.item>

        <flux:navbar.item
            href="{{ route('contact') }}"
            :current="request()->routeIs('contact')"
        >
            Contact
        </flux:navbar.item>

        {{-- Guest navigation --}}
        @guest

            <div class="ml-3 border-l border-zinc-300 pl-3">

                <flux:navbar.item
                    href="{{ route('login') }}"
                    :current="request()->routeIs('login')"
                >
                    Log In
                </flux:navbar.item>

            </div>

            <flux:navbar.item
                href="{{ route('register') }}"
                :current="request()->routeIs('register')"
            >
                Create Account
            </flux:navbar.item>

        @endguest

        {{-- Authenticated navigation --}}
        @auth

            @if (auth()->user()->is_admin)

                @php
                    $pendingReviewCount = \App\Models\PropertyReview::query()
                        ->whereNull('admin_reviewed_at')
                        ->count();

                    $pendingMessageReportCount = \App\Models\MessageReport::query()
                        ->where('status', 'pending')
                        ->count();
                @endphp

                <div
                    class="relative ml-3 border-l border-zinc-300 pl-3"
                    x-data="{ open: false }"
                >

                    <button
                        type="button"
                        class="inline-flex items-center gap-1 px-3 py-2 text-sm font-medium text-zinc-700 transition hover:text-irdi-green"
                        x-on:click="open = ! open"
                        x-on:click.outside="open = false"
                    >
                        Admin

                        <svg
                            viewBox="0 0 20 20"
                            fill="currentColor"
                            aria-hidden="true"
                            class="h-4 w-4"
                        >
                            <path
                                fill-rule="evenodd"
                                d="M5.23 7.21a.75.75 0 0 1 1.06.02L10 11.168l3.71-3.938a.75.75 0 1 1 1.08 1.04l-4.25 4.5a.75.75 0 0 1-1.08 0l-4.25-4.5a.75.75 0 0 1 .02-1.06Z"
                                clip-rule="evenodd"
                            />
                        </svg>
                    </button>

                    <div
                        x-show="open"
                        x-cloak
                        class="absolute right-0 z-50 mt-2 w-52 rounded-lg border border-zinc-200 bg-white py-2 shadow-lg"
                    >
                        <a
                            href="{{ route('admin.members.index') }}"
                            class="flex items-center justify-between gap-4 px-4 py-2 text-sm text-zinc-700 transition hover:bg-zinc-50 hover:text-irdi-green"
                        >
                            <span>Member Management</span>
                        </a>

                        <a
                            href="{{ route('admin.reviews.index') }}"
                            class="flex items-center justify-between gap-4 px-4 py-2 text-sm text-zinc-700 transition hover:bg-zinc-50 hover:text-irdi-green"
                        >
                            <span>Moderate Reviews</span>

                            @if ($pendingReviewCount > 0)
                                <span class="inline-flex min-w-5 items-center justify-center rounded-full bg-amber-100 px-1.5 py-0.5 text-xs font-semibold text-amber-800">
                                    {{ $pendingReviewCount }}
                                </span>
                            @endif
                        </a>

                        <a
                            href="{{ route('admin.message-reports.index') }}"
                            class="flex items-center justify-between gap-4 px-4 py-2 text-sm text-zinc-700 transition hover:bg-zinc-50 hover:text-irdi-green"
                        >
                            <span>Message Reports</span>

                            @if ($pendingMessageReportCount > 0)
                                <span class="inline-flex min-w-5 items-center justify-center rounded-full bg-red-100 px-1.5 py-0.5 text-xs font-semibold text-red-800">
                                    {{ $pendingMessageReportCount }}
                                </span>
                            @endif
                        </a>
                    </div>

                </div>

            @endif

            <div class="{{ auth()->user()->is_admin ? '' : 'ml-3 border-l border-zinc-300 pl-3' }}">

                <flux:navbar.item
                    href="{{ route('account') }}"
                    :current="request()->routeIs('account') || request()->routeIs('account.*')"
                    icon="user-circle"
                >
                    Account
                </flux:navbar.item>

            </div>

            <form method="POST" action="{{ route('logout') }}">
                @csrf

                <flux:navbar.item
                    as="button"
                    type="submit"
                    icon="arrow-right-start-on-rectangle"
                >
                    Log Out
                </flux:navbar.item>
            </form>

        @endauth

    </flux:navbar>

</flux:header>

{{-- Mobile navigation --}}
<flux:sidebar
    sticky
    collapsible="mobile"
    class="lg:hidden border-r border-zinc-200 bg-white"
>

    <flux:sidebar.header>

        <a
            href="{{ route('home') }}"
            class="flex items-center gap-3"
        >
            <img
                src="{{ asset('irdi-logo.png') }}"
                alt="IRDI logo"
                class="h-12 w-12 rounded-full object-contain"
            >

            <div class="min-w-0">
                <div class="font-bold text-irdi-green">
                    IRDI
                </div>

                <div class="mt-0.5 text-xs leading-4 text-zinc-500">
                    International Responsible Detectorist Institute
                </div>
            </div>
        </a>

        <flux:sidebar.collapse class="lg:hidden" />

    </flux:sidebar.header>

    <flux:sidebar.nav>

        <flux:sidebar.item
            href="{{ route('home') }}"
            :current="request()->routeIs('home')"
        >
            Home
        </flux:sidebar.item>

        <flux:sidebar.item
            href="{{ route('about') }}"
            :current="request()->routeIs('about')"
        >
            About
        </flux:sidebar.item>

        <flux:sidebar.item
            href="{{ route('membership') }}"
            :current="request()->routeIs('membership')"
        >
            Membership
        </flux:sidebar.item>

        <flux:sidebar.item
            href="{{ route('directory') }}"
            :current="request()->routeIs('directory')"
        >
            Directory
        </flux:sidebar.item>

        <flux:sidebar.item
            href="{{ route('faq') }}"
            :current="request()->routeIs('faq')"
        >
            FAQ
        </flux:sidebar.item>

        <flux:sidebar.item
            href="{{ route('contact') }}"
            :current="request()->routeIs('contact')"
        >
            Contact
        </flux:sidebar.item>

        {{-- Guest navigation --}}
        @guest

            <div class="my-4 border-t border-zinc-200"></div>

            <div class="px-3 pb-2 text-xs font-semibold uppercase tracking-wide text-zinc-500">
                Account
            </div>

            <flux:sidebar.item
                href="{{ route('login') }}"
                :current="request()->routeIs('login')"
            >
                Log In
            </flux:sidebar.item>

            <flux:sidebar.item
                href="{{ route('register') }}"
                :current="request()->routeIs('register')"
            >
                Create Account
            </flux:sidebar.item>

        @endguest

        {{-- Authenticated navigation --}}
        @auth

            @if (auth()->user()->is_admin)

                <div class="my-4 border-t border-zinc-200"></div>

                <div class="px-3 pb-2 text-xs font-semibold uppercase tracking-wide text-zinc-500">
                    Admin
                </div>

                <flux:sidebar.item
                    href="{{ route('admin.members.index') }}"
                    :current="request()->routeIs('admin.members.*')"
                    icon="users"
                >
                    Member Management
                </flux:sidebar.item>

                <flux:sidebar.item
                    href="{{ route('admin.reviews.index') }}"
                    :current="request()->routeIs('admin.reviews.*')"
                    icon="shield-check"
                >
                    <div class="flex w-full items-center justify-between gap-3">
                        <span>Moderate Reviews</span>

                        @if ($pendingReviewCount > 0)
                            <span class="inline-flex min-w-5 items-center justify-center rounded-full bg-amber-100 px-1.5 py-0.5 text-xs font-semibold text-amber-800">
                                {{ $pendingReviewCount }}
                            </span>
                        @endif
                    </div>
                </flux:sidebar.item>

                <flux:sidebar.item
                    href="{{ route('admin.message-reports.index') }}"
                    :current="request()->routeIs('admin.message-reports.*')"
                    icon="flag"
                >
                    <div class="flex w-full items-center justify-between gap-3">
                        <span>Message Reports</span>

                        @if ($pendingMessageReportCount > 0)
                            <span class="inline-flex min-w-5 items-center justify-center rounded-full bg-red-100 px-1.5 py-0.5 text-xs font-semibold text-red-800">
                                {{ $pendingMessageReportCount }}
                            </span>
                        @endif
                    </div>
                </flux:sidebar.item>

            @endif

            <div class="my-4 border-t border-zinc-200"></div>

            <div class="px-3 pb-2 text-xs font-semibold uppercase tracking-wide text-zinc-500">
                Your Account
            </div>

            <flux:sidebar.item
                href="{{ route('account') }}"
                :current="request()->routeIs('account') || request()->routeIs('account.*')"
                icon="user-circle"
            >
                Account
            </flux:sidebar.item>

            <form method="POST" action="{{ route('logout') }}">
                @csrf

                <flux:sidebar.item
                    as="button"
                    type="submit"
                    icon="arrow-right-start-on-rectangle"
                >
                    Log Out
                </flux:sidebar.item>
            </form>

        @endauth

    </flux:sidebar.nav>

</flux:sidebar>

{{-- Main content --}}
<flux:main container class="py-12">
    {{ $slot }}
</flux:main>

{{-- Footer --}}
<footer class="[grid-area:footer] border-t border-zinc-200 bg-zinc-50">

    <div class="mx-auto max-w-7xl px-6 py-10 lg:px-8">

        <div class="grid gap-8 lg:grid-cols-3 lg:items-start">

            {{-- IRDI identity --}}
            <div>

                <div class="flex flex-col gap-4 sm:flex-row sm:items-center">

                    <a
                        href="{{ route('home') }}"
                        class="shrink-0"
                    >
                        <img
                            src="{{ asset('irdi-logo.png') }}"
                            alt="IRDI logo"
                            class="h-20 w-20 rounded-full object-contain"
                        >
                    </a>

                    <div>
                        <p class="text-lg font-bold text-irdi-green">
                            IRDI
                        </p>

                        <p class="mt-1 text-sm text-zinc-600">
                            The International Responsible Detectorist Institute
                        </p>
                    </div>

                </div>

                <p class="mt-4 max-w-sm text-sm leading-6 text-zinc-500">
                    Promoting ethical metal detecting, responsible stewardship,
                    and respect for our shared history.
                </p>

            </div>

            {{-- Footer navigation --}}
            <div>
                <p class="text-sm font-semibold text-zinc-900">
                    Explore
                </p>

                <nav class="mt-4 grid grid-cols-2 gap-x-6 gap-y-3 text-sm">

                    <a
                        href="{{ route('home') }}"
                        class="text-zinc-600 transition hover:text-irdi-green"
                    >
                        Home
                    </a>

                    <a
                        href="{{ route('about') }}"
                        class="text-zinc-600 transition hover:text-irdi-green"
                    >
                        About
                    </a>

                    <a
                        href="{{ route('membership') }}"
                        class="text-zinc-600 transition hover:text-irdi-green"
                    >
                        Membership
                    </a>

                    <a
                        href="{{ route('directory') }}"
                        class="text-zinc-600 transition hover:text-irdi-green"
                    >
                        Directory
                    </a>

                    <a
                        href="{{ route('faq') }}"
                        class="text-zinc-600 transition hover:text-irdi-green"
                    >
                        FAQ
                    </a>

                    <a
                        href="{{ route('contact') }}"
                        class="text-zinc-600 transition hover:text-irdi-green"
                    >
                        Contact
                    </a>

                </nav>
            </div>

            {{-- Social media --}}
            <div>
                <p class="text-sm font-semibold text-zinc-900">
                    Follow IRDI
                </p>

                <div class="mt-4 flex items-center gap-4">

                    {{-- Facebook --}}
                    <a
                        href="#"
                        target="_blank"
                        rel="noopener noreferrer"
                        aria-label="Facebook"
                        class="text-zinc-500 transition hover:text-irdi-green"
                    >
                        <svg
                            viewBox="0 0 24 24"
                            aria-hidden="true"
                            class="size-5 fill-current"
                        >
                            <path d="M13.5 22v-9h3l.5-3h-3.5V8.1c0-.9.3-1.6 1.8-1.6H17V3.8c-.3 0-1.4-.1-2.6-.1-2.6 0-4.4 1.6-4.4 4.5V10H7v3h3v9h3.5Z"/>
                        </svg>
                    </a>

                    {{-- Instagram --}}
                    <a
                        href="#"
                        target="_blank"
                        rel="noopener noreferrer"
                        aria-label="Instagram"
                        class="text-zinc-500 transition hover:text-irdi-green"
                    >
                        <svg
                            viewBox="0 0 24 24"
                            aria-hidden="true"
                            class="size-5 fill-current"
                        >
                            <path d="M7.8 2h8.4A5.8 5.8 0 0 1 22 7.8v8.4a5.8 5.8 0 0 1-5.8 5.8H7.8A5.8 5.8 0 0 1 2 16.2V7.8A5.8 5.8 0 0 1 7.8 2Zm0 2A3.8 3.8 0 0 0 4 7.8v8.4A3.8 3.8 0 0 0 7.8 20h8.4a3.8 3.8 0 0 0 3.8-3.8V7.8A3.8 3.8 0 0 0 16.2 4H7.8Zm8.7 1.5a1.3 1.3 0 1 1 0 2.6 1.3 1.3 0 0 1 0-2.6ZM12 7a5 5 0 1 1 0 10 5 5 0 0 1 0-10Zm0 2a3 3 0 1 0 0 6 3 3 0 0 0 0-6Z"/>
                        </svg>
                    </a>

                    {{-- YouTube --}}
                    <a
                        href="#"
                        target="_blank"
                        rel="noopener noreferrer"
                        aria-label="YouTube"
                        class="text-zinc-500 transition hover:text-irdi-green"
                    >
                        <svg
                            viewBox="0 0 24 24"
                            aria-hidden="true"
                            class="size-5 fill-current"
                        >
                            <path d="M21.6 7.2a2.9 2.9 0 0 0-2-2C17.8 4.7 12 4.7 12 4.7s-5.8 0-7.6.5a2.9 2.9 0 0 0-2 2A30.4 30.4 0 0 0 2 12a30.4 30.4 0 0 0 .4 4.8 2.9 2.9 0 0 0 2 2c1.8.5 7.6.5 7.6.5s5.8 0 7.6-.5a2.9 2.9 0 0 0 2-2A30.4 30.4 0 0 0 22 12a30.4 30.4 0 0 0-.4-4.8ZM10 15.2V8.8l5.3 3.2L10 15.2Z"/>
                        </svg>
                    </a>

                    {{-- X --}}
                    <a
                        href="#"
                        target="_blank"
                        rel="noopener noreferrer"
                        aria-label="X"
                        class="text-zinc-500 transition hover:text-irdi-green"
                    >
                        <svg
                            viewBox="0 0 24 24"
                            aria-hidden="true"
                            class="size-5 fill-current"
                        >
                            <path d="M18.2 2H22l-8.3 9.5L23.5 22h-7.7l-6-7.8L3 22H-.8l8.8-10.1L-1.4 2h7.9l5.4 7.2L18.2 2Zm-1.3 18h2.1L5.3 3.9H3.1L16.9 20Z"/>
                        </svg>
                    </a>

                </div>
            </div>

        </div>

        <div class="mt-8 border-t border-zinc-200 pt-6">

            <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">

                <p class="text-sm text-zinc-500">
                    &copy; {{ date('Y') }} IRDI. All rights reserved.
                </p>

                <nav
                    class="flex flex-wrap items-center gap-x-5 gap-y-2 text-sm"
                    aria-label="Legal"
                >
                    <a
                        href="{{ route('privacy-policy') }}"
                        class="text-zinc-500 transition hover:text-irdi-green"
                    >
                        Privacy Policy
                    </a>

                    <a
                        href="{{ route('terms') }}"
                        class="text-zinc-500 transition hover:text-irdi-green"
                    >
                        Terms
                    </a>
                </nav>

            </div>

        </div>

    </div>

</footer>

@livewireScripts
@fluxScripts

</body>

</html>
