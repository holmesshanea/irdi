<x-layouts.public
    title="{{ __('Responsible Metal Detecting FAQ | IRDI') }}"
    description="{{ __('Find answers about IRDI membership, good standing, public member verification, property owner feedback, accountability, member profiles, and IRDI accounts.') }}"
>

    {{-- FAQ Hero --}}
    <section class="relative overflow-hidden bg-irdi-green">

        <picture class="absolute inset-0">
            <source
                media="(max-width: 640px)"
                srcset="{{ asset('images/mobile/mobile5.png') }}"
            >

            <img
                src="{{ asset('images/carousel/carousel5.png') }}"
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
                    {{ __('Frequently Asked Questions') }}
                </h1>

                <p class="mx-auto mt-6 max-w-2xl text-lg leading-8 text-zinc-200">
                    {{ __('Answers to common questions about IRDI membership, good standing, public verification, property owner feedback, accountability, member profiles, and managing your account.') }}
                </p>

            </div>

        </div>
    </section>

    {{-- FAQ Content --}}
    <section class="bg-white">
        <div class="mx-auto max-w-7xl px-6 py-16 lg:px-8 lg:py-20">

            <div class="mx-auto max-w-4xl">


                {{-- About IRDI --}}
                <section class="mb-14">

                    <div class="mb-3 rounded-xl bg-irdi-green/10 px-5 py-4">
                        <flux:heading size="xl" level="2" class="text-irdi-green">
                            {{ __('About IRDI') }}
                        </flux:heading>

                        <div class="mt-2 h-1 w-12 rounded-full bg-irdi-gold"></div>
                    </div>

                    <flux:accordion>

                        <flux:accordion.item heading="{{ __('What is IRDI?') }}">
                            <div class="space-y-3 text-sm leading-6 text-zinc-600 dark:text-zinc-400">
                                <p>
                                    {{ __('IRDI stands for the International Responsible Detectorist Institute. It is an international membership organization dedicated to promoting ethical metal detecting, protecting our shared history, respecting landowners, and strengthening trust through shared standards, public membership verification, property owner feedback, and ongoing accountability.') }}
                                </p>

                                <p>
                                    {{ __('IRDI encourages detectorists to protect history, respect private property, uphold responsible detecting standards throughout their membership, and lead by example within the metal detecting community.') }}
                                </p>
                            </div>
                        </flux:accordion.item>


                        <flux:accordion.item heading="{{ __('Who can join IRDI?') }}">
                            <p class="text-sm leading-6 text-zinc-600 dark:text-zinc-400">
                                {{ __('IRDI membership is intended for detectorists, organizations, and vendors that support responsible detecting, ethical stewardship, respect for landowners, and the protection of our shared history.') }}
                            </p>
                        </flux:accordion.item>


                        <flux:accordion.item heading="{{ __('Is IRDI membership free?') }}">
                            <p class="text-sm leading-6 text-zinc-600 dark:text-zinc-400">
                                {{ __("Yes. IRDI membership is free. Becoming a member is a commitment to follow IRDI's Code of Ethics and Best Practices and to represent the hobby responsibly.") }}
                            </p>
                        </flux:accordion.item>


                        <flux:accordion.item heading="{{ __('Does IRDI membership certify or guarantee a detectorist?') }}">
                            <p class="text-sm leading-6 text-zinc-600 dark:text-zinc-400">
                                {{ __("No. IRDI membership and good-standing status reflect a member's current status with IRDI. They should not be considered a license, professional certification, background check, endorsement, or guarantee of an individual's past or future conduct.") }}
                            </p>
                        </flux:accordion.item>

                    </flux:accordion>

                </section>


                {{-- Membership and Registration --}}
                <section class="mb-14">

                    <div class="mb-3 rounded-xl bg-irdi-green/10 px-5 py-4">
                        <flux:heading size="xl" level="2" class="text-irdi-green">
                            {{ __('Membership & Registration') }}
                        </flux:heading>

                        <div class="mt-2 h-1 w-12 rounded-full bg-irdi-gold"></div>
                    </div>

                    <flux:accordion>

                        <flux:accordion.item heading="{{ __('How do I become an IRDI Member?') }}">
                            <div class="space-y-3 text-sm leading-6 text-zinc-600 dark:text-zinc-400">
                                <p>
                                    {{ __('Begin by creating an IRDI account. After registering, you will need to verify your email address.') }}
                                </p>

                                <p>
                                    {{ __('You will then be asked to read and agree to the IRDI Code of Ethics and Best Practices. Once those steps are complete, your membership becomes active.') }}
                                </p>
                            </div>
                        </flux:accordion.item>


                        <flux:accordion.item heading="{{ __('Why do I need to verify my email address?') }}">
                            <p class="text-sm leading-6 text-zinc-600 dark:text-zinc-400">
                                {{ __('Email verification confirms that you have access to the email address used to create your account. It also helps IRDI protect member accounts and ensures important account messages can reach you.') }}
                            </p>
                        </flux:accordion.item>


                        <flux:accordion.item heading="{{ __('Why do I need to agree to the Code of Ethics and Best Practices?') }}">
                            <p class="text-sm leading-6 text-zinc-600 dark:text-zinc-400">
                                {{ __('IRDI membership is built around responsible metal detecting. Agreeing to the Code of Ethics and Best Practices confirms your commitment to protecting history, respecting property and landowners, following responsible detecting practices, and representing the hobby positively.') }}
                            </p>
                        </flux:accordion.item>


                        <flux:accordion.item heading="{{ __('When does my IRDI membership become active?') }}">
                            <p class="text-sm leading-6 text-zinc-600 dark:text-zinc-400">
                                {{ __('Your membership becomes active after you have registered your account, verified your email address, and agreed to both the IRDI Code of Ethics and Best Practices.') }}
                            </p>
                        </flux:accordion.item>


                        <flux:accordion.item heading="{{ __('What does my Member Since date mean?') }}">
                            <p class="text-sm leading-6 text-zinc-600 dark:text-zinc-400">
                                {{ __('Your Member Since date records when your IRDI membership became active. It may be displayed on your account, public member profile, and IRDI Member Card.') }}
                            </p>
                        </flux:accordion.item>

                    </flux:accordion>

                </section>


                {{-- Good Standing and Accountability --}}
                <section class="mb-14">

                    <div class="mb-3 rounded-xl bg-irdi-green/10 px-5 py-4">
                        <flux:heading size="xl" level="2" class="text-irdi-green">
                            {{ __('Good Standing & Accountability') }}
                        </flux:heading>

                        <div class="mt-2 h-1 w-12 rounded-full bg-irdi-gold"></div>
                    </div>

                    <flux:accordion>

                        <flux:accordion.item heading="{{ __('What does Member in Good Standing mean?') }}">
                            <div class="space-y-3 text-sm leading-6 text-zinc-600 dark:text-zinc-400">
                                <p>
                                    {{ __('Member in Good Standing means the member currently meets IRDI’s membership and accountability requirements. Good Standing may be affected by active accountability actions under IRDI policy, including probation or an administrative review, and is not current while membership is suspended or banned.') }}
                                </p>

                                <p>
                                    {{ __('Good standing is a current membership-status indicator. It is not a background check, endorsement, certification, or guarantee of past or future conduct.') }}
                                </p>
                            </div>
                        </flux:accordion.item>

                        <flux:accordion.item heading="{{ __('Can a member’s Good Standing status change?') }}">
                            <p class="text-sm leading-6 text-zinc-600 dark:text-zinc-400">
                                {{ __('Yes. Membership standing can change when IRDI takes membership action under its accountability process. Public verification should therefore be based on the member’s current live profile rather than a saved Member Card or screenshot.') }}
                            </p>
                        </flux:accordion.item>

                        <flux:accordion.item heading="{{ __('How does IRDI handle concerns about a member?') }}">
                            <p class="text-sm leading-6 text-zinc-600 dark:text-zinc-400">
                                {{ __('IRDI may review concerns involving member conduct or use of IRDI services. Depending on the circumstances and available information, IRDI may take proportionate administrative action, which can include a warning, probation, suspension, restoration, or a ban.') }}
                            </p>
                        </flux:accordion.item>

                        <flux:accordion.item heading="{{ __('Are disciplinary details shown publicly?') }}">
                            <p class="text-sm leading-6 text-zinc-600 dark:text-zinc-400">
                                {{ __('No. Public profiles are intended to show a member’s current IRDI standing and appropriate public trust information, not private reports, confidential concerns, staff notes, or detailed disciplinary records.') }}
                            </p>
                        </flux:accordion.item>

                        <flux:accordion.item heading="{{ __('Does Good Standing mean IRDI has background checked or personally vetted the member?') }}">
                            <p class="text-sm leading-6 text-zinc-600 dark:text-zinc-400">
                                {{ __('No. Good Standing reflects the member’s current status within IRDI’s membership and accountability system. IRDI does not present Good Standing as a criminal background check, personal endorsement, or guarantee of trustworthiness or future behavior.') }}
                            </p>
                        </flux:accordion.item>

                    </flux:accordion>

                </section>


                {{-- Member Profile --}}
                <section class="mb-14">

                    <div class="mb-3 rounded-xl bg-irdi-green/10 px-5 py-4">
                        <flux:heading size="xl" level="2" class="text-irdi-green">
                            {{ __('Your Member Profile') }}
                        </flux:heading>

                        <div class="mt-2 h-1 w-12 rounded-full bg-irdi-gold"></div>
                    </div>

                    <flux:accordion>

                        <flux:accordion.item heading="{{ __('What is an IRDI Member Profile?') }}">
                            <p class="text-sm leading-6 text-zinc-600 dark:text-zinc-400">
                                {{ __('Your Member Profile is your detectorist profile within IRDI. It allows you to provide information about yourself, including your profile name, location, biography, profile image, website, and other information you choose to make available.') }}
                            </p>
                        </flux:accordion.item>


                        <flux:accordion.item heading="{{ __('How many Member Profiles can I create?') }}">
                            <p class="text-sm leading-6 text-zinc-600 dark:text-zinc-400">
                                {{ __('Each IRDI member can create one detectorist Member Profile.') }}
                            </p>
                        </flux:accordion.item>


                        <flux:accordion.item heading="{{ __('What is my IRDI username?') }}">
                            <div class="space-y-3 text-sm leading-6 text-zinc-600 dark:text-zinc-400">
                                <p>
                                    {{ __('Your username uniquely identifies your Member Profile and becomes part of your public IRDI profile address.') }}
                                </p>

                                <p>
                                    {{ __('Choose your username carefully. Once your Member Profile has been created, the username is permanent and cannot be changed.') }}
                                </p>
                            </div>
                        </flux:accordion.item>


                        <flux:accordion.item heading="{{ __('Can I change the name on my account?') }}">
                            <p class="text-sm leading-6 text-zinc-600 dark:text-zinc-400">
                                {{ __('Your account name is established when you register and cannot be changed through your account settings. Other profile information can be managed through your Member Profile.') }}
                            </p>
                        </flux:accordion.item>


                        <flux:accordion.item heading="{{ __('What information can I add to my Member Profile?') }}">
                            <p class="text-sm leading-6 text-zinc-600 dark:text-zinc-400">
                                {{ __('Your profile can include a profile image, public location, biography, website, profile name, and other member information provided by IRDI. You can manage this information from your account.') }}
                            </p>
                        </flux:accordion.item>


                        <flux:accordion.item heading="{{ __('Why does IRDI ask for my location?') }}">
                            <p class="text-sm leading-6 text-zinc-600 dark:text-zinc-400">
                                {{ __('Your public location helps other people understand the general area where you are located. IRDI uses fields such as city, state or province, and country rather than requiring a street address for your public profile.') }}
                            </p>
                        </flux:accordion.item>


                        <flux:accordion.item heading="{{ __('What does Profile Complete mean?') }}">
                            <p class="text-sm leading-6 text-zinc-600 dark:text-zinc-400">
                                {{ __('IRDI may indicate whether your profile is complete based on important profile information such as your profile image, location, and biography. Completing your profile helps visitors learn more about you when viewing your public Member Profile.') }}
                            </p>
                        </flux:accordion.item>

                    </flux:accordion>

                </section>


                {{-- Directory --}}
                <section class="mb-14">

                    <div class="mb-3 rounded-xl bg-irdi-green/10 px-5 py-4">
                        <flux:heading size="xl" level="2" class="text-irdi-green">
                            {{ __('Member Directory') }}
                        </flux:heading>

                        <div class="mt-2 h-1 w-12 rounded-full bg-irdi-gold"></div>
                    </div>

                    <flux:accordion>

                        <flux:accordion.item heading="{{ __('What is the IRDI Member Directory?') }}">
                            <p class="text-sm leading-6 text-zinc-600 dark:text-zinc-400">
                                {{ __('The Member Directory is a public verification tool for current IRDI members in good standing who have chosen to make their Member Profile visible. Visitors can use it to confirm current membership and standing and open a member’s public profile for additional information.') }}
                            </p>
                        </flux:accordion.item>


                        <flux:accordion.item heading="{{ __('Do I have to appear in the Member Directory?') }}">
                            <p class="text-sm leading-6 text-zinc-600 dark:text-zinc-400">
                                {{ __('No. Directory participation is optional. You control whether your Member Profile is visible in the public Member Directory.') }}
                            </p>
                        </flux:accordion.item>


                        <flux:accordion.item heading="{{ __('How do I make my profile visible in the Member Directory?') }}">
                            <p class="text-sm leading-6 text-zinc-600 dark:text-zinc-400">
                                {{ __('Edit your Member Profile from your account and enable directory visibility. You can return to your profile settings later if you want to hide it from the directory again.') }}
                            </p>
                        </flux:accordion.item>


                        <flux:accordion.item heading="{{ __('Can people still view my profile if I hide it from the directory?') }}">
                            <p class="text-sm leading-6 text-zinc-600 dark:text-zinc-400">
                                {{ __('A profile that is not publicly visible is not available to regular public visitors through the Member Directory. You can still manage and view your own profile while signed into your account.') }}
                            </p>
                        </flux:accordion.item>


                        <flux:accordion.item heading="{{ __('How can someone verify that I am an active IRDI Member?') }}">
                            <p class="text-sm leading-6 text-zinc-600 dark:text-zinc-400">
                                {{ __('If you have chosen to make your profile public, someone can search the IRDI Member Directory or visit your public Member Profile to confirm your current membership and standing. Your Member Card and QR code can direct someone to that live profile, which is the authoritative source for your current public IRDI status.') }}
                            </p>
                        </flux:accordion.item>


                        <flux:accordion.item heading="{{ __('How can I find another IRDI Member?') }}">
                            <p class="text-sm leading-6 text-zinc-600 dark:text-zinc-400">
                                {{ __('Use the Member Directory to search public IRDI Member Profiles. Directory search and location filters can help narrow the results.') }}
                            </p>
                        </flux:accordion.item>

                    </flux:accordion>

                </section>


                {{-- Member Card --}}
                <section class="mb-14">

                    <div class="mb-3 rounded-xl bg-irdi-green/10 px-5 py-4">
                        <flux:heading size="xl" level="2" class="text-irdi-green">
                            {{ __('IRDI Member Card') }}
                        </flux:heading>

                        <div class="mt-2 h-1 w-12 rounded-full bg-irdi-gold"></div>
                    </div>

                    <flux:accordion>

                        <flux:accordion.item heading="{{ __('What is the IRDI Member Card?') }}">
                            <p class="text-sm leading-6 text-zinc-600 dark:text-zinc-400">
                                {{ __('Your IRDI Member Card is a digital card connected to your Member Profile. It displays identifying member information and provides a convenient way to direct someone to your live public IRDI profile. The card itself is not permanent proof of current membership or standing.') }}
                            </p>
                        </flux:accordion.item>


                        <flux:accordion.item heading="{{ __('What does the QR code on my Member Card do?') }}">
                            <p class="text-sm leading-6 text-zinc-600 dark:text-zinc-400">
                                {{ __('The QR code links to your live IRDI Member Profile. Someone can scan it to view your current public membership and standing information. Because a member’s status can change, the live profile—not a saved image or copy of the card—is the authoritative source for current IRDI verification.') }}
                            </p>
                        </flux:accordion.item>


                        <flux:accordion.item heading="{{ __('Can I display my IRDI Member Card on another website?') }}">
                            <p class="text-sm leading-6 text-zinc-600 dark:text-zinc-400">
                                {{ __('Yes. IRDI provides an embeddable version of your Member Card that can be placed on a compatible website using the embed code provided with your card.') }}
                            </p>
                        </flux:accordion.item>

                    </flux:accordion>

                </section>


                {{-- Property Owner Reviews --}}
                <section class="mb-14">

                    <div class="mb-3 rounded-xl bg-irdi-green/10 px-5 py-4">
                        <flux:heading size="xl" level="2" class="text-irdi-green">
                            {{ __('Property Owner Feedback') }}
                        </flux:heading>

                        <div class="mt-2 h-1 w-12 rounded-full bg-irdi-gold"></div>
                    </div>

                    <flux:accordion>

                        <flux:accordion.item heading="{{ __('What is Property Owner Feedback?') }}">
                            <p class="text-sm leading-6 text-zinc-600 dark:text-zinc-400">
                                {{ __('Property Owner Feedback allows a property owner to provide feedback about their experience with an IRDI member after allowing that member to detect on their property. It adds real-world context to the member’s public trust record while remaining subject to IRDI review and visibility rules.') }}
                            </p>
                        </flux:accordion.item>


                        <flux:accordion.item heading="{{ __('How do I request a review from a property owner?') }}">
                            <p class="text-sm leading-6 text-zinc-600 dark:text-zinc-400">
                                {{ __('From your IRDI account, you can create a Property Owner Review invitation for your Member Profile. The invitation provides the property owner with a unique link they can use to submit their review.') }}
                            </p>
                        </flux:accordion.item>


                        <flux:accordion.item heading="{{ __('Does a property owner need an IRDI account to leave a review?') }}">
                            <p class="text-sm leading-6 text-zinc-600 dark:text-zinc-400">
                                {{ __('No. Property owners do not need to become IRDI Members or create an IRDI account in order to respond to a valid Property Owner Review invitation.') }}
                            </p>
                        </flux:accordion.item>


                        <flux:accordion.item heading="{{ __('What does a Property Owner Review ask about?') }}">
                            <p class="text-sm leading-6 text-zinc-600 dark:text-zinc-400">
                                {{ __("The current Property Owner Feedback questionnaire asks about the overall experience, the member's respect for the property, communication and courtesy, whether the property was left in satisfactory condition, and whether the property owner would allow the member to return. The property owner may also provide written comments and can confidentially tell IRDI about a concern.") }}
                            </p>
                        </flux:accordion.item>


                        <flux:accordion.item heading="{{ __('Can anyone leave a review on my profile?') }}">
                            <p class="text-sm leading-6 text-zinc-600 dark:text-zinc-400">
                                {{ __('No. Property Owner Feedback is submitted through unique review invitations associated with an IRDI Member Profile rather than through an unrestricted public review form. Reviewers must also verify their email address before submitting feedback.') }}
                            </p>
                        </flux:accordion.item>


                        <flux:accordion.item heading="{{ __('How long does a review invitation remain available?') }}">
                            <p class="text-sm leading-6 text-zinc-600 dark:text-zinc-400">
                                {{ __('A Property Owner Review invitation is available for a limited period. Invitations currently expire 30 days after they are created and are intended for a single review.') }}
                            </p>
                        </flux:accordion.item>


                        <flux:accordion.item heading="{{ __('Can the same review invitation be used more than once?') }}">
                            <p class="text-sm leading-6 text-zinc-600 dark:text-zinc-400">
                                {{ __('No. Review invitations are intended for one-time use. Once a review has been successfully submitted through an invitation, that invitation cannot be used to submit another review.') }}
                            </p>
                        </flux:accordion.item>


                        <flux:accordion.item heading="{{ __('What review information appears on my public profile?') }}">
                            <p class="text-sm leading-6 text-zinc-600 dark:text-zinc-400">
                                {{ __('IRDI may summarize visible Property Owner Feedback on your public Member Profile, including the number of visible reviews, an overall rating when enough reviews are available, and information about whether property owners would allow you to return. Confidential concerns and staff moderation information are not displayed publicly.') }}
                            </p>
                        </flux:accordion.item>

                    </flux:accordion>

                </section>


                {{-- Property Owner Feedback: Verification and Privacy --}}
                <section class="mb-14">

                    <div class="mb-3 rounded-xl bg-irdi-green/10 px-5 py-4">
                        <flux:heading size="xl" level="2" class="text-irdi-green">
                            {{ __('Feedback Verification & Privacy') }}
                        </flux:heading>

                        <div class="mt-2 h-1 w-12 rounded-full bg-irdi-gold"></div>
                    </div>

                    <flux:accordion>

                        <flux:accordion.item heading="{{ __('Does IRDI verify property owners who submit feedback?') }}">
                            <p class="text-sm leading-6 text-zinc-600 dark:text-zinc-400">
                                {{ __('IRDI requires a reviewer to verify access to the email address used for the review. IRDI does not independently verify property ownership or guarantee the accuracy of the contents of a review.') }}
                            </p>
                        </flux:accordion.item>

                        <flux:accordion.item heading="{{ __('When is a public Property Owner Rating displayed?') }}">
                            <p class="text-sm leading-6 text-zinc-600 dark:text-zinc-400">
                                {{ __('A public Property Owner Rating is displayed after a member has at least three visible Property Owner Reviews. Before that threshold is reached, the public profile may show the number of visible reviews without displaying an average rating.') }}
                            </p>
                        </flux:accordion.item>

                        <flux:accordion.item heading="{{ __('What is the confidential concern option?') }}">
                            <p class="text-sm leading-6 text-zinc-600 dark:text-zinc-400">
                                {{ __('A property owner submitting feedback can privately indicate that they have a concern they want IRDI to know about and can provide additional details. This information is intended for authorized IRDI staff review and is not shown on the member’s public profile or member-facing review pages.') }}
                            </p>
                        </flux:accordion.item>

                        <flux:accordion.item heading="{{ __('Can IRDI hide or moderate Property Owner Feedback?') }}">
                            <p class="text-sm leading-6 text-zinc-600 dark:text-zinc-400">
                                {{ __('Yes. Authorized IRDI staff can review Property Owner Feedback and may hide or restore a review when appropriate. Public profile statistics are based only on reviews that are currently visible.') }}
                            </p>
                        </flux:accordion.item>

                    </flux:accordion>

                </section>


                {{-- Contact and Privacy --}}
                <section class="mb-14">

                    <div class="mb-3 rounded-xl bg-irdi-green/10 px-5 py-4">
                        <flux:heading size="xl" level="2" class="text-irdi-green">
                            {{ __('Contact & Privacy') }}
                        </flux:heading>

                        <div class="mt-2 h-1 w-12 rounded-full bg-irdi-gold"></div>
                    </div>

                    <flux:accordion>

                        <flux:accordion.item heading="{{ __('Can someone contact me through my IRDI Member Profile?') }}">
                            <p class="text-sm leading-6 text-zinc-600 dark:text-zinc-400">
                                {{ __('If you choose to allow member messages, eligible signed-in IRDI members can use the Message Member option on your public profile to send you a message through IRDI.') }}
                            </p>
                        </flux:accordion.item>


                        <flux:accordion.item heading="{{ __('Do I have to allow people to contact me?') }}">
                            <p class="text-sm leading-6 text-zinc-600 dark:text-zinc-400">
                                {{ __('No. Allowing messages from other IRDI members is optional. If you turn this setting off, the Message Member option will not be displayed on your profile.') }}
                            </p>
                        </flux:accordion.item>


                        <flux:accordion.item heading="{{ __('Is my email address displayed publicly when someone contacts me?') }}">
                            <p class="text-sm leading-6 text-zinc-600 dark:text-zinc-400">
                                {{ __('The IRDI member messaging system allows eligible members to contact you without displaying your account email address publicly on your Member Profile.') }}
                            </p>
                        </flux:accordion.item>


                        <flux:accordion.item heading="{{ __('What information is visible on my public profile?') }}">
                            <p class="text-sm leading-6 text-zinc-600 dark:text-zinc-400">
                                {{ __('Your public Member Profile may display information such as your profile name, username, profile image, general location, biography, website, Member Since date, current membership standing, and available Property Owner Feedback. Confidential concerns, private moderation information, and your account email address are not displayed publicly.') }}
                            </p>
                        </flux:accordion.item>

                    </flux:accordion>

                </section>


                {{-- Account Management --}}
                <section class="mb-14">

                    <div class="mb-3 rounded-xl bg-irdi-green/10 px-5 py-4">
                        <flux:heading size="xl" level="2" class="text-irdi-green">
                            {{ __('Managing Your Account') }}
                        </flux:heading>

                        <div class="mt-2 h-1 w-12 rounded-full bg-irdi-gold"></div>
                    </div>

                    <flux:accordion>

                        <flux:accordion.item heading="{{ __('Can I change my email address?') }}">
                            <p class="text-sm leading-6 text-zinc-600 dark:text-zinc-400">
                                {{ __('Yes. You can change your account email address through your account settings. You may be required to verify the new email address before it becomes fully active on your account.') }}
                            </p>
                        </flux:accordion.item>


                        <flux:accordion.item heading="{{ __('Can I change my password?') }}">
                            <p class="text-sm leading-6 text-zinc-600 dark:text-zinc-400">
                                {{ __('Yes. You can change your password from your account settings while signed in.') }}
                            </p>
                        </flux:accordion.item>


                        <flux:accordion.item heading="{{ __('What if I forget my password?') }}">
                            <p class="text-sm leading-6 text-zinc-600 dark:text-zinc-400">
                                {{ __('Use the Forgot Password option on the sign-in page. IRDI will send password reset instructions to the email address associated with your account.') }}
                            </p>
                        </flux:accordion.item>


                        <flux:accordion.item heading="{{ __('Can I delete my IRDI account?') }}">
                            <p class="text-sm leading-6 text-zinc-600 dark:text-zinc-400">
                                {{ __('Yes. You can permanently delete your IRDI account through your account settings.') }}
                            </p>
                        </flux:accordion.item>


                        <flux:accordion.item heading="{{ __('What happens if I delete my account?') }}">
                            <p class="text-sm leading-6 text-zinc-600 dark:text-zinc-400">
                                {{ __('Deleting your account permanently removes your account and associated Member Profile. Because account deletion is permanent, make sure you want to remove your membership before completing the deletion process.') }}
                            </p>
                        </flux:accordion.item>

                    </flux:accordion>

                </section>


                {{-- Closing --}}
                <section class="rounded-xl border border-zinc-200 bg-zinc-50 p-6 dark:border-zinc-700 dark:bg-zinc-900">

                    <flux:heading size="lg" level="2">
                        {{ __('Still have a question?') }}
                    </flux:heading>

                    <flux:text class="mt-2">
                        {{ __('If you cannot find the answer you are looking for, contact IRDI and we will be happy to help.') }}
                    </flux:text>

                    <div class="mt-5">
                        <flux:button
                            :href="route('contact')"
                            variant="primary"
                            wire:navigate
                        >
                            {{ __('Contact IRDI') }}
                        </flux:button>
                    </div>

                </section>

            </div>

        </div>
    </section>

</x-layouts.public>
