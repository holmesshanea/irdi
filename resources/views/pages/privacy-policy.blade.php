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
                                {{ __('Last updated: September 15, 2026') }}
                            </p>
                        </div>

                        <div>
                            <h2 class="text-xl font-semibold text-irdi-green">
                                {{ __('Introduction') }}
                            </h2>

                            <p class="mt-3 leading-7">
                                {{ __('The International Responsible Detectorist Institute ("IRDI," "we," "us," or "our") respects your privacy. This Privacy Policy explains how we collect, use, store, and protect information when you use the IRDI website, create an account, become a member, create a member profile, use the Member Directory, exchange member messages, submit or receive property owner feedback, participate in IRDI accountability processes, or contact us.') }}
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
                                    {{ __('Messages and information submitted through contact forms, member messaging, reports, or other communications with IRDI.') }}
                                </li>
                                <li>
                                    {{ __('Property owner feedback, review invitation information, reviewer email addresses, ratings, comments, and related submission information.') }}
                                </li>
                                <li>
                                    {{ __('Private concerns submitted through the property owner feedback process and information created or maintained as part of moderation, enforcement, appeals, or other accountability processes.') }}
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
                                {{ __('IRDI may maintain information related to your membership, including membership status and standing, membership start date, acceptance of the Code of Ethics and Best Practices, and records reasonably necessary to administer IRDI accountability, moderation, enforcement, review, suspension, restoration, or termination processes.') }}
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
                                {{ __('If you choose to make a profile publicly visible, information associated with that profile may be available to anyone who visits the IRDI website. This may include your profile name, public username, profile type, profile image, location, biography, website, current membership standing, membership information, and available public property owner feedback.') }}
                            </p>

                            <p class="mt-3 leading-7">
                                {{ __('Your IRDI account email address is not automatically displayed on your public member profile.') }}
                            </p>
                        </div>

                        <div>
                            <h2 class="text-xl font-semibold text-irdi-green">
                                {{ __('Property Owner Feedback and Private Concerns') }}
                            </h2>

                            <p class="mt-3 leading-7">
                                {{ __('IRDI members may invite property owners to submit feedback through private, single-use invitations. IRDI collects the reviewer email address to verify access to that email address and to help prevent duplicate or fraudulent submissions. Email verification does not mean IRDI has independently verified property ownership or the truth or accuracy of the feedback submitted.') }}
                            </p>

                            <p class="mt-3 leading-7">
                                {{ __('Ratings, answers, and public comments from eligible feedback may be displayed on or used to calculate information shown on a member’s public profile, subject to IRDI moderation and visibility rules.') }}
                            </p>

                            <p class="mt-3 leading-7">
                                {{ __('A property owner may also submit a Private Concern and Private Concern Details. These fields are not displayed publicly and are not shown in the member-facing review experience. They are available only to authorized IRDI staff for review and may be used as part of IRDI’s accountability, moderation, or enforcement processes.') }}
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
                                <li>{{ __('Administer property owner feedback, moderation, member reports, and accountability processes.') }}</li>
                                <li>{{ __('Evaluate and maintain current membership standing and enforce IRDI policies.') }}</li>
                            </ul>
                        </div>

                        <div>
                            <h2 class="text-xl font-semibold text-irdi-green">
                                {{ __('Public Information') }}
                            </h2>

                            <p class="mt-3 leading-7">
                                {{ __('Information that you intentionally publish through a public IRDI profile should be considered publicly available. Current membership standing and eligible public property owner feedback may also be displayed as part of IRDI’s public verification features. Private concerns, reviewer email addresses, internal moderation notes, and private enforcement details are not intended for public display. Please avoid including sensitive personal information in public profile fields or public feedback comments.') }}
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
                                {{ __('You may also choose to delete individual member profiles or delete your IRDI account. Account deletion may permanently remove or de-identify account, membership, and profile information, but IRDI may retain certain records when reasonably necessary for security, fraud prevention, legal obligations, dispute resolution, moderation, enforcement, accountability, or protection of IRDI and its users.') }}
                            </p>
                        </div>

                        <div>
                            <h2 class="text-xl font-semibold text-irdi-green">
                                {{ __('Data Retention') }}
                            </h2>

                            <p class="mt-3 leading-7">
                                {{ __('We retain information for as long as reasonably necessary to provide IRDI services, maintain memberships and accounts, administer property owner feedback and accountability processes, comply with legal obligations, resolve disputes, prevent abuse or repeated violations, protect the security and integrity of IRDI, and enforce applicable policies. Different categories of information may be retained for different periods depending on these purposes.') }}
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
