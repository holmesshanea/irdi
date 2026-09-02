<x-layouts.public
    title="Resources | IRDI"
    description="Member resources from the International Responsible Detectorist Institute, including ethical standards, best practices, educational materials, and responsible detecting guidance."
>

    <section class="bg-zinc-50">
        <div class="mx-auto max-w-7xl px-6 py-16 lg:px-8 lg:py-20">

            <div class="mx-auto max-w-3xl text-center">

                <h1 class="text-3xl font-bold tracking-tight text-irdi-green sm:text-4xl">
                    Resources
                </h1>

                <div class="mx-auto mt-4 h-1 w-20 bg-irdi-gold"></div>

                <p class="mt-6 text-lg leading-8 text-zinc-600">
                    Explore IRDI standards, educational materials, and trusted resources
                    that support ethical, responsible, and informed metal detecting.
                </p>

            </div>

        </div>
    </section>

    <section class="bg-white">
        <div class="mx-auto max-w-7xl px-6 py-12 lg:px-8">

            <div class="mx-auto max-w-5xl">

                <div class="mb-8">

                    <h2 class="text-2xl font-bold tracking-tight text-irdi-green">
                        IRDI Resources
                    </h2>

                    <div class="mt-2 h-1 w-16 bg-irdi-gold"></div>

                    <p class="mt-4 text-zinc-600">
                        Standards and guidance developed by IRDI for responsible detectorists.
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
                            Code of Ethics for Responsible Detecting
                        </h3>

                        <p class="mt-3 flex-1 text-sm leading-6 text-zinc-600">
                            Review the ethical principles and responsibilities expected
                            of every IRDI member and responsible detectorist.
                        </p>

                        <div class="mt-6">
                            <flux:button
                                href="{{ route('code-of-ethics') }}"
                                variant="outline"
                                icon="book-open"
                            >
                                View Code of Ethics
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
                            Best Practices of Responsible Detecting
                        </h3>

                        <p class="mt-3 flex-1 text-sm leading-6 text-zinc-600">
                            Practical guidance for detecting responsibly, respecting
                            property, protecting history, and representing the hobby well.
                        </p>

                        <div class="mt-6">
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

            </div>

        </div>
    </section>

</x-layouts.public>
