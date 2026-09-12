<div
    x-data="{
        open: false,
        textSize: 100,
        highContrast: false,
        reduceMotion: false,
        highlightLinks: false,

        init() {
            const saved = localStorage.getItem('irdi_accessibility_preferences');

            if (saved) {
                try {
                    const preferences = JSON.parse(saved);

                    this.textSize = preferences.textSize ?? 100;
                    this.highContrast = preferences.highContrast ?? false;
                    this.reduceMotion = preferences.reduceMotion ?? false;
                    this.highlightLinks = preferences.highlightLinks ?? false;
                } catch (e) {
                    //
                }
            }

            this.apply();
        },

        save() {
            localStorage.setItem(
                'irdi_accessibility_preferences',
                JSON.stringify({
                    textSize: this.textSize,
                    highContrast: this.highContrast,
                    reduceMotion: this.reduceMotion,
                    highlightLinks: this.highlightLinks,
                })
            );

            this.apply();
        },

        apply() {
            document.documentElement.style.fontSize = this.textSize + '%';

            document.documentElement.classList.toggle(
                'irdi-high-contrast',
                this.highContrast
            );

            document.documentElement.classList.toggle(
                'irdi-reduce-motion',
                this.reduceMotion
            );

            document.documentElement.classList.toggle(
                'irdi-highlight-links',
                this.highlightLinks
            );
        },

        increaseText() {
            if (this.textSize < 130) {
                this.textSize += 10;
                this.save();
            }
        },

        decreaseText() {
            if (this.textSize > 90) {
                this.textSize -= 10;
                this.save();
            }
        },

        reset() {
            this.textSize = 100;
            this.highContrast = false;
            this.reduceMotion = false;
            this.highlightLinks = false;

            localStorage.removeItem('irdi_accessibility_preferences');

            this.apply();
        },
    }"
    x-init="init()"
    class="fixed bottom-5 left-5 z-[90]"
>
    <div
        x-cloak
        x-show="open"
        x-transition
        class="mb-3 w-80 rounded-xl border border-zinc-200 bg-white p-5 shadow-2xl"
        role="dialog"
        aria-label="Accessibility preferences"
    >
        <div class="mb-5 flex items-center justify-between gap-4">
            <div>
                <h2 class="text-lg font-semibold text-zinc-900">
                    Accessibility
                </h2>

                <p class="mt-1 text-sm text-zinc-500">
                    Adjust how IRDI is displayed.
                </p>
            </div>

            <button
                type="button"
                x-on:click="open = false"
                class="rounded-md p-2 text-zinc-500 hover:bg-zinc-100 hover:text-zinc-900"
                aria-label="Close accessibility menu"
            >
                <flux:icon.x-mark class="size-5" />
            </button>
        </div>

        <div class="space-y-5">

            {{-- Text size --}}
            <div>
                <div class="mb-2 flex items-center justify-between gap-4">
                    <span class="text-sm font-medium text-zinc-900">
                        Text Size
                    </span>

                    <span
                        class="text-sm text-zinc-500"
                        x-text="textSize + '%'"
                    ></span>
                </div>

                <div class="grid grid-cols-2 gap-2">
                    <flux:button
                        type="button"
                        variant="outline"
                        x-on:click="decreaseText()"
                    >
                        A−
                    </flux:button>

                    <flux:button
                        type="button"
                        variant="outline"
                        x-on:click="increaseText()"
                    >
                        A+
                    </flux:button>
                </div>
            </div>

            <div class="border-t border-zinc-200"></div>

            {{-- High contrast --}}
            <label class="flex cursor-pointer items-start justify-between gap-5">
                <div>
                    <span class="text-sm font-medium text-zinc-900">
                        High Contrast
                    </span>

                    <p class="mt-1 text-sm text-zinc-500">
                        Increase contrast between text and backgrounds.
                    </p>
                </div>

                <input
                    type="checkbox"
                    x-model="highContrast"
                    x-on:change="save()"
                    class="mt-1 size-5"
                >
            </label>

            {{-- Reduce motion --}}
            <label class="flex cursor-pointer items-start justify-between gap-5">
                <div>
                    <span class="text-sm font-medium text-zinc-900">
                        Reduce Motion
                    </span>

                    <p class="mt-1 text-sm text-zinc-500">
                        Minimize animations and transitions.
                    </p>
                </div>

                <input
                    type="checkbox"
                    x-model="reduceMotion"
                    x-on:change="save()"
                    class="mt-1 size-5"
                >
            </label>

            {{-- Highlight links --}}
            <label class="flex cursor-pointer items-start justify-between gap-5">
                <div>
                    <span class="text-sm font-medium text-zinc-900">
                        Highlight Links
                    </span>

                    <p class="mt-1 text-sm text-zinc-500">
                        Make links easier to identify.
                    </p>
                </div>

                <input
                    type="checkbox"
                    x-model="highlightLinks"
                    x-on:change="save()"
                    class="mt-1 size-5"
                >
            </label>

            <div class="border-t border-zinc-200"></div>

            <flux:button
                type="button"
                variant="outline"
                class="w-full"
                x-on:click="reset()"
            >
                Reset Accessibility
            </flux:button>
        </div>
    </div>

    <button
        type="button"
        x-on:click="open = ! open"
        class="flex size-14 items-center justify-center rounded-full bg-irdi-green text-white shadow-xl transition hover:scale-105 hover:bg-irdi-green/90 focus:outline-none focus:ring-4 focus:ring-irdi-gold/40"
        aria-label="Open accessibility preferences"
        :aria-expanded="open"
    >
        <svg
            xmlns="http://www.w3.org/2000/svg"
            viewBox="0 0 24 24"
            fill="none"
            stroke="currentColor"
            stroke-width="2"
            stroke-linecap="round"
            stroke-linejoin="round"
            class="size-8"
            aria-hidden="true"
        >
            <circle cx="12" cy="4" r="2" />
            <path d="M5 8h14" />
            <path d="M12 6v6" />
            <path d="m8 21 4-9 4 9" />
        </svg>
    </button>
</div>
