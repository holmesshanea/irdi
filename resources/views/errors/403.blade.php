<x-layouts.public>

    <div class="mx-auto max-w-4xl px-6 py-20 text-center lg:py-28">

        <div class="mx-auto max-w-2xl">

            <p class="text-sm font-semibold uppercase tracking-[0.2em] text-irdi-gold">
Error 403
</p>

            <flux:heading
                size="xl"
                level="1"
                class="mt-4 text-irdi-green"
>
This area isn’t available to you.
            </flux:heading>

            <flux:text class="mt-4 text-base leading-7">
You don’t have permission to view this page.
                If you believe you should have access, make sure you are signed
                into the correct IRDI account.
            </flux:text>

            <div class="mx-auto mt-8 h-1 w-16 rounded-full bg-irdi-gold"></div>

            <div class="mt-10 rounded-2xl border border-zinc-200 bg-zinc-50 p-8 dark:border-zinc-700 dark:bg-zinc-900">

                <flux:heading size="lg" level="2">
    Let’s get you back on the right path.
                </flux:heading>

                <flux:text class="mt-2">
                    Return to your account or head back to the IRDI home page.
                </flux:text>

                <div class="mt-6 flex flex-col justify-center gap-3 sm:flex-row">

                    <flux:button
                        href="/account"
                        variant="primary"
                        wire:navigate
>
My Account
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
