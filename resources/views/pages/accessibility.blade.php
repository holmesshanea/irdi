<x-layouts.public
    :title="__('accessibility.meta_title')"
    :description="__('accessibility.meta_description')"
>
    <div class="mx-auto max-w-4xl">
        <div class="space-y-8">

            <div>
                <flux:heading size="xl" level="1">
                    {{ __('accessibility.statement_title') }}
                </flux:heading>

                <flux:text class="mt-2 text-zinc-600">
                    {{ __('accessibility.last_updated') }}
                </flux:text>
            </div>

            <section class="space-y-3">
                <flux:heading size="lg" level="2">
                    {{ __('accessibility.commitment_title') }}
                </flux:heading>

                <flux:text class="leading-7 text-zinc-700">
                    {{ __('accessibility.commitment_body_1') }}
                </flux:text>

                <flux:text class="leading-7 text-zinc-700">
                    {{ __('accessibility.commitment_body_2') }}
                </flux:text>
            </section>

            <section class="space-y-3">
                <flux:heading size="lg" level="2">
                    {{ __('accessibility.features_title') }}
                </flux:heading>

                <flux:text class="leading-7 text-zinc-700">
                    {{ __('accessibility.features_intro') }}
                </flux:text>

                <ul class="list-disc space-y-2 pl-6 text-zinc-700">
                    <li>{{ __('accessibility.feature_text_size') }}</li>
                    <li>{{ __('accessibility.feature_contrast') }}</li>
                    <li>{{ __('accessibility.feature_motion') }}</li>
                    <li>{{ __('accessibility.feature_links') }}</li>
                    <li>{{ __('accessibility.feature_reset') }}</li>
                </ul>

                <flux:text class="leading-7 text-zinc-700">
                    {{ __('accessibility.preferences_storage') }}
                </flux:text>
            </section>

            <section class="space-y-3">
                <flux:heading size="lg" level="2">
                    {{ __('accessibility.design_title') }}
                </flux:heading>

                <flux:text class="leading-7 text-zinc-700">
                    {{ __('accessibility.design_body') }}
                </flux:text>
            </section>

            <section class="space-y-3">
                <flux:heading size="lg" level="2">
                    {{ __('accessibility.ongoing_title') }}
                </flux:heading>

                <flux:text class="leading-7 text-zinc-700">
                    {{ __('accessibility.ongoing_body') }}
                </flux:text>
            </section>

            <section class="space-y-3">
                <flux:heading size="lg" level="2">
                    {{ __('accessibility.third_party_title') }}
                </flux:heading>

                <flux:text class="leading-7 text-zinc-700">
                    {{ __('accessibility.third_party_body') }}
                </flux:text>
            </section>

            <section class="space-y-3">
                <flux:heading size="lg" level="2">
                    {{ __('accessibility.assistance_title') }}
                </flux:heading>

                <flux:text class="leading-7 text-zinc-700">
                    {{ __('accessibility.assistance_body') }}
                </flux:text>

                <div>
                    <flux:button
                        href="{{ route('contact') }}"
                        variant="outline"
                    >
                        {{ __('accessibility.contact_irdi') }}
                    </flux:button>
                </div>
            </section>

        </div>
    </div>
</x-layouts.public>
