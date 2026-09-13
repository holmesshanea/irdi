<x-layouts.public
    title="{{ __('Join IRDI | Free Responsible Metal Detecting Membership') }}"
    description="{{ __('Join IRDI for free and become part of an international community committed to responsible metal detecting, ethical stewardship, landowner respect, and protecting our shared history.') }}"
>

    {{-- Membership Hero --}}
    <section class="relative overflow-hidden bg-irdi-green">

        <picture class="absolute inset-0">
            <source
                media="(max-width: 640px)"
                srcset="{{ asset('images/mobile/mobile3.png') }}"
            >

            <img
                src="{{ asset('images/carousel/carousel3.png') }}"
                alt=""
                class="h-full w-full object-cover"
            >
        </picture>

        <div class="absolute inset-0 bg-irdi-green/75"></div>

        <div class="relative mx-auto max-w-7xl px-6 py-20 lg:px-8 lg:py-28">

            <div class="mx-auto max-w-3xl text-center">

                <p class="text-sm font-semibold uppercase tracking-widest text-irdi-gold-light">
                    International Responsible Detectorist Institute
                </p>

                <h1 class="mt-4 text-4xl font-bold tracking-tight text-white sm:text-5xl lg:text-6xl">
                    {{ __('Become an IRDI Member') }}
                </h1>

                <p class="mx-auto mt-6 max-w-2xl text-lg leading-8 text-zinc-200">
                    {{ __('Join a growing international community committed to responsible metal detecting, ethical stewardship, respect for landowners, and protecting our shared history.') }}
                </p>

                @guest
                    <div class="mt-8 flex flex-wrap justify-center gap-3">

                        <flux:button
                            href="{{ route('register') }}"
                            class="bg-irdi-gold text-zinc-950 hover:bg-amber-500"
                        >
                            {{ __('Create an Account') }}
                        </flux:button>

                        <flux:button
                            href="{{ route('login') }}"
                            variant="outline"
                            class="border-white text-white hover:bg-white hover:text-irdi-green"
                        >
                            {{ __('Log In') }}
                        </flux:button>

                    </div>
                @endguest

                @auth
                    <div class="mt-8 flex justify-center">

                        @if (auth()->user()->membership_status === 'active')

                            <flux:button
                                href="{{ route('account') }}"
                                class="bg-irdi-gold text-zinc-950 hover:bg-amber-500"
                            >
                                {{ __('View My Account') }}
                            </flux:button>

                        @else

                            <flux:button
                                href="{{ route('membership.join') }}"
                                class="bg-irdi-gold text-zinc-950 hover:bg-amber-500"
                            >
                                {{ __('Become an IRDI Member') }}
                            </flux:button>

                        @endif

                    </div>
                @endauth

                <p class="mt-4 font-semibold text-white">
                    {{ __('IRDI Membership is completely free.') }}
                </p>

            </div>

        </div>
    </section>

    {{-- How Membership Works --}}
    <section class="bg-zinc-50">
        <div class="mx-auto max-w-7xl px-6 py-16 lg:px-8 lg:py-20">

            <div class="mx-auto max-w-3xl text-center">

                <h2 class="text-3xl font-bold tracking-tight text-irdi-green sm:text-4xl">
                    {{ __('Becoming a Member Is Simple') }}
                </h2>

                <div class="mx-auto mt-4 h-1 w-20 bg-irdi-gold"></div>

                <p class="mt-6 text-lg leading-8 text-zinc-600">
                    {{ __('IRDI membership is built around a simple commitment to responsible metal detecting, ethical stewardship, and respect for our shared history.') }}
                </p>

            </div>

            <div class="mt-12 grid grid-cols-1 gap-8 md:grid-cols-2 lg:grid-cols-4">

                {{-- Step 1 --}}
                <div class="text-center">

                    <div
                        class="mx-auto flex size-12 items-center justify-center rounded-full bg-irdi-green text-lg font-bold text-white"
                    >
                        1
                    </div>

                    <h3 class="mt-5 text-xl font-semibold text-irdi-green">
                        {{ __('Create Your Account') }}
                    </h3>

                    <p class="mt-3 leading-7 text-zinc-600">
                        {{ __('Create a free IRDI account and verify your email address.') }}
                    </p>

                </div>

                {{-- Step 2 --}}
                <div class="text-center">

                    <div
                        class="mx-auto flex size-12 items-center justify-center rounded-full bg-irdi-green text-lg font-bold text-white"
                    >
                        2
                    </div>

                    <h3 class="mt-5 text-xl font-semibold text-irdi-green">
                        {{ __('Read the Code of Ethics') }}
                    </h3>

                    <p class="mt-3 leading-7 text-zinc-600">
                        {{ __('Review the standards IRDI Members agree to follow while metal detecting.') }}
                    </p>

                </div>

                {{-- Step 3 --}}
                <div class="text-center">

                    <div
                        class="mx-auto flex size-12 items-center justify-center rounded-full bg-irdi-green text-lg font-bold text-white"
                    >
                        3
                    </div>

                    <h3 class="mt-5 text-xl font-semibold text-irdi-green">
                        {{ __('Review Best Practices') }}
                    </h3>

                    <p class="mt-3 leading-7 text-zinc-600">
                        {{ __('Learn the responsible detecting practices IRDI encourages members to follow.') }}
                    </p>

                </div>

                {{-- Step 4 --}}
                <div class="text-center">

                    <div
                        class="mx-auto flex size-12 items-center justify-center rounded-full bg-irdi-green text-lg font-bold text-white"
                    >
                        4
                    </div>

                    <h3 class="mt-5 text-xl font-semibold text-irdi-green">
                        {{ __('Activate Your Membership') }}
                    </h3>

                    <p class="mt-3 leading-7 text-zinc-600">
                        {{ __('Confirm that you have read and agree to both standards to activate your IRDI membership.') }}
                    </p>

                </div>

            </div>

        </div>
    </section>

    {{-- Membership Benefits --}}
    <section class="bg-white">
        <div class="mx-auto max-w-7xl px-6 py-16 lg:px-8 lg:py-20">

            <div class="mx-auto max-w-3xl text-center">

                <h2 class="text-3xl font-bold tracking-tight text-irdi-green sm:text-4xl">
                    {{ __('What Your Membership Includes') }}
                </h2>

                <div class="mx-auto mt-4 h-1 w-20 bg-irdi-gold"></div>

                <p class="mt-6 text-lg leading-8 text-zinc-600">
                    {{ __('IRDI membership gives you a way to publicly demonstrate your support for responsible detecting while connecting you with resources and a growing international community.') }}
                </p>

            </div>

            <div class="mt-12 grid grid-cols-1 gap-6 md:grid-cols-2 lg:grid-cols-3">

                <flux:card class="p-8 transition hover:-translate-y-1 hover:shadow-lg">

                    <h3 class="text-xl font-semibold text-irdi-green">
                        {{ __('IRDI Member Profile') }}
                    </h3>

                    <div class="mt-3 h-1 w-full bg-irdi-gold"></div>

                    <p class="mt-5 leading-7 text-zinc-600">
                        {{ __('Receive an IRDI member profile displaying your Member Since date and other membership details, giving you a lasting record of your commitment to IRDI and responsible detecting.') }}
                    </p>

                </flux:card>

                <flux:card class="p-8 transition hover:-translate-y-1 hover:shadow-lg">

                    <h3 class="text-xl font-semibold text-irdi-green">
                        {{ __('Member Directory') }}
                    </h3>

                    <div class="mt-3 h-1 w-full bg-irdi-gold"></div>

                    <p class="mt-5 leading-7 text-zinc-600">
                        {{ __('Members may choose to appear in the public IRDI Member Directory, making it easy for others to confirm membership and connect with participating members.') }}
                    </p>

                </flux:card>

                <flux:card class="p-8 transition hover:-translate-y-1 hover:shadow-lg">

                    <h3 class="text-xl font-semibold text-irdi-green">
                        {{ __('Share Your Commitment') }}
                    </h3>

                    <div class="mt-3 h-1 w-full bg-irdi-gold"></div>

                    <p class="mt-5 leading-7 text-zinc-600">
                        {{ __('Share your IRDI membership with landowners, clubs, organizations, and the public as a visible demonstration of your support for responsible metal detecting.') }}
                    </p>

                </flux:card>

                <flux:card class="p-8 transition hover:-translate-y-1 hover:shadow-lg">

                    <h3 class="text-xl font-semibold text-irdi-green">
                        {{ __('Profile Link & QR Code') }}
                    </h3>

                    <div class="mt-3 h-1 w-full bg-irdi-gold"></div>

                    <p class="mt-5 leading-7 text-zinc-600">
                        {{ __('Receive a unique link to your IRDI profile along with a scannable QR Code that allows others to quickly view and verify your IRDI membership profile.') }}
                    </p>

                </flux:card>

                <flux:card class="p-8 transition hover:-translate-y-1 hover:shadow-lg">

                    <h3 class="text-xl font-semibold text-irdi-green">
                        {{ __('IRDI Brand Use') }}
                    </h3>

                    <div class="mt-3 h-1 w-full bg-irdi-gold"></div>

                    <p class="mt-5 leading-7 text-zinc-600">
                        {{ __('Members may identify themselves as IRDI Members and use approved IRDI member logos on websites, social media, printed materials, and other communications in accordance with IRDI brand guidelines.') }}
                    </p>

                </flux:card>

                <flux:card class="p-8 transition hover:-translate-y-1 hover:shadow-lg">

                    <h3 class="text-xl font-semibold text-irdi-green">
                        {{ __('Member Resources') }}
                    </h3>

                    <div class="mt-3 h-1 w-full bg-irdi-gold"></div>

                    <p class="mt-5 leading-7 text-zinc-600">
                        {{ __('Access educational resources, responsible detecting guides, best practices, and other materials designed to help members represent the hobby responsibly.') }}
                    </p>

                </flux:card>

            </div>

        </div>
    </section>

    {{-- Final Call to Action --}}
    <section class="bg-irdi-green">
        <div class="mx-auto max-w-7xl px-6 pt-16 pb-20 lg:px-8 lg:pt-20 lg:pb-24">

            <div class="mx-auto max-w-3xl text-center">

                <h2 class="text-3xl font-bold tracking-tight text-white sm:text-4xl">
                    {{ __('Ready to Join IRDI?') }}
                </h2>

                <div class="mx-auto mt-4 h-1 w-20 bg-irdi-gold"></div>

                <p class="mt-6 text-lg leading-8 text-zinc-200">
                    {{ __('Create your free membership and make your commitment to responsible metal detecting, ethical stewardship, and protecting our shared history.') }}
                </p>

                @guest
                    <div class="mt-8 flex flex-wrap justify-center gap-3">

                        <flux:button
                            href="{{ route('register') }}"
                            class="bg-irdi-gold text-zinc-950 hover:bg-amber-500"
                        >
                            {{ __('Create an Account') }}
                        </flux:button>

                        <flux:button
                            href="{{ route('login') }}"
                            variant="outline"
                            class="border-white text-white hover:bg-white hover:text-irdi-green"
                        >
                            {{ __('Log In') }}
                        </flux:button>

                    </div>
                @endguest

                @auth
                    <div class="mt-8 flex justify-center">

                        @if (auth()->user()->membership_status === 'active')

                            <flux:button
                                href="{{ route('account') }}"
                                class="bg-irdi-gold text-zinc-950 hover:bg-amber-500"
                            >
                                {{ __('View My Account') }}
                            </flux:button>

                        @else

                            <flux:button
                                href="{{ route('membership.join') }}"
                                class="bg-irdi-gold text-zinc-950 hover:bg-amber-500"
                            >
                                {{ __('Become an IRDI Member') }}
                            </flux:button>

                        @endif

                    </div>
                @endauth

                <p class="mt-4 text-sm text-zinc-300">
                    {{ __('IRDI Membership is completely free.') }}
                </p>

            </div>

        </div>
    </section>

</x-layouts.public>
