<x-layouts.public
    title="{{ __('Terms of Use | IRDI') }}"
    description="{{ __('Read the IRDI Terms of Use governing membership, member profiles, the Member Directory, responsible conduct, member services, and use of the IRDI website.') }}"
>

    {{-- Terms Hero --}}
    <section class="relative overflow-hidden bg-irdi-green">

        <picture class="absolute inset-0">
            <source
                media="(max-width: 640px)"
                srcset="{{ asset('images/mobile/mobile9.png') }}"
            >

            <img
                src="{{ asset('images/carousel/carousel9.png') }}"
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
                    {{ __('Terms of Use') }}
                </h1>

                <p class="mx-auto mt-6 max-w-2xl text-lg leading-8 text-zinc-200">
                    {{ __('The terms that govern use of the IRDI website, membership features, and Member Directory.') }}
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
                                {{ __('Acceptance of These Terms') }}
                            </h2>

                            <p class="mt-3 leading-7">
                                {{ __('These Terms of Use govern your access to and use of the website, membership features, Member Directory, member profiles, and other services provided by the International Responsible Detectorist Institute ("IRDI," "we," "us," or "our").') }}
                            </p>

                            <p class="mt-3 leading-7">
                                {{ __('By accessing or using IRDI services, you agree to comply with these Terms. If you do not agree with these Terms, you should not use the services.') }}
                            </p>
                        </div>

                        <div>
                            <h2 class="text-xl font-semibold text-irdi-green">
                                {{ __('IRDI Membership') }}
                            </h2>

                            <p class="mt-3 leading-7">
                                {{ __('IRDI membership is intended for individuals, organizations, and businesses that support responsible metal detecting, ethical stewardship, respect for landowners, and protection of our shared history.') }}
                            </p>

                            <p class="mt-3 leading-7">
                                {{ __('Membership requires acceptance of applicable IRDI membership requirements, including the Code of Ethics and Best Practices. Members are expected to uphold those standards throughout their membership and are responsible for complying with applicable laws, regulations, permissions, and property-owner requirements wherever they participate in metal detecting activities.') }}
                            </p>
                        </div>

                        <div>
                            <h2 class="text-xl font-semibold text-irdi-green">
                                {{ __('Member in Good Standing') }}
                            </h2>

                            <p class="mt-3 leading-7">
                                {{ __('A Member in Good Standing is a member whose current IRDI membership status meets IRDI’s membership and accountability requirements. Good Standing is a current organizational status and may change if IRDI takes action under its policies or accountability processes.') }}
                            </p>

                            <p class="mt-3 leading-7">
                                {{ __('IRDI membership or Good Standing is not a professional license, certification, background check, personal endorsement, warranty, or guarantee of identity, qualifications, legal compliance, property-owner permission, or future conduct.') }}
                            </p>
                        </div>

                        <div>
                            <h2 class="text-xl font-semibold text-irdi-green">
                                {{ __('Accounts') }}
                            </h2>

                            <p class="mt-3 leading-7">
                                {{ __('Certain IRDI features require an account. You are responsible for providing accurate information, maintaining the confidentiality of your login credentials, and protecting access to your account.') }}
                            </p>

                            <p class="mt-3 leading-7">
                                {{ __('You are responsible for activity conducted through your account and should notify IRDI if you believe your account has been accessed or used without authorization.') }}
                            </p>
                        </div>

                        <div>
                            <h2 class="text-xl font-semibold text-irdi-green">
                                {{ __('Member Profiles') }}
                            </h2>

                            <p class="mt-3 leading-7">
                                {{ __('Eligible IRDI members may create Detectorist, Organization, and Vendor profiles subject to the profile limits and requirements established by IRDI.') }}
                            </p>

                            <p class="mt-3 leading-7">
                                {{ __('You are responsible for the accuracy and appropriateness of information you publish in your profile. You must not impersonate another person or organization or submit false, misleading, unlawful, abusive, or fraudulent information.') }}
                            </p>
                        </div>

                        <div>
                            <h2 class="text-xl font-semibold text-irdi-green">
                                {{ __('Public Member Directory') }}
                            </h2>

                            <p class="mt-3 leading-7">
                                {{ __('Eligible members may choose whether their profiles are displayed in the public Member Directory. The Directory is a current verification tool and may display membership standing, membership information, profile information, and available public property owner feedback. A profile may cease to be publicly available if the member’s eligibility or standing changes.') }}
                            </p>

                            <p class="mt-3 leading-7">
                                {{ __('Directory visibility, IRDI membership, or Good Standing does not constitute a guarantee, certification, endorsement, background check, or warranty concerning any individual, organization, or business.') }}
                            </p>
                        </div>

                        <div>
                            <h2 class="text-xl font-semibold text-irdi-green">
                                {{ __('Responsible Conduct') }}
                            </h2>

                            <p class="mt-3 leading-7">
                                {{ __('Users and members must use IRDI services responsibly and in a manner consistent with the mission and values of the organization.') }}
                            </p>

                            <p class="mt-3 leading-7">
                                {{ __('You may not use IRDI services to harass others, commit fraud, distribute malicious software, interfere with website operation, collect information unlawfully, violate the rights of others, or engage in unlawful activity.') }}
                            </p>
                        </div>

                        <div>
                            <h2 class="text-xl font-semibold text-irdi-green">
                                {{ __('Metal Detecting Activities') }}
                            </h2>

                            <p class="mt-3 leading-7">
                                {{ __('IRDI provides educational, membership, and directory services. IRDI does not grant permission to enter property or conduct metal detecting activities.') }}
                            </p>

                            <p class="mt-3 leading-7">
                                {{ __('Members are solely responsible for obtaining appropriate permission from landowners or other authorized parties and for understanding and complying with applicable local, state, provincial, national, and other laws and regulations.') }}
                            </p>
                        </div>

                        <div>
                            <h2 class="text-xl font-semibold text-irdi-green">
                                {{ __('Property Owner Feedback') }}
                            </h2>

                            <p class="mt-3 leading-7">
                                {{ __('Eligible members may invite property owners to provide feedback about their experience through IRDI. Reviewers are responsible for submitting feedback honestly and in good faith. IRDI may verify access to a reviewer’s email address, but IRDI does not independently verify property ownership or guarantee the truth, accuracy, completeness, or reliability of submitted feedback.') }}
                            </p>

                            <p class="mt-3 leading-7">
                                {{ __('IRDI may display eligible ratings, answers, and public comments, calculate public feedback summaries, and moderate, hide, restore, or otherwise manage feedback when reasonably necessary to enforce IRDI policies, protect users, or maintain the integrity of the feedback system.') }}
                            </p>

                            <p class="mt-3 leading-7">
                                {{ __('Private concerns submitted through the feedback process are intended for authorized IRDI staff review and are not part of the public or member-facing feedback record. IRDI may consider those concerns when administering its accountability, moderation, or enforcement processes.') }}
                            </p>
                        </div>

                        <div>
                            <h2 class="text-xl font-semibold text-irdi-green">
                                {{ __('Member Cards, QR Codes, and Verification') }}
                            </h2>

                            <p class="mt-3 leading-7">
                                {{ __('IRDI may provide member cards, QR codes, profile links, badges, or embeddable membership information to help users identify or verify an IRDI member profile.') }}
                            </p>

                            <p class="mt-3 leading-7">
                                {{ __('These features indicate information maintained by IRDI at the time it is displayed. They do not guarantee a person\'s conduct, qualifications, identity beyond the information provided to IRDI, or compliance with laws or property-owner requirements.') }}
                            </p>
                        </div>

                        <div>
                            <h2 class="text-xl font-semibold text-irdi-green">
                                {{ __('User Content') }}
                            </h2>

                            <p class="mt-3 leading-7">
                                {{ __('You retain responsibility for information, images, biographies, links, and other content you submit to IRDI.') }}
                            </p>

                            <p class="mt-3 leading-7">
                                {{ __('By submitting content for display through IRDI services, you grant IRDI permission to store, process, reproduce, and display that content as reasonably necessary to operate the services and provide the features you have selected.') }}
                            </p>
                        </div>

                        <div>
                            <h2 class="text-xl font-semibold text-irdi-green">
                                {{ __('Prohibited Content') }}
                            </h2>

                            <p class="mt-3 leading-7">
                                {{ __('You may not publish or submit content that is unlawful, fraudulent, threatening, abusive, defamatory, infringing, deceptive, malicious, or otherwise inappropriate for IRDI services.') }}
                            </p>

                            <p class="mt-3 leading-7">
                                {{ __('IRDI may remove content or restrict access to services when reasonably necessary to protect users, enforce these Terms, maintain the integrity of the organization, or comply with applicable law.') }}
                            </p>
                        </div>

                        <div>
                            <h2 class="text-xl font-semibold text-irdi-green">
                                {{ __('Third-Party Websites') }}
                            </h2>

                            <p class="mt-3 leading-7">
                                {{ __('IRDI profiles and pages may contain links to websites or services operated by third parties. IRDI does not control those services and is not responsible for their content, availability, privacy practices, security, or terms.') }}
                            </p>
                        </div>

                        <div>
                            <h2 class="text-xl font-semibold text-irdi-green">
                                {{ __('Moderation and Accountability') }}
                            </h2>

                            <p class="mt-3 leading-7">
                                {{ __('IRDI may review reports, property owner feedback, private concerns, member communications, and other relevant information when reasonably necessary to investigate potential violations, protect users, administer membership standing, or enforce IRDI policies.') }}
                            </p>

                            <p class="mt-3 leading-7">
                                {{ __('Depending on the circumstances and applicable IRDI policies, IRDI may take actions including content moderation, messaging restrictions, warnings, membership review, suspension, restoration, or termination. Internal reports, private concerns, staff notes, and detailed enforcement records are not necessarily made public.') }}
                            </p>
                        </div>

                        <div>
                            <h2 class="text-xl font-semibold text-irdi-green">
                                {{ __('Suspension or Termination') }}
                            </h2>

                            <p class="mt-3 leading-7">
                                {{ __('IRDI may suspend, restrict, restore, or terminate access to accounts, memberships, profiles, messaging, or other website features when reasonably necessary to enforce these Terms or other IRDI policies, administer membership standing, protect IRDI or its users, address abuse or fraud, or comply with legal requirements.') }}
                            </p>
                        </div>

                        <div>
                            <h2 class="text-xl font-semibold text-irdi-green">
                                {{ __('Account Deletion') }}
                            </h2>

                            <p class="mt-3 leading-7">
                                {{ __('Users may be able to delete their IRDI account through account settings. Account deletion may permanently remove associated account, membership, and profile information, but IRDI may retain records when reasonably necessary for legal obligations, security, fraud prevention, dispute resolution, moderation, enforcement, accountability, or protection of IRDI and its users.') }}
                            </p>
                        </div>

                        <div>
                            <h2 class="text-xl font-semibold text-irdi-green">
                                {{ __('Availability of Services') }}
                            </h2>

                            <p class="mt-3 leading-7">
                                {{ __('We work to keep IRDI services available and reliable, but we do not guarantee uninterrupted or error-free operation. Features may be modified, suspended, or discontinued as the organization and services evolve.') }}
                            </p>
                        </div>

                        <div>
                            <h2 class="text-xl font-semibold text-irdi-green">
                                {{ __('Disclaimer of Warranties') }}
                            </h2>

                            <p class="mt-3 leading-7">
                                {{ __('IRDI services are provided on an "as is" and "as available" basis to the extent permitted by law. IRDI does not guarantee that information supplied by members or third parties is complete, accurate, current, or suitable for any particular purpose.') }}
                            </p>
                        </div>

                        <div>
                            <h2 class="text-xl font-semibold text-irdi-green">
                                {{ __('Limitation of Liability') }}
                            </h2>

                            <p class="mt-3 leading-7">
                                {{ __('To the extent permitted by applicable law, IRDI is not responsible for losses, damages, disputes, property damage, injuries, legal violations, or other consequences arising from interactions between users, landowners, members, organizations, vendors, or third parties, or from metal detecting activities undertaken by users.') }}
                            </p>
                        </div>

                        <div>
                            <h2 class="text-xl font-semibold text-irdi-green">
                                {{ __('Changes to These Terms') }}
                            </h2>

                            <p class="mt-3 leading-7">
                                {{ __('IRDI may update these Terms from time to time as services, policies, or legal requirements change. The current version will be posted on this page with an updated revision date.') }}
                            </p>
                        </div>

                        <div>
                            <h2 class="text-xl font-semibold text-irdi-green">
                                {{ __('Contact IRDI') }}
                            </h2>

                            <p class="mt-3 leading-7">
                                {{ __('If you have questions about these Terms of Use, please contact IRDI through the Contact page.') }}
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
