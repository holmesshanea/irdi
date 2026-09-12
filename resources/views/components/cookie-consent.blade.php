<div
    x-data="{
        open: false,
        preferencesOpen: false,
        necessary: true,
        analytics: false,

        init() {
            const consent = localStorage.getItem('irdi_cookie_consent');

            if (! consent) {
                this.open = true;
                return;
            }

            try {
                const saved = JSON.parse(consent);

                this.analytics = saved.analytics ?? false;
            } catch (e) {
                this.open = true;
            }
        },

        save() {
            localStorage.setItem(
                'irdi_cookie_consent',
                JSON.stringify({
                    necessary: true,
                    analytics: this.analytics,
                    updated_at: new Date().toISOString(),
                })
            );

            this.preferencesOpen = false;
            this.open = false;

            window.dispatchEvent(
                new CustomEvent('irdi-cookie-consent-updated', {
                    detail: {
                        necessary: true,
                        analytics: this.analytics,
                    },
                })
            );
        },

        acceptAll() {
            this.analytics = true;
            this.save();
        },

        rejectOptional() {
            this.analytics = false;
            this.save();
        },

        showPreferences() {
            this.open = true;
            this.preferencesOpen = true;
        },
    }"
    x-init="init()"
    @irdi-cookie-preferences.window="showPreferences()"
>
    {{-- Cookie banner --}}
    <div
        x-cloak
        x-show="open"
        x-transition.opacity
        class="fixed inset-x-0 bottom-0 z-[100] border-t border-zinc-700 bg-zinc-950 text-white shadow-2xl"
        role="region"
        aria-label="Cookie consent"
    >
        <div class="mx-auto max-w-7xl px-4 py-4 sm:px-6 lg:px-8">

            <div
                class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between"
                x-show="! preferencesOpen"
            >
                <div class="max-w-3xl">
                    <p class="text-sm leading-6 text-zinc-200">
                        We use cookies to provide essential site functionality
                        and, with your permission, to help us understand how
                        visitors use IRDI.
                    </p>
                </div>

                <div class="flex flex-wrap gap-2">
                    <flux:button
                        type="button"
                        variant="outline"
                        class="text-white"
                        x-on:click="preferencesOpen = true"
                    >
                        Preferences
                    </flux:button>

                    <flux:button
                        type="button"
                        variant="outline"
                        x-on:click="rejectOptional()"
                    >
                        Reject Optional
                    </flux:button>

                    <flux:button
                        type="button"
                        variant="primary"
                        x-on:click="acceptAll()"
                    >
                        Accept All
                    </flux:button>
                </div>
            </div>

            {{-- Preferences --}}
            <div
                x-cloak
                x-show="preferencesOpen"
                class="mx-auto max-w-2xl"
            >
                <div class="mb-5">
                    <h2 class="text-lg font-semibold">
                        Cookie Preferences
                    </h2>

                    <p class="mt-1 text-sm leading-6 text-zinc-300">
                        Choose which optional cookies IRDI may use.
                        Necessary cookies are required for the website to work
                        and cannot be disabled.
                    </p>
                </div>

                <div class="space-y-4">
                    <div class="rounded-lg border border-zinc-700 p-4">
                        <div class="flex items-start justify-between gap-6">
                            <div>
                                <p class="font-medium">
                                    Necessary Cookies
                                </p>

                                <p class="mt-1 text-sm text-zinc-300">
                                    Used for security, authentication, sessions,
                                    and other essential website functions.
                                </p>
                            </div>

                            <span class="text-sm font-medium text-emerald-400">
                                Always Active
                            </span>
                        </div>
                    </div>

                    <div class="rounded-lg border border-zinc-700 p-4">
                        <div class="flex items-start justify-between gap-6">
                            <div>
                                <p class="font-medium">
                                    Analytics Cookies
                                </p>

                                <p class="mt-1 text-sm text-zinc-300">
                                    Help us understand how visitors use IRDI so
                                    we can improve the website.
                                </p>
                            </div>

                            <label class="relative inline-flex cursor-pointer items-center">
                                <input
                                    type="checkbox"
                                    x-model="analytics"
                                    class="peer sr-only"
                                >

                                <span
                                    class="h-6 w-11 rounded-full bg-zinc-600
                                           after:absolute after:left-[2px]
                                           after:top-[2px] after:h-5 after:w-5
                                           after:rounded-full after:bg-white
                                           after:transition-all after:content-['']
                                           peer-checked:bg-irdi-green
                                           peer-checked:after:translate-x-full"
                                ></span>

                                <span class="sr-only">
                                    Allow analytics cookies
                                </span>
                            </label>
                        </div>
                    </div>
                </div>

                <div class="mt-5 flex flex-wrap justify-end gap-2">
                    <flux:button
                        type="button"
                        variant="outline"
                        class="text-white"
                        x-on:click="preferencesOpen = false"
                    >
                        Back
                    </flux:button>

                    <flux:button
                        type="button"
                        variant="primary"
                        x-on:click="save()"
                    >
                        Save Preferences
                    </flux:button>
                </div>
            </div>

        </div>
    </div>
</div>
