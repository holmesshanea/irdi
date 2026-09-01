<x-layouts.public
    title="Contact IRDI | Responsible Metal Detecting"
    description="Contact IRDI with questions about membership, responsible metal detecting, partnerships, member accounts, or the International Responsible Detectorist Institute."
>

    <section class="bg-white">
        <div class="mx-auto max-w-7xl px-6 py-16 lg:px-8 lg:py-20">

            <div class="mx-auto max-w-3xl text-center">

                <h1 class="text-3xl font-bold tracking-tight text-irdi-green sm:text-4xl">
                    Contact IRDI
                </h1>

                <div class="mx-auto mt-4 h-1 w-20 bg-irdi-gold"></div>

                <p class="mt-6 text-lg leading-8 text-zinc-600">
                    Have a question about IRDI, membership, or responsible metal detecting?
                    We’d be happy to hear from you.
                </p>

            </div>

        </div>
    </section>

    {{-- Contact content --}}
    <section class="bg-zinc-50">
        <div class="mx-auto max-w-7xl px-6 py-16 lg:px-8 lg:py-20">

            <div class="grid grid-cols-1 items-start gap-10 lg:grid-cols-[0.9fr_1.1fr] lg:gap-14 xl:gap-20">

                {{-- Left side --}}
                <div class="lg:pr-4">
                    <h2 class="text-2xl font-bold text-irdi-green">
                        Get in Touch
                    </h2>

                    <div class="mt-3 h-1 w-full bg-irdi-gold"></div>

                    <p class="mt-6 max-w-xl text-lg leading-8 text-zinc-600">
                        Whether you have a question about membership, IRDI,
                        responsible detecting, or our organization, we’re here to help.
                    </p>

                    <div class="mt-8 space-y-6">

                        <div>
                            <h3 class="font-semibold text-zinc-900">
                                Membership Questions
                            </h3>

                            <p class="mt-2 text-zinc-600">
                                Have questions about becoming an IRDI Member or
                                your existing membership? Send us a message.
                            </p>
                        </div>

                        <div>
                            <h3 class="font-semibold text-zinc-900">
                                General Inquiries
                            </h3>

                            <p class="mt-2 text-zinc-600">
                                Contact us with questions about IRDI, responsible
                                metal detecting, partnerships, or other inquiries.
                            </p>
                        </div>

                    </div>
                </div>


                {{-- Right side: Contact form --}}
                <livewire:contact-form />

            </div>

        </div>
    </section>

</x-layouts.public>

