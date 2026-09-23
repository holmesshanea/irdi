<x-layouts.public
    title="{{ __('Join IRDI | Free Responsible Metal Detecting Membership') }}"
    description="{{ __('Join IRDI for free, commit to responsible metal detecting standards, and participate in a membership system built around public verification, good standing, property owner feedback, and accountability.') }}"
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
                    {{ __('Make a public commitment to responsible metal detecting and become part of a membership system built around shared standards, current standing, property owner feedback, and accountability.') }}
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
                    {{ __("Joining IRDI begins with a clear commitment to responsible metal detecting. Members verify their email address and agree to IRDI's Code of Ethics and Best Practices before membership is activated.") }}
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
                        {{ __('Confirm that you have read and agree to both standards to activate your membership and begin as a Member in Good Standing.') }}
                    </p>

                </div>

            </div>

        </div>
    </section>

    {{-- Membership & Good Standing --}}
    <section class="bg-white">
        <div class="mx-auto max-w-7xl px-6 py-16 lg:px-8 lg:py-20">

            <div class="mx-auto max-w-3xl text-center">
                <p class="text-sm font-semibold uppercase tracking-widest text-irdi-gold-text">
                    {{ __('Trust & Accountability') }}
                </p>

                <h2 class="mt-3 text-3xl font-bold tracking-tight text-irdi-green sm:text-4xl">
                    {{ __('Membership Is a Commitment. Good Standing Is Maintained.') }}
                </h2>

                <div class="mx-auto mt-4 h-1 w-20 bg-irdi-gold"></div>

                <p class="mt-6 text-lg leading-8 text-zinc-600">
                    {{ __('Becoming an IRDI member means agreeing to our standards. Remaining a Member in Good Standing means continuing to meet IRDI’s membership and accountability requirements.') }}
                </p>
            </div>

            <div class="mx-auto mt-12 grid max-w-5xl grid-cols-1 gap-6 md:grid-cols-3">
                <flux:card class="p-7 transition hover:-translate-y-1 hover:shadow-lg">
                    <div class="flex size-10 items-center justify-center rounded-full bg-irdi-gold/10 text-irdi-green">
                        <flux:icon.document-check class="size-5" />
                    </div>
                    <h3 class="mt-5 text-lg font-semibold text-irdi-green">
                        {{ __('Commit to the Standards') }}
                    </h3>
                    <p class="mt-3 leading-7 text-zinc-600">
                        {{ __('Members agree to IRDI’s Code of Ethics and Best Practices as the standards that guide responsible participation.') }}
                    </p>
                </flux:card>

                <flux:card class="p-7 transition hover:-translate-y-1 hover:shadow-lg">
                    <div class="flex size-10 items-center justify-center rounded-full bg-irdi-gold/10 text-irdi-green">
                        <flux:icon.shield-check class="size-5" />
                    </div>
                    <h3 class="mt-5 text-lg font-semibold text-irdi-green">
                        {{ __('Maintain Good Standing') }}
                    </h3>
                    <p class="mt-3 leading-7 text-zinc-600">
                        {{ __('Good Standing reflects a member’s current status with IRDI and continued compliance with IRDI’s membership and accountability requirements.') }}
                    </p>
                </flux:card>

                <flux:card class="p-7 transition hover:-translate-y-1 hover:shadow-lg">
                    <div class="flex size-10 items-center justify-center rounded-full bg-irdi-gold/10 text-irdi-green">
                        <flux:icon.chat-bubble-left-right class="size-5" />
                    </div>
                    <h3 class="mt-5 text-lg font-semibold text-irdi-green">
                        {{ __('Participate in Accountability') }}
                    </h3>
                    <p class="mt-3 leading-7 text-zinc-600">
                        {{ __('IRDI may consider property owner feedback, reported concerns, and other relevant information through its moderation and accountability processes.') }}
                    </p>
                </flux:card>
            </div>

            <div class="mx-auto mt-8 max-w-5xl rounded-xl border border-zinc-200 bg-zinc-50 p-5">
                <p class="text-sm leading-6 text-zinc-600">
                    {{ __('Good Standing is a statement of a member’s current IRDI status. It is not a background check, endorsement, or guarantee of future conduct. Membership standing may change when IRDI’s accountability process requires action.') }}
                </p>
            </div>

        </div>
    </section>

    {{-- Membership Benefits --}}
    <section class="bg-zinc-50">
        <div class="mx-auto max-w-7xl px-6 py-16 lg:px-8 lg:py-20">

            <div class="mx-auto max-w-3xl text-center">

                <h2 class="text-3xl font-bold tracking-tight text-irdi-green sm:text-4xl">
                    {{ __('What Your Membership Includes') }}
                </h2>

                <div class="mx-auto mt-4 h-1 w-20 bg-irdi-gold"></div>

                <p class="mt-6 text-lg leading-8 text-zinc-600">
                    {{ __('IRDI membership provides public verification of your current membership and standing, tools for sharing your commitment, access to member resources, and participation in a growing international community.') }}
                </p>

            </div>

            <div class="mt-12 grid grid-cols-1 gap-6 md:grid-cols-2 lg:grid-cols-3">

                <flux:card class="p-8 transition hover:-translate-y-1 hover:shadow-lg">

                    <h3 class="text-xl font-semibold text-irdi-green">
                        {{ __('IRDI Member Profile') }}
                    </h3>

                    <div class="mt-3 h-1 w-full bg-irdi-gold"></div>

                    <p class="mt-5 leading-7 text-zinc-600">
                        {{ __('Receive a public IRDI member profile that can display your current Good Standing status, Member Since date, membership details, and available property owner feedback.') }}
                    </p>

                </flux:card>

                <flux:card class="p-8 transition hover:-translate-y-1 hover:shadow-lg">

                    <h3 class="text-xl font-semibold text-irdi-green">
                        {{ __('Member Directory') }}
                    </h3>

                    <div class="mt-3 h-1 w-full bg-irdi-gold"></div>

                    <p class="mt-5 leading-7 text-zinc-600">
                        {{ __('privacy.directory_intro') }}
                    </p>

                </flux:card>

                <flux:card class="p-8 transition hover:-translate-y-1 hover:shadow-lg">

                    <h3 class="text-xl font-semibold text-irdi-green">
                        {{ __('Share Your Commitment') }}
                    </h3>

                    <div class="mt-3 h-1 w-full bg-irdi-gold"></div>

                    <p class="mt-5 leading-7 text-zinc-600">
                        {{ __('Share your IRDI profile with landowners, clubs, organizations, and the public so they can view your current membership standing and your commitment to responsible metal detecting.') }}
                    </p>

                </flux:card>

                <flux:card class="p-8 transition hover:-translate-y-1 hover:shadow-lg">

                    <h3 class="text-xl font-semibold text-irdi-green">
                        {{ __('Profile Link & QR Code') }}
                    </h3>

                    <div class="mt-3 h-1 w-full bg-irdi-gold"></div>

                    <p class="mt-5 leading-7 text-zinc-600">
                        {{ __('Receive a unique profile link and scannable QR code that directs others to your live IRDI profile, where they can verify your current membership and standing.') }}
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
                    {{ __('Create your free membership, commit to IRDI’s standards, and begin building a public record of responsible participation and accountability.') }}
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
