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
        aria-label="{{ __('cookies.consent_aria') }}"
    >
        <div class="mx-auto max-w-7xl px-4 py-4 sm:px-6 lg:px-8">

            <div
                class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between"
                x-show="! preferencesOpen"
            >
                <div class="max-w-3xl">
                    <p class="text-sm leading-6 text-zinc-200">
                        {{ __('cookies.banner_message') }}
                    </p>
                </div>

                <div class="flex flex-wrap gap-2">
                    <flux:button
                        type="button"
                        variant="outline"
                        class="text-white"
                        x-on:click="preferencesOpen = true"
                    >
                        {{ __('cookies.preferences') }}
                    </flux:button>

                    <flux:button
                        type="button"
                        variant="outline"
                        x-on:click="rejectOptional()"
                    >
                        {{ __('cookies.reject_optional') }}
                    </flux:button>

                    <flux:button
                        type="button"
                        variant="primary"
                        x-on:click="acceptAll()"
                    >
                        {{ __('cookies.accept_all') }}
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
                        {{ __('cookies.preferences_title') }}
                    </h2>

                    <p class="mt-1 text-sm leading-6 text-zinc-300">
                        {{ __('cookies.preferences_intro') }}
                    </p>
                </div>

                <div class="space-y-4">
                    <div class="rounded-lg border border-zinc-700 p-4">
                        <div class="flex items-start justify-between gap-6">
                            <div>
                                <p class="font-medium">
                                    {{ __('cookies.necessary_title') }}
                                </p>

                                <p class="mt-1 text-sm text-zinc-300">
                                    {{ __('cookies.necessary_description') }}
                                </p>
                            </div>

                            <span class="text-sm font-medium text-emerald-400">
                                {{ __('cookies.always_active') }}
                            </span>
                        </div>
                    </div>

                    <div class="rounded-lg border border-zinc-700 p-4">
                        <div class="flex items-start justify-between gap-6">
                            <div>
                                <p class="font-medium">
                                    {{ __('cookies.analytics_title') }}
                                </p>

                                <p class="mt-1 text-sm text-zinc-300">
                                    {{ __('cookies.analytics_description') }}
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
                                    {{ __('cookies.allow_analytics') }}
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
                        {{ __('cookies.back') }}
                    </flux:button>

                    <flux:button
                        type="button"
                        variant="primary"
                        x-on:click="save()"
                    >
                        {{ __('cookies.save_preferences') }}
                    </flux:button>
                </div>
            </div>

        </div>
    </div>
</div>
