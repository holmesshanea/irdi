<x-layouts.public
    title="{{ __('Resources | IRDI') }}"
    description="{{ __('Member resources from the International Responsible Detectorist Institute, including ethical standards, best practices, educational materials, and responsible detecting guidance.') }}"
>

    {{-- Resources Hero --}}
    <section class="relative overflow-hidden bg-irdi-green">

        <picture class="absolute inset-0">
            <source
                media="(max-width: 640px)"
                srcset="{{ asset('images/mobile/mobile8.png') }}"
            >

            <img
                src="{{ asset('images/carousel/carousel8.png') }}"
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
                    {{ __('Resources') }}
                </h1>

                <p class="mx-auto mt-6 max-w-2xl text-lg leading-8 text-zinc-200">
                    {{ __('Explore IRDI standards, accountability guidance, and educational resources that support ethical, responsible, and informed metal detecting.') }}
                </p>

            </div>

        </div>
    </section>

    <section class="bg-white">
        <div class="mx-auto max-w-7xl px-6 py-12 lg:px-8">

            <div class="mx-auto max-w-5xl">

                <div class="mb-8">

                    <h2 class="text-2xl font-bold tracking-tight text-irdi-green">
                        {{ __('IRDI Resources') }}
                    </h2>

                    <div class="mt-2 h-1 w-16 bg-irdi-gold"></div>

                    <p class="mt-4 text-zinc-600">
                        {{ __('Standards and guidance that help IRDI members understand their commitments, maintain Good Standing, and practice responsible detecting.') }}
                    </p>

                </div>

                <div class="grid gap-6 md:grid-cols-2">

                    {{-- Code of Ethics --}}
                    <flux:card
                        class="flex h-full flex-col p-6 transition duration-200 hover:-translate-y-0.5 hover:shadow-lg"
                    >

                        <div class="flex size-11 items-center justify-center rounded-lg bg-irdi-green/10 text-irdi-green">
                            <flux:icon.book-open class="size-6" />
                        </div>

                        <h3 class="mt-5 text-xl font-semibold text-irdi-green">
                            {{ __('Code of Ethics for Responsible Detecting') }}
                        </h3>

                        <p class="mt-3 flex-1 text-sm leading-6 text-zinc-600">
                            {{ __('Review the ethical principles and responsibilities expected of every IRDI member and responsible detectorist.') }}
                        </p>

                        <div class="mt-6">
                            <flux:button
                                href="{{ route('code-of-ethics') }}"
                                variant="outline"
                                icon="book-open"
                            >
                                {{ __('View Code of Ethics') }}
                            </flux:button>
                        </div>

                    </flux:card>

                    {{-- Best Practices --}}
                    <flux:card
                        class="flex h-full flex-col p-6 transition duration-200 hover:-translate-y-0.5 hover:shadow-lg"
                    >

                        <div class="flex size-11 items-center justify-center rounded-lg bg-irdi-green/10 text-irdi-green">
                            <flux:icon.check-badge class="size-6" />
                        </div>

                        <h3 class="mt-5 text-xl font-semibold text-irdi-green">
                            {{ __('Best Practices of Responsible Detecting') }}
                        </h3>

                        <p class="mt-3 flex-1 text-sm leading-6 text-zinc-600">
                            {{ __('Practical guidance for detecting responsibly, respecting property, protecting history, and representing the hobby well.') }}
                        </p>

                        <div class="mt-6">
                            <flux:button
                                href="{{ route('best-practices') }}"
                                variant="outline"
                                icon="book-open"
                            >
                                {{ __('View Best Practices') }}
                            </flux:button>
                        </div>

                    </flux:card>

                    {{-- Membership and Good Standing --}}
                    <flux:card
                        class="flex h-full flex-col p-6 transition duration-200 hover:-translate-y-0.5 hover:shadow-lg"
                    >
                        <div class="flex size-11 items-center justify-center rounded-lg bg-irdi-green/10 text-irdi-green">
                            <flux:icon.shield-check class="size-6" />
                        </div>

                        <h3 class="mt-5 text-xl font-semibold text-irdi-green">
                            {{ __('Membership & Good Standing') }}
                        </h3>

                        <p class="mt-3 flex-1 text-sm leading-6 text-zinc-600">
                            {{ __('Learn how IRDI membership works, what Member in Good Standing means, and how accountability supports responsible membership.') }}
                        </p>

                        <div class="mt-6">
                            <flux:button
                                href="{{ route('membership') }}"
                                variant="outline"
                                icon="shield-check"
                            >
                                {{ __('Learn About Membership') }}
                            </flux:button>
                        </div>
                    </flux:card>

                    {{-- Member Verification --}}
                    <flux:card
                        class="flex h-full flex-col p-6 transition duration-200 hover:-translate-y-0.5 hover:shadow-lg"
                    >
                        <div class="flex size-11 items-center justify-center rounded-lg bg-irdi-green/10 text-irdi-green">
                            <flux:icon.identification class="size-6" />
                        </div>

                        <h3 class="mt-5 text-xl font-semibold text-irdi-green">
                            {{ __('Member Verification') }}
                        </h3>

                        <p class="mt-3 flex-1 text-sm leading-6 text-zinc-600">
                            {{ __('Use the public IRDI Member Directory to verify a member’s current standing and view available public membership information and property owner feedback.') }}
                        </p>

                        <div class="mt-6">
                            <flux:button
                                href="{{ route('directory') }}"
                                variant="outline"
                                icon="identification"
                            >
                                {{ __('Verify an IRDI Member') }}
                            </flux:button>
                        </div>
                    </flux:card>

                </div>

            </div>

        </div>
    </section>

</x-layouts.public>
