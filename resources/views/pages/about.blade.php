<x-layouts.public
    title="About IRDI | Ethical & Responsible Metal Detecting"
    description="Learn about IRDI, an international membership organization promoting ethical metal detecting, responsible stewardship, respect for landowners, and protection of our shared history."
>

    {{-- About intro --}}
    <section class="bg-white">
        <div class="mx-auto max-w-7xl px-6 py-16 lg:px-8 lg:py-20">

            <div class="mx-auto max-w-3xl text-center">

                <h1 class="text-3xl font-bold tracking-tight text-irdi-green sm:text-4xl">
                    About IRDI
                </h1>

                <div class="mx-auto mt-4 h-1 w-20 bg-irdi-gold"></div>

                <p class="mt-6 text-lg leading-8 text-zinc-600">
                    Metal detecting gives us a unique connection to the past—and with
                    that opportunity comes a responsibility to protect the history,
                    places, and relationships that make the hobby possible.
                </p>

            </div>

        </div>
    </section>

    {{-- Who We Are --}}
    <section class="bg-zinc-50">
        <div class="mx-auto max-w-7xl px-6 py-16 lg:px-8 lg:py-20">

            <div class="grid grid-cols-1 gap-10 lg:grid-cols-2 lg:items-center">

                {{-- Left side --}}
                <div>
                    <h2 class="text-3xl font-bold tracking-tight text-irdi-green">
                        Who We Are
                    </h2>

                    <div class="mt-4 h-1 w-20 bg-irdi-gold"></div>
                </div>

                {{-- Right side --}}
                <div class="text-lg leading-8 text-zinc-600">

                    <p>
                        IRDI is an international membership organization dedicated to
                        promoting ethical metal detecting, protecting our shared history,
                        and encouraging responsible detecting through education, best
                        practices, and respect for landowners.
                    </p>

                </div>

            </div>

        </div>
    </section>

    {{-- Our Mission --}}
    <section class="bg-irdi-green">
        <div class="mx-auto max-w-7xl px-6 py-16 lg:px-8 lg:py-20">

            {{-- Mission statement --}}
            <div class="mx-auto max-w-4xl text-center">

                <h2 class="text-3xl font-bold tracking-tight text-white sm:text-4xl">
                    Our Mission
                </h2>

                <div class="mx-auto mt-4 h-1 w-20 bg-irdi-gold"></div>

                <p class="mt-8 text-xl leading-9 text-white">
                    Our mission is to promote responsible metal detecting through
                    education, ethical stewardship, respect for landowners, and a
                    commitment to protecting our shared history for future generations.
                </p>

            </div>

            {{-- Guiding principles --}}
            <div class="mx-auto mt-14 max-w-4xl divide-y divide-white/20">

                {{-- Principle 1 --}}
                <div class="grid gap-4 py-8 sm:grid-cols-[80px_1fr]">

                    <div class="text-3xl font-bold text-irdi-gold">
                        01
                    </div>

                    <div>
                        <h3 class="text-xl font-semibold text-white">
                            Protect Our Shared History
                        </h3>

                        <p class="mt-3 leading-7 text-zinc-200">
                            Every artifact tells a story. IRDI encourages responsible
                            recovery and thoughtful stewardship so that today’s discoveries
                            can continue to educate and inspire future generations.
                        </p>
                    </div>

                </div>

                {{-- Principle 2 --}}
                <div class="grid gap-4 py-8 sm:grid-cols-[80px_1fr]">

                    <div class="text-3xl font-bold text-irdi-gold">
                        02
                    </div>

                    <div>
                        <h3 class="text-xl font-semibold text-white">
                            Respect Landowners
                        </h3>

                        <p class="mt-3 leading-7 text-zinc-200">
                            Responsible detectorists earn trust through honesty, courtesy,
                            and respect for private property. Every hunt begins with
                            permission and ends with leaving the land exactly as it
                            should be—or better.
                        </p>
                    </div>

                </div>

                {{-- Principle 3 --}}
                <div class="grid gap-4 py-8 sm:grid-cols-[80px_1fr]">

                    <div class="text-3xl font-bold text-irdi-gold">
                        03
                    </div>

                    <div>
                        <h3 class="text-xl font-semibold text-white">
                            Lead by Example
                        </h3>

                        <p class="mt-3 leading-7 text-zinc-200">
                            IRDI Members demonstrate that responsible metal detecting
                            is about more than finding artifacts. It is about integrity,
                            education, and helping build a positive future for the hobby
                            through ethical practices.
                        </p>
                    </div>

                </div>

            </div>

        </div>
    </section>

    {{-- Closing Call to Action --}}
    <section class="bg-white">
        <div class="mx-auto max-w-7xl px-6 py-16 lg:px-8 lg:py-20">

            <div class="mx-auto max-w-4xl text-center">

                <h2 class="text-3xl font-bold tracking-tight text-irdi-green sm:text-4xl">
                    Become Part of Something Bigger
                </h2>

                <div class="mx-auto mt-4 h-1 w-20 bg-irdi-gold"></div>

                <p class="mt-6 text-lg leading-8 text-zinc-600">
                    Every responsible detectorist has the power to make a difference.
                    By joining IRDI, you’re helping protect our shared history, promote
                    ethical detecting, and build a stronger future for the hobby.
                </p>

                <div class="mt-8 flex flex-col items-center justify-center gap-4 sm:flex-row">

                    <flux:button
                        href="{{ route('membership') }}"
                        variant="primary"
                    >
                        Become a Member
                    </flux:button>

                    <flux:button
                        href="{{ route('directory') }}"
                        variant="ghost"
                    >
                        Member Directory
                    </flux:button>

                </div>

            </div>

        </div>
    </section>

</x-layouts.public>
