<x-layouts.public>

    <div class="mx-auto max-w-4xl px-6 py-20 text-center lg:py-28">

        <div class="mx-auto max-w-2xl">

            <p class="text-sm font-semibold uppercase tracking-[0.2em] text-irdi-gold">
                Error 429
            </p>

            <flux:heading
                size="xl"
                level="1"
                class="mt-4 text-irdi-green"
            >
                You’re moving a little too quickly.
            </flux:heading>

            <flux:text class="mt-4 text-base leading-7">
                IRDI has temporarily limited requests from your connection.
                This helps protect member accounts and our website from automated
                or excessive activity.
            </flux:text>

            <div class="mx-auto mt-8 h-1 w-16 rounded-full bg-irdi-gold"></div>

            <div class="mt-10 rounded-2xl border border-zinc-200 bg-zinc-50 p-8 dark:border-zinc-700 dark:bg-zinc-900">

                <flux:heading size="lg" level="2">
                    Please try again in a few moments.
                </flux:heading>

                <flux:text class="mt-2">
                    You can wait briefly and try your request again, or return
                    to the IRDI home page.
                </flux:text>

                <div class="mt-6">
                    <flux:button
                        href="/"
                        variant="primary"
                        wire:navigate
                    >
                        Return Home
                    </flux:button>
                </div>

            </div>

        </div>

    </div>

</x-layouts.public>
