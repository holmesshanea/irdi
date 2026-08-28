<x-layouts.public>

    <div class="mx-auto max-w-4xl px-6 py-20 text-center lg:py-28">

        <div class="mx-auto max-w-2xl">

            <p class="text-sm font-semibold uppercase tracking-[0.2em] text-irdi-gold">
                Error 419
            </p>

            <flux:heading
                size="xl"
                level="1"
                class="mt-4 text-irdi-green"
            >
                Your session has expired.
            </flux:heading>

            <flux:text class="mt-4 text-base leading-7">
                For your security, IRDI sessions expire after a period of inactivity.
                Any information that was not submitted may need to be entered again.
            </flux:text>

            <div class="mx-auto mt-8 h-1 w-16 rounded-full bg-irdi-gold"></div>

            <div class="mt-10 rounded-2xl border border-zinc-200 bg-zinc-50 p-8 dark:border-zinc-700 dark:bg-zinc-900">

                <flux:heading size="lg" level="2">
                    No problem — you can continue from here.
                </flux:heading>

                <flux:text class="mt-2">
                    Sign in again or return to the IRDI home page.
                </flux:text>

                <div class="mt-6 flex flex-col justify-center gap-3 sm:flex-row">

                    <flux:button
                        href="/login"
                        variant="primary"
                        wire:navigate
                    >
                        Sign In Again
                    </flux:button>

                    <flux:button
                        href="/"
                        variant="outline"
                        wire:navigate
                    >
                        Return Home
                    </flux:button>

                </div>

            </div>

        </div>

    </div>

</x-layouts.public>
