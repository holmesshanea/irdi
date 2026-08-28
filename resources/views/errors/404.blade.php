<x-layouts.public>

    <div class="mx-auto max-w-4xl px-6 py-20 text-center lg:py-28">

        <div class="mx-auto max-w-2xl">

            <p class="text-sm font-semibold uppercase tracking-[0.2em] text-irdi-gold">
                Error 404
            </p>

            <flux:heading
                size="xl"
                level="1"
                class="mt-4 text-irdi-green"
            >
                This trail doesn’t lead anywhere.
            </flux:heading>

            <flux:text class="mt-4 text-base leading-7">
                The page you’re looking for may have been moved, removed,
                or the address may be incorrect.
            </flux:text>

            <div class="mx-auto mt-8 h-1 w-16 rounded-full bg-irdi-gold"></div>

            <div class="mt-10 rounded-2xl border border-zinc-200 bg-zinc-50 p-8 dark:border-zinc-700 dark:bg-zinc-900">

                <flux:heading size="lg" level="2">
                    Let’s get you back on the right path.
                </flux:heading>

                <flux:text class="mt-2">
                    You can return to the home page or search the IRDI Member Directory.
                </flux:text>

                <div class="mt-6 flex flex-col justify-center gap-3 sm:flex-row">

                    <flux:button
                        :href="route('home')"
                        variant="primary"
                        wire:navigate
                    >
                        Return Home
                    </flux:button>

                    <flux:button
                        :href="route('directory')"
                        variant="outline"
                        wire:navigate
                    >
                        Member Directory
                    </flux:button>

                </div>

            </div>

            <div class="mt-10">

                <flux:text class="text-sm">
                    If you believe this page should exist, you can contact IRDI for help.
                </flux:text>

                <div class="mt-4">
                    <flux:button
                        :href="route('contact')"
                        variant="ghost"
                        wire:navigate
                    >
                        Contact IRDI
                    </flux:button>
                </div>

            </div>

        </div>

    </div>

</x-layouts.public>
