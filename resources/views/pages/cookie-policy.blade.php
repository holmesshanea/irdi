<x-layouts.public
    :title="__('cookies.meta_title')"
    :description="__('cookies.meta_description')"
>
    <div class="mx-auto max-w-4xl">
        <div class="space-y-8">
            <div>
                <flux:heading size="xl" level="1">
                    {{ __('cookies.policy_title') }}
                </flux:heading>

                <flux:text class="mt-2 text-zinc-600">
                    {{ __('cookies.last_updated') }}
                </flux:text>
            </div>

            <section class="space-y-3">
                <flux:heading size="lg" level="2">
                    {{ __('cookies.what_are_cookies') }}
                </flux:heading>

                <flux:text class="leading-7 text-zinc-700">
                    {{ __('cookies.what_are_cookies_body') }}
                </flux:text>
            </section>

            <section class="space-y-3">
                <flux:heading size="lg" level="2">
                    {{ __('cookies.how_irdi_uses') }}
                </flux:heading>

                <flux:text class="leading-7 text-zinc-700">
                    {{ __('cookies.how_irdi_uses_body') }}
                </flux:text>
            </section>

            <section class="space-y-4">
                <flux:heading size="lg" level="2">
                    {{ __('cookies.necessary_title') }}
                </flux:heading>

                <flux:text class="leading-7 text-zinc-700">
                    {{ __('cookies.necessary_body_1') }}
                </flux:text>

                <flux:text class="leading-7 text-zinc-700">
                    {{ __('cookies.necessary_body_2') }}
                </flux:text>
            </section>

            <section class="space-y-4">
                <flux:heading size="lg" level="2">
                    {{ __('cookies.analytics_title') }}
                </flux:heading>

                <flux:text class="leading-7 text-zinc-700">
                    {{ __('cookies.analytics_body_1') }}
                </flux:text>

                <flux:text class="leading-7 text-zinc-700">
                    {{ __('cookies.analytics_body_2') }}
                </flux:text>
            </section>

            <section class="space-y-3">
                <flux:heading size="lg" level="2">
                    {{ __('cookies.managing_title') }}
                </flux:heading>

                <flux:text class="leading-7 text-zinc-700">
                    {{ __('cookies.managing_body') }}
                </flux:text>
            </section>

            <section class="space-y-3">
                <flux:heading size="lg" level="2">
                    {{ __('cookies.browser_controls_title') }}
                </flux:heading>

                <flux:text class="leading-7 text-zinc-700">
                    {{ __('cookies.browser_controls_body') }}
                </flux:text>
            </section>

            <section class="space-y-3">
                <flux:heading size="lg" level="2">
                    {{ __('cookies.changes_title') }}
                </flux:heading>

                <flux:text class="leading-7 text-zinc-700">
                    {{ __('cookies.changes_body') }}
                </flux:text>
            </section>

            <section class="space-y-3">
                <flux:heading size="lg" level="2">
                    {{ __('cookies.contact_title') }}
                </flux:heading>

                <flux:text class="leading-7 text-zinc-700">
                    {{ __('cookies.contact_body') }}
                </flux:text>

                <div>
                    <flux:button
                        href="{{ route('contact') }}"
                        variant="outline"
                    >
                        {{ __('cookies.contact_irdi') }}
                    </flux:button>
                </div>
            </section>
        </div>
    </div>
</x-layouts.public>
