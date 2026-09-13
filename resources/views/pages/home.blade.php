<x-layouts.public
    :title="__('home.meta.title')"
    :description="__('home.meta.description')"
>
    {{-- Hero --}}
    <section class="relative overflow-hidden bg-irdi-green">

        <picture class="absolute inset-0">
            <source
                media="(max-width: 640px)"
                srcset="{{ asset('images/mobile/mobile1.png') }}"
            >

            <img
                src="{{ asset('images/carousel/carousel1.png') }}"
                alt=""
                class="h-full w-full object-cover"
            >
        </picture>

        <div class="absolute inset-0 bg-irdi-green/75"></div>

        <div class="relative mx-auto max-w-7xl px-6 py-20 lg:px-8 lg:py-28">

            <div class="mx-auto max-w-3xl text-center">

                <p class="text-sm font-semibold uppercase tracking-widest text-irdi-gold-light">
                    {{ __('home.hero.organization') }}
                </p>

                <h1 class="mt-4 text-4xl font-bold tracking-tight text-white sm:text-5xl lg:text-6xl">
                    {{ __('home.hero.heading') }}
                </h1>

                <p class="mx-auto mt-6 max-w-2xl text-lg leading-8 text-zinc-200">
                    {{ __('home.hero.description') }}
                </p>

                <div class="mt-8 flex flex-col items-center justify-center gap-4 sm:flex-row">

                    <flux:button
                        href="{{ route('membership') }}"
                        variant="primary"
                        class="bg-irdi-gold! text-zinc-950! hover:bg-amber-500!"
                    >
                        {{ __('home.hero.become_member') }}
                    </flux:button>

                    <flux:button
                        href="{{ route('directory') }}"
                        variant="outline"
                        class="border-white/40! bg-transparent! text-white! hover:bg-white/10! hover:text-white!"
                    >
                        {{ __('home.hero.member_directory') }}
                    </flux:button>

                </div>

                <div class="mt-8 flex items-center justify-center gap-3 text-white">
                    <flux:icon.users class="size-7 text-irdi-gold" />

                    <div class="flex items-baseline gap-2">
                        <span class="text-3xl font-bold tracking-tight">
                            {{ number_format($memberCount) }}
                        </span>

                        <span class="text-sm text-zinc-200 sm:text-base">
                            {{ trans_choice('home.hero.active_members_worldwide', $memberCount) }}
                        </span>
                    </div>
                </div>

            </div>

        </div>
    </section>

    {{-- Three Pillars --}}
    <section class="bg-white">
        <div class="mx-auto max-w-7xl px-6 py-16 lg:px-8 lg:py-20">

            {{-- Section heading --}}
            <div class="mx-auto max-w-3xl text-center">
                <h2 class="text-3xl font-bold tracking-tight text-irdi-green sm:text-4xl">
                    {{ __('home.principles.heading') }}
                </h2>

                <p class="mt-4 text-lg text-zinc-600">
                    {{ __('home.principles.description') }}
                </p>
            </div>

            {{-- Three cards --}}
            <div class="mt-12 grid grid-cols-1 gap-6 lg:grid-cols-3">

                <flux:card class="group flex h-full flex-col p-8 transition hover:-translate-y-1 hover:shadow-lg">

                    <div class="mb-5 flex size-12 items-center justify-center rounded-full bg-irdi-green/10">
                        <flux:icon.book-open class="size-6 text-irdi-gold" />
                    </div>

                    <h3 class="text-xl font-semibold text-irdi-green lg:min-h-14">
                        {{ __('home.principles.history.heading') }}
                    </h3>
                    <div class="mt-3 h-1 w-full rounded-full bg-irdi-gold"></div>

                    <p class="mt-4 leading-7 text-zinc-600">
                        {{ __('home.principles.history.description') }}
                    </p>

                    <div class="mt-auto pt-6">
                        <div class="relative overflow-hidden rounded-lg">
                            <img
                                src="{{ asset('images/historic-preservation.png') }}"
                                alt="{{ __('home.principles.history.image_alt') }}"
                                class="h-48 w-full object-cover transition duration-300 group-hover:scale-[1.05]"
                            >

                            <div class="pointer-events-none absolute inset-0 bg-irdi-green/25"></div>
                        </div>
                    </div>
                </flux:card>


                <flux:card class="group flex h-full flex-col p-8 transition hover:-translate-y-1 hover:shadow-lg">

                    <div class="mb-5 flex size-12 items-center justify-center rounded-full bg-irdi-green/10">
                        <flux:icon.hand-raised class="size-6 text-irdi-gold" />
                    </div>

                    <h3 class="text-xl font-semibold text-irdi-green lg:min-h-14">
                        {{ __('home.principles.landowners.heading') }}
                    </h3>
                    <div class="mt-3 h-1 w-full rounded-full bg-irdi-gold"></div>

                    <p class="mt-4 leading-7 text-zinc-600">
                        {{ __('home.principles.landowners.description') }}
                    </p>

                    <div class="mt-auto pt-6">
                        <div class="relative overflow-hidden rounded-lg">
                            <img
                                src="{{ asset('images/landowner-trust.png') }}"
                                alt="{{ __('home.principles.landowners.image_alt') }}"
                                class="h-48 w-full object-cover transition duration-300 group-hover:scale-[1.05]"
                            >

                            <div class="pointer-events-none absolute inset-0 bg-irdi-green/25"></div>
                        </div>
                    </div>
                </flux:card>

                <flux:card class="group flex h-full flex-col p-8 transition hover:-translate-y-1 hover:shadow-lg">

                    <div class="mb-5 flex size-12 items-center justify-center rounded-full bg-irdi-green/10">
                        <flux:icon.star class="size-6 text-irdi-gold" />
                    </div>

                    <h3 class="text-xl font-semibold text-irdi-green lg:min-h-14">
                        {{ __('home.principles.example.heading') }}
                    </h3>
                    <div class="mt-3 h-1 w-full rounded-full bg-irdi-gold"></div>

                    <p class="mt-4 leading-7 text-zinc-600">
                        {{ __('home.principles.example.description') }}
                    </p>

                    <div class="mt-auto pt-6">
                        <div class="relative overflow-hidden rounded-lg">
                            <img
                                src="{{ asset('images/global-standards.png') }}"
                                alt="{{ __('home.principles.example.image_alt') }}"
                                class="h-48 w-full object-cover transition duration-300 group-hover:scale-[1.05]"
                            >

                            <div class="pointer-events-none absolute inset-0 bg-irdi-green/25"></div>
                        </div>
                    </div>
                </flux:card>

            </div>

        </div>
    </section>

</x-layouts.public>
