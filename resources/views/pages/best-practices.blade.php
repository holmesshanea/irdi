<x-layouts.public
    title="{{ __('Responsible Metal Detecting Best Practices | IRDI') }}"
    description="{{ __('Learn responsible metal detecting best practices for landowner permission, careful target recovery, property respect, historical preservation, safety, privacy, and ethical detecting.') }}"
>

    {{-- Best Practices Hero --}}
    <section class="relative overflow-hidden bg-irdi-green">

        <picture class="absolute inset-0">
            <source
                media="(max-width: 640px)"
                srcset="{{ asset('images/mobile/mobile2.png') }}"
            >

            <img
                src="{{ asset('images/carousel/carousel2.png') }}"
                alt=""
                class="h-full w-full object-cover"
            >
        </picture>

        <div class="absolute inset-0 bg-irdi-green/75"></div>

        <div class="relative mx-auto max-w-7xl px-6 py-20 lg:px-8 lg:py-28">

            <div class="mx-auto max-w-3xl text-center">

                <p class="text-sm font-semibold uppercase tracking-widest text-irdi-gold-light">
                    International Responsible Detectorist Institute
                </p>

                <h1 class="mt-4 text-4xl font-bold tracking-tight text-white sm:text-5xl lg:text-6xl">
                    {{ __('IRDI Best Practices') }}
                </h1>

                <p class="mx-auto mt-6 max-w-2xl text-lg leading-8 text-zinc-200">
                    {{ __('Practical guidance IRDI members are expected to follow throughout responsible, respectful, and lawful metal detecting.') }}
                </p>

            </div>

        </div>
    </section>

    <section class="bg-white">
        <div class="mx-auto max-w-7xl px-6 py-12 lg:px-8">

            <div class="mx-auto max-w-3xl">

                <flux:card class="p-6 sm:p-8">

                    <div class="space-y-8 text-zinc-700">

                        <div>
                            <p class="text-sm text-zinc-500">
                                {{ __('IRDI Member Resource') }}
                            </p>
                        </div>

                        <div>
                            <h2 class="text-xl font-semibold text-irdi-green">
                                {{ __('Before You Detect') }}
                            </h2>

                            <ul class="mt-3 list-disc space-y-2 pl-6 leading-7">
                                <li>
                                    {{ __('Research the property and determine whether metal detecting is permitted.') }}
                                </li>
                                <li>
                                    {{ __('Obtain clear permission from the landowner or authorized representative before entering private property.') }}
                                </li>
                                <li>
                                    {{ __('Discuss any conditions, restricted areas, time limits, parking instructions, and expectations before beginning.') }}
                                </li>
                                <li>
                                    {{ __('Understand any agreement concerning recovered items before detecting.') }}
                                </li>
                                <li>
                                    {{ __('Review applicable local, state, provincial, national, or other laws and regulations.') }}
                                </li>
                            </ul>
                        </div>

                        <div>
                            <h2 class="text-xl font-semibold text-irdi-green">
                                {{ __('Respect the Property') }}
                            </h2>

                            <ul class="mt-3 list-disc space-y-2 pl-6 leading-7">
                                <li>
                                    {{ __('Stay within the areas for which permission has been granted.') }}
                                </li>
                                <li>
                                    {{ __('Avoid crops, gardens, landscaping, livestock areas, structures, utilities, and sensitive ground unless specifically permitted.') }}
                                </li>
                                <li>
                                    {{ __('Follow gates, access roads, parking instructions, and other property rules.') }}
                                </li>
                                <li>
                                    {{ __('Do not invite additional people onto private property without the landowner\'s permission.') }}
                                </li>
                                <li>
                                    {{ __('Respect a landowner\'s right to end or change permission at any time.') }}
                                </li>
                            </ul>
                        </div>

                        <div>
                            <h2 class="text-xl font-semibold text-irdi-green">
                                {{ __('Recover Targets Carefully') }}
                            </h2>

                            <ul class="mt-3 list-disc space-y-2 pl-6 leading-7">
                                <li>
                                    {{ __('Use recovery techniques appropriate for the terrain and ground conditions.') }}
                                </li>
                                <li>
                                    {{ __('Keep holes as small and controlled as reasonably possible.') }}
                                </li>
                                <li>
                                    {{ __('Replace soil, plugs, turf, gravel, leaves, or other surface material carefully.') }}
                                </li>
                                <li>
                                    {{ __('Avoid unnecessary digging when conditions make responsible recovery difficult.') }}
                                </li>
                                <li>
                                    {{ __('Check the area after recovery to ensure it is safe and presentable.') }}
                                </li>
                            </ul>
                        </div>

                        <div>
                            <h2 class="text-xl font-semibold text-irdi-green">
                                {{ __('Remove Trash Responsibly') }}
                            </h2>

                            <p class="mt-3 leading-7">
                                {{ __('Responsible detectorists frequently recover modern trash and potentially harmful objects along with desirable targets.') }}
                            </p>

                            <ul class="mt-3 list-disc space-y-2 pl-6 leading-7">
                                <li>
                                    {{ __('Remove recovered trash whenever practical rather than returning it to the ground.') }}
                                </li>
                                <li>
                                    {{ __('Dispose of sharp metal, cans, wire, and other hazardous debris safely.') }}
                                </li>
                                <li>
                                    {{ __('Use appropriate containers for sharp or potentially dangerous objects.') }}
                                </li>
                            </ul>
                        </div>

                        <div>
                            <h2 class="text-xl font-semibold text-irdi-green">
                                {{ __('Handle Significant Finds Responsibly') }}
                            </h2>

                            <ul class="mt-3 list-disc space-y-2 pl-6 leading-7">
                                <li>
                                    {{ __('Stop and evaluate the situation if a discovery appears historically, archaeologically, culturally, or legally significant.') }}
                                </li>
                                <li>
                                    {{ __('Avoid damaging or unnecessarily disturbing the surrounding context of potentially important discoveries.') }}
                                </li>
                                <li>
                                    {{ __('Follow applicable reporting requirements.') }}
                                </li>
                                <li>
                                    {{ __('Seek appropriate professional or official guidance when circumstances warrant it.') }}
                                </li>
                                <li>
                                    {{ __('Never knowingly disturb human remains, burial sites, protected archaeological sites, or other legally protected areas.') }}
                                </li>
                            </ul>
                        </div>

                        <div>
                            <h2 class="text-xl font-semibold text-irdi-green">
                                {{ __('Honor Agreements About Finds') }}
                            </h2>

                            <p class="mt-3 leading-7">
                                {{ __('Expectations concerning recovered property should be discussed with the landowner whenever possible before detecting begins.') }}
                            </p>

                            <ul class="mt-3 list-disc space-y-2 pl-6 leading-7">
                                <li>
                                    {{ __('Honor agreements concerning ownership or division of finds.') }}
                                </li>
                                <li>
                                    {{ __('Be transparent about significant recoveries.') }}
                                </li>
                                <li>
                                    {{ __('Respect the landowner\'s property and interests.') }}
                                </li>
                            </ul>
                        </div>

                        <div>
                            <h2 class="text-xl font-semibold text-irdi-green">
                                {{ __('Protect Privacy') }}
                            </h2>

                            <ul class="mt-3 list-disc space-y-2 pl-6 leading-7">
                                <li>
                                    {{ __('Do not publicly disclose private property locations without permission.') }}
                                </li>
                                <li>
                                    {{ __('Avoid publishing identifying details that could encourage unauthorized detecting or trespassing.') }}
                                </li>
                                <li>
                                    {{ __('Ask before publishing photographs of a landowner, their home, property, or other identifying information.') }}
                                </li>
                            </ul>
                        </div>

                        <div>
                            <h2 class="text-xl font-semibold text-irdi-green">
                                {{ __('Communicate Professionally') }}
                            </h2>

                            <ul class="mt-3 list-disc space-y-2 pl-6 leading-7">
                                <li>
                                    {{ __('Be courteous when requesting permission.') }}
                                </li>
                                <li>
                                    {{ __('Clearly explain who you are and what you are asking to do.') }}
                                </li>
                                <li>
                                    {{ __('Accept refusals respectfully and without argument.') }}
                                </li>
                                <li>
                                    {{ __('Thank landowners for allowing access to their property.') }}
                                </li>
                                <li>
                                    {{ __('Follow up appropriately when significant finds or agreed-upon information should be shared.') }}
                                </li>
                            </ul>
                        </div>

                        <div>
                            <h2 class="text-xl font-semibold text-irdi-green">
                                {{ __('Detect Safely') }}
                            </h2>

                            <ul class="mt-3 list-disc space-y-2 pl-6 leading-7">
                                <li>
                                    {{ __('Be aware of weather, terrain, traffic, wildlife, water, and other environmental hazards.') }}
                                </li>
                                <li>
                                    {{ __('Use caution around utilities, wells, foundations, fences, structures, and buried infrastructure.') }}
                                </li>
                                <li>
                                    {{ __('Treat ammunition, explosives, unidentified ordnance, hazardous materials, and suspicious objects with extreme caution.') }}
                                </li>
                                <li>
                                    {{ __('Do not handle potentially dangerous items unnecessarily.') }}
                                </li>
                                <li>
                                    {{ __('Contact appropriate authorities when a discovery presents a safety concern.') }}
                                </li>
                            </ul>
                        </div>

                        <div>
                            <h2 class="text-xl font-semibold text-irdi-green">
                                {{ __('Respect Other Detectorists') }}
                            </h2>

                            <ul class="mt-3 list-disc space-y-2 pl-6 leading-7">
                                <li>
                                    {{ __('Respect another detectorist\'s lawful permissions and relationships with landowners.') }}
                                </li>
                                <li>
                                    {{ __('Do not pressure landowners for access based on information improperly obtained from another detectorist.') }}
                                </li>
                                <li>
                                    {{ __('Share knowledge constructively and encourage responsible practices.') }}
                                </li>
                                <li>
                                    {{ __('Resolve disagreements respectfully.') }}
                                </li>
                            </ul>
                        </div>

                        <div>
                            <h2 class="text-xl font-semibold text-irdi-green">
                                {{ __('Represent Metal Detecting Positively') }}
                            </h2>

                            <p class="mt-3 leading-7">
                                {{ __('Every interaction with a landowner, public official, historian, archaeologist, passerby, or fellow detectorist can influence how the hobby is perceived.') }}
                            </p>

                            <ul class="mt-3 list-disc space-y-2 pl-6 leading-7">
                                <li>
                                    {{ __('Be prepared to explain responsible detecting practices respectfully.') }}
                                </li>
                                <li>
                                    {{ __('Demonstrate good recovery techniques.') }}
                                </li>
                                <li>
                                    {{ __('Leave places clean and safe.') }}
                                </li>
                                <li>
                                    {{ __('Avoid behavior that creates unnecessary conflict or damages public trust.') }}
                                </li>
                            </ul>
                        </div>

                        <div>
                            <h2 class="text-xl font-semibold text-irdi-green">
                                {{ __('Continue Learning') }}
                            </h2>

                            <p class="mt-3 leading-7">
                                {{ __('Laws, technology, recovery techniques, historical knowledge, and community expectations can change over time. Responsible detectorists should continue learning and improving their practices.') }}
                            </p>

                            <p class="mt-3 leading-7">
                                {{ __('IRDI members are encouraged to share responsible practices, help educate newer detectorists, and lead through positive example.') }}
                            </p>
                        </div>

                        <div>
                            <h2 class="text-xl font-semibold text-irdi-green">
                                {{ __('The Standard We Set') }}
                            </h2>

                            <p class="mt-3 leading-7">
                                {{ __('Responsible metal detecting depends on trust. By respecting property, protecting history, following the law, recovering targets carefully, and treating others with integrity, IRDI members help strengthen that trust, maintain the standards expected of membership, and protect the future of the hobby.') }}
                            </p>
                        </div>

                    </div>

                    @auth
                        @if (auth()->user()->hasVerifiedEmail())
                            <livewire:membership.best-practices-agreement />
                        @endif
                    @endauth

                </flux:card>

                <div class="mt-8 flex flex-wrap items-center justify-center gap-3">

                    <flux:button
                        href="{{ route('code-of-ethics') }}"
                        variant="outline"
                        icon="document-text"
                    >
                        {{ __('View Code of Ethics') }}
                    </flux:button>

                    <flux:button
                        href="{{ route('resources') }}"
                        variant="outline"
                        icon="arrow-left"
                    >
                        {{ __('Back to Resources') }}
                    </flux:button>

                </div>

            </div>

        </div>
    </section>

</x-layouts.public>
