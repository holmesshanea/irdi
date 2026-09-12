<x-layouts.public
    title="Accessibility | IRDI"
    description="Learn about IRDI's commitment to providing an accessible and inclusive website experience."
>
    <div class="mx-auto max-w-4xl">
        <div class="space-y-8">

            <div>
                <flux:heading size="xl">
                    Accessibility Statement
                </flux:heading>

                <flux:text class="mt-2 text-zinc-600">
                    Last updated: September 12, 2026
                </flux:text>
            </div>

            <section class="space-y-3">
                <flux:heading size="lg">
                    Our Commitment
                </flux:heading>

                <flux:text class="leading-7 text-zinc-700">
                    The International Responsible Detectorist Institute (IRDI)
                    is committed to providing a website that is accessible to
                    the widest possible audience, including people with
                    disabilities.
                </flux:text>

                <flux:text class="leading-7 text-zinc-700">
                    We strive to provide an inclusive online experience and
                    continue to improve the accessibility and usability of our
                    website as its content and features evolve.
                </flux:text>
            </section>

            <section class="space-y-3">
                <flux:heading size="lg">
                    Accessibility Features
                </flux:heading>

                <flux:text class="leading-7 text-zinc-700">
                    IRDI provides accessibility preferences that allow visitors
                    to adjust the presentation of the website according to
                    their needs.
                </flux:text>

                <ul class="list-disc space-y-2 pl-6 text-zinc-700">
                    <li>Increase or decrease text size</li>
                    <li>Increase visual contrast</li>
                    <li>Reduce animations and motion</li>
                    <li>Highlight links for easier identification</li>
                    <li>Reset accessibility preferences at any time</li>
                </ul>

                <flux:text class="leading-7 text-zinc-700">
                    Accessibility preferences are stored in your browser so
                    your selections can remain available when you return to
                    the website.
                </flux:text>
            </section>

            <section class="space-y-3">
                <flux:heading size="lg">
                    Accessible Website Design
                </flux:heading>

                <flux:text class="leading-7 text-zinc-700">
                    In addition to visitor-controlled accessibility
                    preferences, IRDI works to make accessibility part of the
                    website itself. This includes efforts to provide clear
                    navigation, readable content, keyboard-accessible
                    controls, visible focus indicators, meaningful image
                    descriptions, accessible forms, and sufficient color
                    contrast.
                </flux:text>
            </section>

            <section class="space-y-3">
                <flux:heading size="lg">
                    Ongoing Improvements
                </flux:heading>

                <flux:text class="leading-7 text-zinc-700">
                    Accessibility is an ongoing effort. As IRDI adds or changes
                    website content and functionality, we will continue to
                    review accessibility and make improvements where
                    appropriate.
                </flux:text>
            </section>

            <section class="space-y-3">
                <flux:heading size="lg">
                    Third-Party Content
                </flux:heading>

                <flux:text class="leading-7 text-zinc-700">
                    Some pages may contain links to or content provided by
                    third-party websites or services. IRDI does not control
                    the accessibility of third-party websites, but we
                    encourage providers to make their content and services
                    accessible.
                </flux:text>
            </section>

            <section class="space-y-3">
                <flux:heading size="lg">
                    Need Assistance?
                </flux:heading>

                <flux:text class="leading-7 text-zinc-700">
                    If you experience difficulty accessing any part of the
                    IRDI website, encounter an accessibility barrier, or have
                    suggestions for improving accessibility, please contact
                    us. We welcome feedback and will make reasonable efforts
                    to provide the information or assistance you need.
                </flux:text>

                <div>
                    <flux:button
                        href="{{ route('contact') }}"
                        variant="outline"
                    >
                        Contact IRDI
                    </flux:button>
                </div>
            </section>

        </div>
    </div>
</x-layouts.public>
