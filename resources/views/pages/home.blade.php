<x-layouts.public>

    {{-- Hero --}}
    <section class="bg-irdi-green">
        <div class="mx-auto max-w-7xl px-6 py-20 lg:px-8 lg:py-28">

            <div class="mx-auto max-w-3xl text-center">

                <p class="text-sm font-semibold uppercase tracking-widest text-irdi-gold">
                    International Responsible Detectorist Institute
                </p>

                <h1 class="mt-4 text-4xl font-bold tracking-tight text-white sm:text-5xl lg:text-6xl">
                    Raising the Standard for Responsible Metal Detecting
                </h1>

                <p class="mx-auto mt-6 max-w-2xl text-lg leading-8 text-zinc-200">
                    Promoting responsible metal detecting, ethical stewardship,
                    and respect for our shared history.
                </p>

                <div class="mt-8 flex flex-col items-center justify-center gap-4 sm:flex-row">

                    <flux:button
                        href="{{ route('membership') }}"
                        variant="primary"
                        class="bg-irdi-gold! text-white! hover:bg-amber-700!"
                    >
                        Become a Member
                    </flux:button>

                    <flux:button
                        href="{{ route('directory') }}"
                        variant="outline"
                        class="bg-transparent! border-white/40! text-white! hover:bg-white/10! hover:text-white!"
                    >
                        Member Directory
                    </flux:button>

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
                    Our Guiding Principles
                </h2>

                <p class="mt-4 text-lg text-zinc-600">
                    Responsible detecting begins with respect—for history,
                    landowners, and the future of the hobby.
                </p>
            </div>

            {{-- Three cards --}}
            <div class="mt-12 grid grid-cols-1 gap-6 lg:grid-cols-3">

                <flux:card class="flex h-full flex-col p-8 transition hover:-translate-y-1 hover:shadow-lg">

                    <div class="mb-5 flex size-12 items-center justify-center rounded-full bg-irdi-green/10">
                        <flux:icon.book-open class="size-6 text-irdi-gold" />
                    </div>

                    <h3 class="text-xl font-semibold text-irdi-green lg:min-h-14">
                        Protect Our Shared History
                    </h3>
                    <div class="mt-3 h-1 w-full rounded-full bg-irdi-gold"></div>

                    <p class="mt-4 leading-7 text-zinc-600">
                        Every artifact tells a story. IRDI encourages responsible
                        recovery and thoughtful stewardship so that today’s
                        discoveries can continue to educate and inspire future
                        generations.
                    </p>
                </flux:card>


                <flux:card class="flex h-full flex-col p-8 transition hover:-translate-y-1 hover:shadow-lg">

                    <div class="mb-5 flex size-12 items-center justify-center rounded-full bg-irdi-green/10">
                        <flux:icon.hand-raised class="size-6 text-irdi-gold" />
                    </div>

                    <h3 class="text-xl font-semibold text-irdi-green lg:min-h-14">
                        Respect Landowners
                    </h3>
                    <div class="mt-3 h-1 w-full rounded-full bg-irdi-gold"></div>

                    <p class="mt-4 leading-7 text-zinc-600">
                        Responsible detectorists earn trust through honesty,
                        courtesy, and respect for private property. We believe
                        every hunt begins with permission and ends with leaving
                        the land exactly as it should be—or better.
                    </p>
                </flux:card>

                <flux:card class="flex h-full flex-col p-8 transition hover:-translate-y-1 hover:shadow-lg">

                    <div class="mb-5 flex size-12 items-center justify-center rounded-full bg-irdi-green/10">
                        <flux:icon.star class="size-6 text-irdi-gold" />
                    </div>

                    <h3 class="text-xl font-semibold text-irdi-green lg:min-h-14">
                        Lead by Example
                    </h3>
                    <div class="mt-3 h-1 w-full rounded-full bg-irdi-gold"></div>

                    <p class="mt-4 leading-7 text-zinc-600">
                        IRDI Members demonstrate that responsible metal detecting
                        is about more than finding artifacts. It is about
                        integrity, education, and helping build a positive future
                        for the hobby through ethical practices.
                    </p>
                </flux:card>

            </div>

        </div>
    </section>

</x-layouts.public>
