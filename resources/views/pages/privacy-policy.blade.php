<x-layouts.public
    title="{{ __('Privacy Policy | IRDI') }}"
    description="{{ __('Read the IRDI Privacy Policy to learn how we collect, use, store, and protect information related to accounts, membership, member profiles, and the IRDI Member Directory.') }}"
>

    {{-- Privacy Policy Hero --}}
    <section class="relative overflow-hidden bg-irdi-green">

        <picture class="absolute inset-0">
            <source
                media="(max-width: 640px)"
                srcset="{{ asset('images/mobile/mobile10.png') }}"
            >

            <img
                src="{{ asset('images/carousel/carousel10.png') }}"
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
                    {{ __('Privacy Policy') }}
                </h1>

                <p class="mx-auto mt-6 max-w-2xl text-lg leading-8 text-zinc-200">
                    {{ __('How IRDI collects, uses, and protects your information.') }}
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
                                {{ __('Last updated: August 22, 2026') }}
                            </p>
                        </div>

                        <div>
                            <h2 class="text-xl font-semibold text-irdi-green">
                                {{ __('Introduction') }}
                            </h2>

                            <p class="mt-3 leading-7">
                                {{ __('The International Responsible Detectorist Institute ("IRDI," "we," "us," or "our") respects your privacy. This Privacy Policy explains how we collect, use, store, and protect information when you use the IRDI website, create an account, become a member, create a member profile, use the Member Directory, or contact us.') }}
                            </p>
                        </div>

                        <div>
                            <h2 class="text-xl font-semibold text-irdi-green">
                                {{ __('Information We Collect') }}
                            </h2>

                            <p class="mt-3 leading-7">
                                {{ __('We may collect information that you provide directly to IRDI, including:') }}
                            </p>

                            <ul class="mt-3 list-disc space-y-2 pl-6">
                                <li>{{ __('Your name.') }}</li>
                                <li>{{ __('Your email address.') }}</li>
                                <li>{{ __('Your account and membership information.') }}</li>
                                <li>
                                    {{ __('Information you choose to include in a Detectorist, Organization, or Vendor profile.') }}
                                </li>
                                <li>
                                    {{ __('Profile images, biographies, websites, usernames, and location information you choose to provide.') }}
                                </li>
                                <li>
                                    {{ __('Messages and information submitted through contact forms or other communications with IRDI.') }}
                                </li>
                            </ul>
                        </div>

                        <div>
                            <h2 class="text-xl font-semibold text-irdi-green">
                                {{ __('Account and Authentication Information') }}
                            </h2>

                            <p class="mt-3 leading-7">
                                {{ __('When you create an IRDI account, we use your account information to provide access to membership and account features. Email addresses may be used for account verification, password resets, security notifications, and other account-related communications.') }}
                            </p>

                            <p class="mt-3 leading-7">
                                {{ __('Passwords are not stored in plain text. They are processed and stored using secure password hashing provided by the application framework.') }}
                            </p>
                        </div>

                        <div>
                            <h2 class="text-xl font-semibold text-irdi-green">
                                {{ __('Membership Information') }}
                            </h2>

                            <p class="mt-3 leading-7">
                                {{ __('IRDI may maintain information related to your membership, including membership status, membership start date, and your acceptance of IRDI membership requirements such as the Code of Ethics and Best Practices.') }}
                            </p>
                        </div>

                        <div>
                            <h2 class="text-xl font-semibold text-irdi-green">
                                {{ __('Member Profiles and the Public Directory') }}
                            </h2>

                            <p class="mt-3 leading-7">
                                {{ __('IRDI members may create Detectorist, Organization, and Vendor profiles. Members control whether a profile is displayed in the public Member Directory.') }}
                            </p>

                            <p class="mt-3 leading-7">
                                {{ __('If you choose to make a profile publicly visible, information associated with that profile may be available to anyone who visits the IRDI website. This may include your profile name, public username, profile type, profile image, location, biography, website, and membership information.') }}
                            </p>

                            <p class="mt-3 leading-7">
                                {{ __('Your IRDI account email address is not automatically displayed on your public member profile.') }}
                            </p>
                        </div>

                        <div>
                            <h2 class="text-xl font-semibold text-irdi-green">
                                {{ __('How We Use Information') }}
                            </h2>

                            <p class="mt-3 leading-7">
                                {{ __('We may use information we collect to:') }}
                            </p>

                            <ul class="mt-3 list-disc space-y-2 pl-6">
                                <li>{{ __('Create and maintain user accounts.') }}</li>
                                <li>{{ __('Manage IRDI memberships.') }}</li>
                                <li>{{ __('Provide and operate the Member Directory.') }}</li>
                                <li>{{ __('Verify email addresses and account information.') }}</li>
                                <li>{{ __('Send password reset and other security-related messages.') }}</li>
                                <li>{{ __('Respond to questions, requests, and contact-form submissions.') }}</li>
                                <li>{{ __('Maintain the security and proper operation of the website.') }}</li>
                                <li>{{ __('Improve IRDI services and website functionality.') }}</li>
                            </ul>
                        </div>

                        <div>
                            <h2 class="text-xl font-semibold text-irdi-green">
                                {{ __('Public Information') }}
                            </h2>

                            <p class="mt-3 leading-7">
                                {{ __('Information that you intentionally publish through a public IRDI profile should be considered publicly available. Please avoid including sensitive personal information in your public biography, website information, profile image, or other public profile fields.') }}
                            </p>
                        </div>

                        <div>
                            <h2 class="text-xl font-semibold text-irdi-green">
                                {{ __('Cookies and Technical Information') }}
                            </h2>

                            <p class="mt-3 leading-7">
                                {{ __('The IRDI website may use cookies and similar technologies that are necessary for account authentication, security, session management, and normal operation of the website.') }}
                            </p>

                            <p class="mt-3 leading-7">
                                {{ __('Our systems may also process basic technical information such as IP addresses, browser information, request information, and server logs for security, troubleshooting, and website operation.') }}
                            </p>
                        </div>

                        <div>
                            <h2 class="text-xl font-semibold text-irdi-green">
                                {{ __('Sharing of Information') }}
                            </h2>

                            <p class="mt-3 leading-7">
                                {{ __('IRDI does not sell your personal information.') }}
                            </p>

                            <p class="mt-3 leading-7">
                                {{ __('We may share information with service providers when necessary to operate the website, deliver email, host data, provide security, or perform other services on behalf of IRDI.') }}
                            </p>

                            <p class="mt-3 leading-7">
                                {{ __('We may also disclose information when required by law or when reasonably necessary to protect IRDI, our members, our users, or others from fraud, abuse, security threats, or unlawful activity.') }}
                            </p>
                        </div>

                        <div>
                            <h2 class="text-xl font-semibold text-irdi-green">
                                {{ __('Data Security') }}
                            </h2>

                            <p class="mt-3 leading-7">
                                {{ __('We use reasonable administrative and technical measures designed to protect information from unauthorized access, misuse, loss, or disclosure. However, no website, network, or electronic storage system can be guaranteed to be completely secure.') }}
                            </p>
                        </div>

                        <div>
                            <h2 class="text-xl font-semibold text-irdi-green">
                                {{ __('Your Choices') }}
                            </h2>

                            <p class="mt-3 leading-7">
                                {{ __('You can manage certain information through your IRDI account, including your email address, password, member profiles, profile visibility, and other profile information.') }}
                            </p>

                            <p class="mt-3 leading-7">
                                {{ __('You may also choose to delete individual member profiles or delete your IRDI account. Account deletion may permanently remove your account, membership information, and associated profiles.') }}
                            </p>
                        </div>

                        <div>
                            <h2 class="text-xl font-semibold text-irdi-green">
                                {{ __('Data Retention') }}
                            </h2>

                            <p class="mt-3 leading-7">
                                {{ __('We retain information for as long as reasonably necessary to provide IRDI services, maintain memberships and accounts, comply with legal obligations, resolve disputes, protect the security of the website, and enforce applicable policies.') }}
                            </p>
                        </div>

                        <div>
                            <h2 class="text-xl font-semibold text-irdi-green">
                                {{ __('Children\'s Privacy') }}
                            </h2>

                            <p class="mt-3 leading-7">
                                {{ __('IRDI services are not intended to knowingly collect personal information from children where parental consent or other legal authorization would be required. If you believe a child has provided personal information improperly, please contact IRDI.') }}
                            </p>
                        </div>

                        <div>
                            <h2 class="text-xl font-semibold text-irdi-green">
                                {{ __('Changes to This Privacy Policy') }}
                            </h2>

                            <p class="mt-3 leading-7">
                                {{ __('We may update this Privacy Policy from time to time as IRDI services, technology, or legal requirements change. The updated version will be posted on this page with a revised "Last updated" date.') }}
                            </p>
                        </div>

                        <div>
                            <h2 class="text-xl font-semibold text-irdi-green">
                                {{ __('Contact IRDI') }}
                            </h2>

                            <p class="mt-3 leading-7">
                                {{ __('If you have questions about this Privacy Policy or how IRDI handles information, please contact us through the IRDI Contact page.') }}
                            </p>

                            <div class="mt-4">
                                <flux:button
                                    href="{{ route('contact') }}"
                                    variant="outline"
                                >
                                    {{ __('Contact IRDI') }}
                                </flux:button>
                            </div>
                        </div>

                    </div>

                </flux:card>

            </div>

        </div>
    </section>

</x-layouts.public>
