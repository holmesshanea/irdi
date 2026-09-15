<x-layouts.public
    title="{{ __('About IRDI | Trust, Ethics & Responsible Metal Detecting') }}"
    description="{{ __('Learn how IRDI connects responsible detectorists, landowners, and the public through shared ethical standards, public membership verification, property owner feedback, and accountability.') }}"
>

    {{-- About Hero --}}
    <section class="relative overflow-hidden bg-irdi-green">
        <picture class="absolute inset-0">
            <source media="(max-width: 640px)" srcset="{{ asset('images/mobile/mobile2.png') }}">
            <img src="{{ asset('images/carousel/carousel2.png') }}" alt="" class="h-full w-full object-cover">
        </picture>
        <div class="absolute inset-0 bg-irdi-green/75"></div>
        <div class="relative mx-auto max-w-7xl px-6 py-20 lg:px-8 lg:py-28">
            <div class="mx-auto max-w-3xl text-center">
                <p class="text-sm font-semibold uppercase tracking-widest text-irdi-gold-light">International Responsible Detectorist Institute</p>
                <h1 class="mt-4 text-4xl font-bold tracking-tight text-white sm:text-5xl lg:text-6xl">{{ __('About IRDI') }}</h1>
                <p class="mx-auto mt-6 max-w-2xl text-lg leading-8 text-zinc-200">
                    {{ __('Metal detecting gives us a unique connection to the past—and with that opportunity comes a responsibility to protect history, respect landowners, and earn the trust that keeps the hobby possible.') }}
                </p>
            </div>
        </div>
    </section>

    {{-- Who We Are --}}
    <section class="bg-zinc-50">
        <div class="mx-auto max-w-7xl px-6 py-16 lg:px-8 lg:py-20">
            <div class="grid grid-cols-1 gap-10 lg:grid-cols-2 lg:items-center">
                <div>
                    <h2 class="text-3xl font-bold tracking-tight text-irdi-green">{{ __('Who We Are') }}</h2>
                    <div class="mt-4 h-1 w-20 bg-irdi-gold"></div>
                </div>
                <div class="space-y-5 text-lg leading-8 text-zinc-600">
                    <p>{{ __('IRDI is an international membership organization dedicated to promoting ethical metal detecting, protecting our shared history, and encouraging responsible detecting through education, best practices, and respect for landowners.') }}</p>
                    <p>{{ __('We also believe responsible conduct should be visible and accountable. IRDI helps connect detectorists, landowners, and the public through shared standards, current membership verification, property owner feedback, and a process for addressing concerns when standards are not upheld.') }}</p>
                </div>
            </div>
        </div>
    </section>

    {{-- Trust Bridge --}}
    <section class="bg-white">
        <div class="mx-auto max-w-7xl px-6 py-16 lg:px-8 lg:py-20">
            <div class="mx-auto max-w-3xl text-center">
                <p class="text-sm font-semibold uppercase tracking-widest text-irdi-gold-text">{{ __('Trust & Accountability') }}</p>
                <h2 class="mt-3 text-3xl font-bold tracking-tight text-irdi-green sm:text-4xl">{{ __('Building a Bridge of Trust') }}</h2>
                <div class="mx-auto mt-4 h-1 w-20 bg-irdi-gold"></div>
                <p class="mt-6 text-lg leading-8 text-zinc-600">{{ __('Membership is a commitment. Trust is earned through conduct and accountability. IRDI provides a framework that helps responsible detectorists demonstrate that commitment while giving landowners and the public a way to verify current membership and available feedback.') }}</p>
            </div>

            <div class="mx-auto mt-12 grid max-w-5xl gap-6 md:grid-cols-3">
                <flux:card class="p-6 transition hover:-translate-y-1 hover:shadow-lg">
                    <div class="flex size-11 items-center justify-center rounded-full bg-irdi-gold/10 text-irdi-green"><flux:icon.document-check class="size-6" /></div>
                    <h3 class="mt-5 text-lg font-semibold text-irdi-green">{{ __('For Detectorists') }}</h3>
                    <p class="mt-3 leading-7 text-zinc-600">{{ __('Members make a clear commitment to the IRDI Code of Ethics and Best Practices and are expected to uphold those standards throughout their membership.') }}</p>
                </flux:card>

                <flux:card class="p-6 transition hover:-translate-y-1 hover:shadow-lg">
                    <div class="flex size-11 items-center justify-center rounded-full bg-irdi-gold/10 text-irdi-green"><flux:icon.home-modern class="size-6" /></div>
                    <h3 class="mt-5 text-lg font-semibold text-irdi-green">{{ __('For Landowners') }}</h3>
                    <p class="mt-3 leading-7 text-zinc-600">{{ __('Public member profiles provide current membership standing and, when available, property owner feedback. IRDI also provides a confidential way for invited reviewers to raise concerns.') }}</p>
                </flux:card>

                <flux:card class="p-6 transition hover:-translate-y-1 hover:shadow-lg">
                    <div class="flex size-11 items-center justify-center rounded-full bg-irdi-gold/10 text-irdi-green"><flux:icon.shield-check class="size-6" /></div>
                    <h3 class="mt-5 text-lg font-semibold text-irdi-green">{{ __('For the Public') }}</h3>
                    <p class="mt-3 leading-7 text-zinc-600">{{ __('The IRDI Member Directory provides a public way to confirm a member’s current standing and view the information that member has chosen to make public.') }}</p>
                </flux:card>
            </div>

            <div class="mx-auto mt-8 max-w-4xl rounded-xl border border-zinc-200 bg-zinc-50 p-5 text-center">
                <p class="text-sm leading-6 text-zinc-500">{{ __('IRDI membership standing is a current organizational status. It is not a background check, endorsement, or guarantee of future conduct.') }}</p>
            </div>
        </div>
    </section>

    {{-- Our Mission --}}
    <section class="bg-irdi-green">
        <div class="mx-auto max-w-7xl px-6 py-16 lg:px-8 lg:py-20">
            <div class="mx-auto max-w-4xl text-center">
                <h2 class="text-3xl font-bold tracking-tight text-white sm:text-4xl">{{ __('Our Mission') }}</h2>
                <div class="mx-auto mt-4 h-1 w-20 bg-irdi-gold"></div>
                <p class="mt-8 text-xl leading-9 text-white">{{ __('Our mission is to promote responsible metal detecting through education, ethical stewardship, respect for landowners, accountability, and a commitment to protecting our shared history for future generations.') }}</p>
            </div>
            <div class="mx-auto mt-14 max-w-4xl divide-y divide-white/20">
                <div class="grid gap-4 py-8 sm:grid-cols-[80px_1fr]">
                    <div class="text-3xl font-bold text-irdi-gold-light">01</div>
                    <div><h3 class="text-xl font-semibold text-white">{{ __('Protect Our Shared History') }}</h3><p class="mt-3 leading-7 text-zinc-200">{{ __('Every artifact tells a story. IRDI encourages responsible recovery and thoughtful stewardship so that today’s discoveries can continue to educate and inspire future generations.') }}</p></div>
                </div>
                <div class="grid gap-4 py-8 sm:grid-cols-[80px_1fr]">
                    <div class="text-3xl font-bold text-irdi-gold-light">02</div>
                    <div><h3 class="text-xl font-semibold text-white">{{ __('Respect Landowners') }}</h3><p class="mt-3 leading-7 text-zinc-200">{{ __('Responsible detectorists earn trust through honesty, courtesy, and respect for private property. Every hunt begins with permission and ends with leaving the land exactly as it should be—or better.') }}</p></div>
                </div>
                <div class="grid gap-4 py-8 sm:grid-cols-[80px_1fr]">
                    <div class="text-3xl font-bold text-irdi-gold-light">03</div>
                    <div><h3 class="text-xl font-semibold text-white">{{ __('Lead by Example') }}</h3><p class="mt-3 leading-7 text-zinc-200">{{ __('IRDI Members demonstrate that responsible metal detecting is about more than finding artifacts. It is about integrity, education, accountability, and helping build a positive future for the hobby through ethical practices.') }}</p></div>
                </div>
            </div>
        </div>
    </section>

    {{-- Closing Call to Action --}}
    <section class="bg-white">
        <div class="mx-auto max-w-7xl px-6 py-16 lg:px-8 lg:py-20">
            <div class="mx-auto max-w-4xl text-center">
                <h2 class="text-3xl font-bold tracking-tight text-irdi-green sm:text-4xl">{{ __('Become Part of Something Bigger') }}</h2>
                <div class="mx-auto mt-4 h-1 w-20 bg-irdi-gold"></div>
                <p class="mt-6 text-lg leading-8 text-zinc-600">{{ __('Every responsible detectorist has the power to make a difference. By joining IRDI, you’re making a public commitment to ethical detecting, respect for landowners, accountability, and the protection of our shared history.') }}</p>
                <div class="mt-8 flex flex-col items-center justify-center gap-4 sm:flex-row">
                    <flux:button href="{{ route('membership') }}" variant="primary">{{ __('Become a Member') }}</flux:button>
                    <flux:button href="{{ route('directory') }}" variant="ghost">{{ __('Verify a Member') }}</flux:button>
                </div>
            </div>
        </div>
    </section>

</x-layouts.public>
