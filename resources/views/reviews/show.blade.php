<x-layouts.public
    title="Property Owner Feedback | IRDI"
    :noindex="true"
>

    <section class="bg-zinc-50">
        <div class="mx-auto max-w-3xl px-6 py-16 lg:px-8 lg:py-20">

            <div class="text-center">

                <h1 class="text-3xl font-bold tracking-tight text-irdi-green sm:text-4xl">
                    Property Owner Feedback
                </h1>

                <div class="mx-auto mt-4 h-1 w-20 bg-irdi-gold"></div>

            </div>

            <flux:card class="mt-10 p-6">

                @if ($invitation->isUsed())

                    <div class="rounded-lg bg-zinc-100 p-5 text-center">

                        <h2 class="text-lg font-semibold text-zinc-900">
                            This invitation has already been used.
                        </h2>

                        <p class="mt-2 text-sm text-zinc-600">
                            Each IRDI property owner feedback invitation can only be used once.
                        </p>

                    </div>

                @elseif ($invitation->isExpired())

                    <div class="rounded-lg bg-amber-50 p-5 text-center">

                        <h2 class="text-lg font-semibold text-amber-900">
                            This invitation has expired.
                        </h2>

                        <p class="mt-2 text-sm text-amber-800">
                            Please ask the Detectorist to create a new property owner feedback invitation.
                        </p>

                    </div>

                @else

                    <div class="text-center">

                        @if ($invitation->memberProfile->profile_image)

                            <img
                                src="{{ asset('storage/' . $invitation->memberProfile->profile_image) }}"
                                alt="{{ $invitation->memberProfile->profile_name }}"
                                class="mx-auto h-20 w-20 rounded-full object-cover"
                            >

                        @endif

                        <h2 class="mt-4 text-2xl font-semibold text-irdi-green">
                            Rate your experience with
                            {{ $invitation->memberProfile->profile_name }}
                        </h2>

                        <p class="mt-3 text-zinc-600">
                            You do not need an IRDI account to leave feedback.
                        </p>

                        <p class="mt-2 text-sm text-zinc-500">
                            This invitation is private, can only be used once, and expires
                            {{ $invitation->expires_at->diffForHumans() }}.
                        </p>

                    </div>

                    <div class="mt-8 rounded-lg border border-zinc-200 bg-zinc-50 p-5">

                        <h3 class="font-semibold text-irdi-green">
                            What happens next?
                        </h3>

                        <p class="mt-2 text-sm leading-6 text-zinc-600">
                            Before submitting feedback, you will be asked to verify your email address.
                            Your email address will not be displayed publicly.
                        </p>

                    </div>

                @endif

            </flux:card>

        </div>
    </section>

</x-layouts.public>
