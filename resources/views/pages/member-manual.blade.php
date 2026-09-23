<x-layouts.public>

    {{-- Hero --}}
    <section class="relative overflow-hidden bg-irdi-green">

        <div class="absolute inset-0 bg-irdi-green"></div>

        <div class="relative mx-auto max-w-7xl px-6 py-20 lg:px-8 lg:py-28">

            <div class="mx-auto max-w-3xl text-center">

                <p class="text-sm font-semibold uppercase tracking-widest text-irdi-gold-light">
                    International Responsible Detectorist Institute
                </p>

                <h1 class="mt-4 text-4xl font-bold tracking-tight text-white sm:text-5xl lg:text-6xl">
                    {{ __('IRDI Manual') }}
                </h1>

                <p class="mx-auto mt-6 max-w-2xl text-lg leading-8 text-zinc-200">
                    {{ __('Your guide to IRDI membership, your account, Member Profile, Member Directory, messaging, Property Owner Feedback, and member resources.') }}
                </p>

            </div>

        </div>

    </section>


    {{-- Manual --}}
    <section class="bg-white">

        <div class="mx-auto max-w-7xl px-6 py-12 lg:px-8 lg:py-16">

            <div class="grid gap-10 lg:grid-cols-[16rem_minmax(0,1fr)]">


                {{-- Table of Contents --}}
                <aside>

                    <div class="lg:sticky lg:top-8">

                        <flux:card class="p-5">

                            <h2 class="font-semibold text-irdi-green">
                                {{ __('In This Manual') }}
                            </h2>

                            <nav
                                class="mt-5 space-y-1 text-sm"
                                aria-label="{{ __('IRDI Manual') }}"
                            >

                                <a
                                    href="#getting-started"
                                    class="block rounded-lg px-3 py-2 text-zinc-700 hover:bg-zinc-100 hover:text-irdi-green"
                                >
                                    {{ __('1. Getting Started') }}
                                </a>

                                <a
                                    href="#membership"
                                    class="block rounded-lg px-3 py-2 text-zinc-700 hover:bg-zinc-100 hover:text-irdi-green"
                                >
                                    {{ __('2. Becoming a Member') }}
                                </a>

                                <a
                                    href="#good-standing"
                                    class="block rounded-lg px-3 py-2 text-zinc-700 hover:bg-zinc-100 hover:text-irdi-green"
                                >
                                    {{ __('3. Good Standing') }}
                                </a>

                                <a
                                    href="#account"
                                    class="block rounded-lg px-3 py-2 text-zinc-700 hover:bg-zinc-100 hover:text-irdi-green"
                                >
                                    {{ __('4. Your Account') }}
                                </a>

                                <a
                                    href="#profile"
                                    class="block rounded-lg px-3 py-2 text-zinc-700 hover:bg-zinc-100 hover:text-irdi-green"
                                >
                                    {{ __('5. Member Profile') }}
                                </a>

                                <a
                                    href="#directory"
                                    class="block rounded-lg px-3 py-2 text-zinc-700 hover:bg-zinc-100 hover:text-irdi-green"
                                >
                                    {{ __('6. Member Directory') }}
                                </a>

                                <a
                                    href="#member-card"
                                    class="block rounded-lg px-3 py-2 text-zinc-700 hover:bg-zinc-100 hover:text-irdi-green"
                                >
                                    {{ __('7. Member Card') }}
                                </a>

                                <a
                                    href="#messages"
                                    class="block rounded-lg px-3 py-2 text-zinc-700 hover:bg-zinc-100 hover:text-irdi-green"
                                >
                                    {{ __('8. Member Messages') }}
                                </a>

                                <a
                                    href="#reviews"
                                    class="block rounded-lg px-3 py-2 text-zinc-700 hover:bg-zinc-100 hover:text-irdi-green"
                                >
                                    {{ __('9. Property Owner Feedback') }}
                                </a>

                                <a
                                    href="#private-property-permission"
                                    class="block rounded-lg px-3 py-2 pl-6 text-zinc-600 hover:bg-zinc-100 hover:text-irdi-green"
                                >
                                    {{ __('Requesting Private Property Permission') }}
                                </a>

                                <a
                                    href="#resources"
                                    class="block rounded-lg px-3 py-2 text-zinc-700 hover:bg-zinc-100 hover:text-irdi-green"
                                >
                                    {{ __('10. Resources') }}
                                </a>

                                <a
                                    href="#accountability"
                                    class="block rounded-lg px-3 py-2 text-zinc-700 hover:bg-zinc-100 hover:text-irdi-green"
                                >
                                    {{ __('11. Membership Status') }}
                                </a>

                                <a
                                    href="#privacy"
                                    class="block rounded-lg px-3 py-2 text-zinc-700 hover:bg-zinc-100 hover:text-irdi-green"
                                >
                                    {{ __('privacy.manual_nav') }}
                                </a>

                                <a
                                    href="#help"
                                    class="block rounded-lg px-3 py-2 text-zinc-700 hover:bg-zinc-100 hover:text-irdi-green"
                                >
                                    {{ __('13. Getting Help') }}
                                </a>

                            </nav>

                        </flux:card>

                    </div>

                </aside>


                {{-- Manual Content --}}
                <main class="min-w-0">

                    <div class="mb-10">

                        <h2 class="text-3xl font-bold tracking-tight text-irdi-green">
                            {{ __('Using Your IRDI Membership') }}
                        </h2>

                        <div class="mt-3 h-1 w-16 bg-irdi-gold"></div>

                        <p class="mt-5 text-lg leading-8 text-zinc-600">
                            {{ __('This public manual explains IRDI membership and the member features available through an IRDI account. You can read it before creating an account and return to it whenever you need help with membership, your Member Profile, the Member Directory, messaging, Property Owner Feedback, or member resources.') }}
                        </p>

                    </div>


                    {{-- 1. Getting Started --}}
                    <section
                        id="getting-started"
                        class="scroll-mt-8 border-t border-zinc-200 py-10"
                    >

                        <p class="text-sm font-semibold uppercase tracking-wider text-irdi-gold">
                            {{ __('Section 1') }}
                        </p>

                        <h2 class="mt-2 text-2xl font-bold text-irdi-green">
                            {{ __('Getting Started') }}
                        </h2>

                        <p class="mt-4 leading-7 text-zinc-600">
                            {{ __('Your IRDI account is the starting point for membership. Your account stores your sign-in information, membership status, membership agreements, Member Profile, messages, and other information associated with your participation in IRDI.') }}
                        </p>

                        <div class="mt-6 grid gap-5 md:grid-cols-2">

                            <flux:card class="p-6">

                                <h3 class="font-semibold text-irdi-green">
                                    {{ __('Create Your Account') }}
                                </h3>

                                <p class="mt-3 text-sm leading-6 text-zinc-600">
                                    {{ __('Registration creates your IRDI account. Your account name cannot be changed after registration, so enter it carefully.') }}
                                </p>

                            </flux:card>

                            <flux:card class="p-6">

                                <h3 class="font-semibold text-irdi-green">
                                    {{ __('Verify Your Email') }}
                                </h3>

                                <p class="mt-3 text-sm leading-6 text-zinc-600">
                                    {{ __('After registration, verify your email address using the verification message sent by IRDI. Email verification is required before accessing authenticated member features.') }}
                                </p>

                            </flux:card>

                        </div>

                        <div class="mt-6 rounded-xl border border-amber-200 bg-amber-50 p-5">

                            <div class="flex gap-3">

                                <flux:icon.information-circle class="mt-0.5 size-5 shrink-0 text-amber-700" />

                                <p class="text-sm leading-6 text-amber-900">
                                    {{ __('Creating an IRDI account does not by itself activate your IRDI membership. Membership is activated separately after completing the membership requirements described below.') }}
                                </p>

                            </div>

                        </div>

                    </section>


                    {{-- 2. Membership --}}
                    <section
                        id="membership"
                        class="scroll-mt-8 border-t border-zinc-200 py-10"
                    >

                        <p class="text-sm font-semibold uppercase tracking-wider text-irdi-gold">
                            {{ __('Section 2') }}
                        </p>

                        <h2 class="mt-2 text-2xl font-bold text-irdi-green">
                            {{ __('Becoming an IRDI Member') }}
                        </h2>

                        <p class="mt-4 leading-7 text-zinc-600">
                            {{ __('After creating and verifying your account, you can complete the IRDI membership process. Membership requires you to review and agree to both the IRDI Code of Ethics and the Best Practices of Responsible Detecting.') }}
                        </p>

                        <ol class="mt-6 space-y-4">

                            <li class="flex gap-4">
                                <div class="flex size-8 shrink-0 items-center justify-center rounded-full bg-irdi-green font-semibold text-white">
                                    1
                                </div>

                                <div>
                                    <h3 class="font-semibold text-zinc-900">
                                        {{ __('Read the Code of Ethics') }}
                                    </h3>

                                    <p class="mt-1 text-sm leading-6 text-zinc-600">
                                        {{ __('Review the ethical principles and responsibilities expected of IRDI members.') }}
                                    </p>
                                </div>
                            </li>

                            <li class="flex gap-4">
                                <div class="flex size-8 shrink-0 items-center justify-center rounded-full bg-irdi-green font-semibold text-white">
                                    2
                                </div>

                                <div>
                                    <h3 class="font-semibold text-zinc-900">
                                        {{ __('Read the Best Practices') }}
                                    </h3>

                                    <p class="mt-1 text-sm leading-6 text-zinc-600">
                                        {{ __('Review IRDI guidance for responsible, respectful, and lawful metal detecting.') }}
                                    </p>
                                </div>
                            </li>

                            <li class="flex gap-4">
                                <div class="flex size-8 shrink-0 items-center justify-center rounded-full bg-irdi-green font-semibold text-white">
                                    3
                                </div>

                                <div>
                                    <h3 class="font-semibold text-zinc-900">
                                        {{ __('Accept Both Standards') }}
                                    </h3>

                                    <p class="mt-1 text-sm leading-6 text-zinc-600">
                                        {{ __('Confirm your agreement to both standards to activate your membership.') }}
                                    </p>
                                </div>
                            </li>

                        </ol>

                        <div class="mt-6 flex flex-wrap gap-3">

                            @auth
                                <flux:button
                                    href="{{ route('membership.join') }}"
                                    variant="primary"
                                    icon="shield-check"
                                >
                                    {{ __('Membership') }}
                                </flux:button>

                                <flux:button
                                    href="{{ route('code-of-ethics') }}"
                                    variant="outline"
                                >
                                    {{ __('Code of Ethics') }}
                                </flux:button>

                                <flux:button
                                    href="{{ route('best-practices') }}"
                                    variant="outline"
                                >
                                    {{ __('Best Practices') }}
                                </flux:button>
                            @else
                                <flux:button
                                    href="{{ route('register') }}"
                                    variant="primary"
                                    icon="user-plus"
                                >
                                    {{ __('Create an Account') }}
                                </flux:button>

                                <flux:button
                                    href="{{ route('login') }}"
                                    variant="outline"
                                >
                                    {{ __('Log In') }}
                                </flux:button>
                            @endauth

                        </div>

                    </section>


                    {{-- 3. Good Standing --}}
                    <section
                        id="good-standing"
                        class="scroll-mt-8 border-t border-zinc-200 py-10"
                    >

                        <p class="text-sm font-semibold uppercase tracking-wider text-irdi-gold">
                            {{ __('Section 3') }}
                        </p>

                        <h2 class="mt-2 text-2xl font-bold text-irdi-green">
                            {{ __('Understanding Good Standing') }}
                        </h2>

                        <p class="mt-4 leading-7 text-zinc-600">
                            {{ __('An active member who has accepted the Code of Ethics and Best Practices and does not have an active accountability condition that removes Good Standing is identified by IRDI as a Member in Good Standing.') }}
                        </p>

                        <div class="mt-6 rounded-xl border border-green-200 bg-green-50 p-5">

                            <div class="flex gap-3">

                                <flux:icon.shield-check class="mt-0.5 size-5 shrink-0 text-green-700" />

                                <div>

                                    <h3 class="font-semibold text-green-900">
                                        {{ __('Member in Good Standing') }}
                                    </h3>

                                    <p class="mt-2 text-sm leading-6 text-green-800">
                                        {{ __('Good Standing reflects your current status within IRDI’s membership and accountability system. It is not a criminal background check, personal endorsement, or guarantee of trustworthiness or future behavior.') }}
                                    </p>

                                </div>

                            </div>

                        </div>

                        <p class="mt-5 text-sm leading-6 text-zinc-600">
                            {{ __('Your current membership status is displayed on your Account page. If Good Standing is not current, your account may also display information explaining the membership condition that currently applies to you.') }}
                        </p>

                    </section>


                    {{-- 4. Account --}}
                    <section
                        id="account"
                        class="scroll-mt-8 border-t border-zinc-200 py-10"
                    >

                        <p class="text-sm font-semibold uppercase tracking-wider text-irdi-gold">
                            {{ __('Section 4') }}
                        </p>

                        <h2 class="mt-2 text-2xl font-bold text-irdi-green">
                            {{ __('Your IRDI Account') }}
                        </h2>

                        <p class="mt-4 leading-7 text-zinc-600">
                            {{ __('Your Account page is the central location for managing your IRDI membership and member features.') }}
                        </p>

                        <div class="mt-6 grid gap-5 md:grid-cols-2">

                            <flux:card class="p-6">
                                <h3 class="font-semibold text-irdi-green">
                                    {{ __('Account Information') }}
                                </h3>

                                <p class="mt-3 text-sm leading-6 text-zinc-600">
                                    {{ __('Review your account name, email address, email verification status, membership status, and Member Since date.') }}
                                </p>
                            </flux:card>

                            <flux:card class="p-6">
                                <h3 class="font-semibold text-irdi-green">
                                    {{ __('Account Settings') }}
                                </h3>

                                <p class="mt-3 text-sm leading-6 text-zinc-600">
                                    {{ __('Change the email address or password associated with your account. Changing your email address may require verification of the new address.') }}
                                </p>
                            </flux:card>

                        </div>

                        <div class="mt-6 flex flex-wrap gap-3">
                            @auth
                                <flux:button
                                    href="{{ route('account') }}"
                                    variant="primary"
                                    icon="user"
                                >
                                    {{ __('Go to My Account') }}
                                </flux:button>
                            @else
                                <flux:button
                                    href="{{ route('register') }}"
                                    variant="primary"
                                    icon="user-plus"
                                >
                                    {{ __('Create an Account') }}
                                </flux:button>

                                <flux:button
                                    href="{{ route('login') }}"
                                    variant="outline"
                                >
                                    {{ __('Log In') }}
                                </flux:button>
                            @endauth
                        </div>

                    </section>


                    {{-- 5. Profile --}}
                    <section
                        id="profile"
                        class="scroll-mt-8 border-t border-zinc-200 py-10"
                    >

                        <p class="text-sm font-semibold uppercase tracking-wider text-irdi-gold">
                            {{ __('Section 5') }}
                        </p>

                        <h2 class="mt-2 text-2xl font-bold text-irdi-green">
                            {{ __('Your Member Profile') }}
                        </h2>

                        <p class="mt-4 leading-7 text-zinc-600">
                            {{ __('privacy.one_profile') }}
                        </p>

                        <h3 class="mt-7 text-lg font-semibold text-zinc-900">
                            {{ __('Creating Your Profile') }}
                        </h3>

                        <p class="mt-3 text-sm leading-6 text-zinc-600">
                            {{ __('When you first create your profile, you will choose a profile name and a unique username. Your username must be between 5 and 50 characters and may contain letters, numbers, dashes, and underscores.') }}
                        </p>

                        <div class="mt-5 rounded-xl border border-amber-200 bg-amber-50 p-5">

                            <p class="text-sm font-semibold text-amber-900">
                                {{ __('Choose your username carefully.') }}
                            </p>

                            <p class="mt-2 text-sm leading-6 text-amber-800">
                                {{ __('Your username becomes your unique public @username and is used in your IRDI profile address. After your Member Profile has been created, the username cannot be changed through the profile editor.') }}
                            </p>

                        </div>

                        <h3 class="mt-7 text-lg font-semibold text-zinc-900">
                            {{ __('Completing Your Profile') }}
                        </h3>

                        <p class="mt-3 text-sm leading-6 text-zinc-600">
                            {{ __('After creating your profile, you can add and manage your profile name, profile image, city, state or province, country, biography, website, directory visibility, and member messaging preferences.') }}
                        </p>

                        <p class="mt-4 text-sm leading-6 text-zinc-600">
                            {{ __('IRDI considers the profile complete when a profile image, city, state or province, country, and biography have been provided.') }}
                        </p>

                        @auth
                            @if (! auth()->user()->memberProfile && auth()->user()->membership_status === 'active')
                                <div class="mt-6">
                                    <flux:button
                                        href="{{ route('member-profiles.create') }}"
                                        variant="primary"
                                        icon="user-plus"
                                    >
                                        {{ __('Create My Member Profile') }}
                                    </flux:button>
                                </div>
                            @elseif (auth()->user()->memberProfile)
                                <div class="mt-6">
                                    <flux:button
                                        href="{{ route('account.profiles.edit', auth()->user()->memberProfile) }}"
                                        variant="primary"
                                        icon="pencil-square"
                                    >
                                        {{ __('Edit My Profile') }}
                                    </flux:button>
                                </div>
                            @endif
                        @endauth

                    </section>


                    {{-- 6. Directory --}}
                    <section
                        id="directory"
                        class="scroll-mt-8 border-t border-zinc-200 py-10"
                    >

                        <p class="text-sm font-semibold uppercase tracking-wider text-irdi-gold">
                            {{ __('Section 6') }}
                        </p>

                        <h2 class="mt-2 text-2xl font-bold text-irdi-green">
                            {{ __('Member Directory & Privacy') }}
                        </h2>

                        <p class="mt-4 leading-7 text-zinc-600">
                            {{ __('privacy.directory_rules') }}
                        </p>

                        <p class="mt-4 text-sm leading-6 text-zinc-600">
                            {{ __('privacy.public_fields') }}
                        </p>

                        <div class="mt-6">
                            <flux:button
                                href="{{ route('directory') }}"
                                variant="outline"
                                icon="magnifying-glass"
                            >
                                {{ __('View Member Directory') }}
                            </flux:button>
                        </div>

                    </section>


                    {{-- 7. Member Card --}}
                    <section
                        id="member-card"
                        class="scroll-mt-8 border-t border-zinc-200 py-10"
                    >

                        <p class="text-sm font-semibold uppercase tracking-wider text-irdi-gold">
                            {{ __('Section 7') }}
                        </p>

                        <h2 class="mt-2 text-2xl font-bold text-irdi-green">
                            {{ __('Your IRDI Member Card') }}
                        </h2>

                        <p class="mt-4 leading-7 text-zinc-600">
                            {{ __('Your digital IRDI Member Card is connected to your Member Profile. It provides identifying membership information and a convenient way to direct someone to your live IRDI profile.') }}
                        </p>

                        <div class="mt-5 rounded-xl border border-zinc-200 bg-zinc-50 p-5">
                            <p class="text-sm leading-6 text-zinc-700">
                                {{ __('Your Member Card should be understood together with your live IRDI profile. The card itself is not permanent proof of current membership or standing because membership status can change.') }}
                            </p>
                        </div>

                        @auth
                            @if (auth()->user()->memberProfile && auth()->user()->isInGoodStanding())
                                <div class="mt-6">
                                    <flux:button
                                        href="{{ route('member-profiles.card', auth()->user()->memberProfile) }}"
                                        variant="outline"
                                        icon="identification"
                                    >
                                        {{ __('View My Member Card') }}
                                    </flux:button>
                                </div>
                            @endif
                        @endauth

                    </section>


                    {{-- 8. Messages --}}
                    <section
                        id="messages"
                        class="scroll-mt-8 border-t border-zinc-200 py-10"
                    >

                        <p class="text-sm font-semibold uppercase tracking-wider text-irdi-gold">
                            {{ __('Section 8') }}
                        </p>

                        <h2 class="mt-2 text-2xl font-bold text-irdi-green">
                            {{ __('Member-to-Member Messaging') }}
                        </h2>

                        <p class="mt-4 leading-7 text-zinc-600">
                            {{ __('IRDI includes a private member messaging system that allows eligible signed-in members to communicate without publishing their account email addresses on their public Member Profiles.') }}
                        </p>

                        <h3 class="mt-7 text-lg font-semibold text-zinc-900">
                            {{ __('Receiving Messages') }}
                        </h3>

                        <p class="mt-3 text-sm leading-6 text-zinc-600">
                            {{ __('Receiving member messages is optional. You can enable or disable the member messaging option from your Member Profile settings. When disabled, the Message Member option is not displayed for your profile.') }}
                        </p>

                        <h3 class="mt-7 text-lg font-semibold text-zinc-900">
                            {{ __('Email Notifications') }}
                        </h3>

                        <p class="mt-3 text-sm leading-6 text-zinc-600">
                            {{ __('Your profile settings also allow you to control whether IRDI sends email notifications about member messages.') }}
                        </p>

                        <h3 class="mt-7 text-lg font-semibold text-zinc-900">
                            {{ __('Inbox and Sent Messages') }}
                        </h3>

                        <p class="mt-3 text-sm leading-6 text-zinc-600">
                            {{ __('The Messages area provides separate Inbox and Sent views for managing your IRDI member conversations.') }}
                        </p>

                        @auth
                            <div class="mt-6">
                                <flux:button
                                    href="{{ route('messages.index') }}"
                                    variant="primary"
                                    icon="envelope"
                                >
                                    {{ __('Open Messages') }}
                                </flux:button>
                            </div>
                        @endauth

                    </section>


                    {{-- 9. Reviews --}}
                    <section
                        id="reviews"
                        class="scroll-mt-8 border-t border-zinc-200 py-10"
                    >

                        <p class="text-sm font-semibold uppercase tracking-wider text-irdi-gold">
                            {{ __('Section 9') }}
                        </p>

                        <h2 class="mt-2 text-2xl font-bold text-irdi-green">
                            {{ __('Property Owner Feedback') }}
                        </h2>

                        <div id="private-property-permission" class="scroll-mt-8">
                            <h3 class="mt-7 text-xl font-semibold text-irdi-green">
                                {{ __('Requesting Private Property Permission') }}
                            </h3>

                            <p class="mt-3 text-sm leading-6 text-zinc-600">
                                {{ __('A good permission request is respectful, brief, and clear. The goal is not to pressure a property owner into saying yes, but to give them enough information to make an informed decision about access to their property.') }}
                            </p>

                            <h4 class="mt-6 font-semibold text-zinc-900">{{ __('Before You Approach') }}</h4>
                            <ul class="mt-3 list-disc space-y-2 pl-6 text-sm leading-6 text-zinc-600">
                                <li>{{ __('Research the property and confirm that you are approaching the person authorized to grant permission whenever reasonably possible.') }}</li>
                                <li>{{ __('Have a genuine and specific reason for your interest in detecting the property or learning about its history.') }}</li>
                                <li>{{ __('Choose a reasonable time to approach and avoid interrupting obvious meals, family activities, work, or other inconvenient circumstances.') }}</li>
                                <li>{{ __('Present yourself neatly and respectfully. When practical, leave your detector and recovery tools in your vehicle during the initial conversation.') }}</li>
                            </ul>

                            <h4 class="mt-6 font-semibold text-zinc-900">{{ __('Choosing How to Approach') }}</h4>
                            <p class="mt-3 text-sm leading-6 text-zinc-600">
                                {{ __('A legitimate personal introduction or referral can make an initial conversation easier. When no introduction is available, an appropriate face-to-face conversation may work well. If the property owner is already outside and circumstances are appropriate, a polite approach may feel less intrusive than an unexpected knock at the door. Door knocking remains an option when done respectfully and at a reasonable time.') }}
                            </p>

                            <h4 class="mt-6 font-semibold text-zinc-900">{{ __('A Simple Permission Framework') }}</h4>
                            <div class="mt-3 rounded-xl border border-zinc-200 bg-zinc-50 p-5">
                                <p class="font-semibold text-zinc-900">{{ __('Introduction → Interest → Request → Property Care → Owner Control') }}</p>
                                <p class="mt-3 text-sm leading-6 text-zinc-600">
                                    {{ __('Introduce yourself, explain briefly why the property interests you, clearly ask for permission to metal detect, explain that you will recover targets carefully and restore the ground, and make it clear that the property owner controls whether permission is granted and under what conditions.') }}
                                </p>
                            </div>

                            <h4 class="mt-6 font-semibold text-zinc-900">{{ __('Example Permission Request') }}</h4>
                            <div class="mt-3 rounded-xl border border-irdi-green/20 bg-irdi-green/5 p-5">
                                <p class="text-sm leading-6 text-zinc-700">
                                    {{ __('Hello, my name is [name]. I am a local metal detectorist interested in the history of this area, and your property caught my attention because [brief genuine reason]. I wanted to ask whether you would consider allowing me to metal detect part of the property. I recover targets carefully, restore the ground, and remove the trash I find. I am also happy to show you what I recover. If you would rather not, I completely understand.') }}
                                </p>
                            </div>
                            <p class="mt-3 text-sm leading-6 text-zinc-600">
                                {{ __('Use the example as a framework rather than a script. A natural conversation that accurately reflects who you are and why you are interested in the property is preferable to a memorized sales pitch.') }}
                            </p>

                            <h4 class="mt-6 font-semibold text-zinc-900">{{ __('If Permission Is Granted') }}</h4>
                            <ul class="mt-3 list-disc space-y-2 pl-6 text-sm leading-6 text-zinc-600">
                                <li>{{ __('Confirm the areas where you may detect and any areas that are off limits.') }}</li>
                                <li>{{ __('Discuss expectations about recovered items, especially potentially valuable, historic, sentimental, or personally identifiable items.') }}</li>
                                <li>{{ __('Confirm parking, gates, livestock, landscaping, time limits, access routes, and other property-specific instructions.') }}</li>
                                <li>{{ __('Ask before bringing another detectorist or other person onto the property.') }}</li>
                                <li>{{ __('Remember that the property owner may change or withdraw permission at any time.') }}</li>
                            </ul>

                            <h4 class="mt-6 font-semibold text-zinc-900">{{ __('If Permission Is Declined') }}</h4>
                            <div class="mt-3 rounded-xl border border-amber-200 bg-amber-50 p-5">
                                <p class="text-sm leading-6 text-amber-900">
                                    {{ __('Thank the property owner for their time and leave respectfully. Do not argue, pressure, repeatedly ask, or attempt to negotiate after permission has been declined.') }}
                                </p>
                            </div>

                            <h4 class="mt-6 font-semibold text-zinc-900">{{ __('After Detecting') }}</h4>
                            <p class="mt-3 text-sm leading-6 text-zinc-600">
                                {{ __('Leave the property clean and restored, follow the agreement concerning recovered items, thank the property owner, and share any information you agreed to provide. When appropriate, an eligible IRDI member may later invite the property owner to provide Property Owner Feedback about the experience.') }}
                            </p>
                        </div>

                        <div class="mt-10 border-t border-zinc-200 pt-8">
                            <h3 class="text-xl font-semibold text-irdi-green">
                                {{ __('Property Owner Feedback') }}
                            </h3>

                            <p class="mt-4 leading-7 text-zinc-600">
                                {{ __('Property Owner Feedback allows a property owner to provide feedback about their experience after allowing you to detect on their property. Feedback can add first-hand experience to your public IRDI record.') }}
                            </p>
                        </div>

                        <h3 class="mt-7 text-lg font-semibold text-zinc-900">
                            {{ __('Requesting Feedback') }}
                        </h3>

                        <p class="mt-3 text-sm leading-6 text-zinc-600">
                            {{ __('When your membership is in Good Standing and you have a Member Profile, your Account page provides a Request Property Owner Review option. Enter the property owner’s email address and IRDI will send a private invitation.') }}
                        </p>

                        <h3 class="mt-7 text-lg font-semibold text-zinc-900">
                            {{ __('Invitation Rules') }}
                        </h3>

                        <ul class="mt-3 list-disc space-y-2 pl-6 text-sm leading-6 text-zinc-600">
                            <li>
                                {{ __('The invitation is associated with your Member Profile.') }}
                            </li>
                            <li>
                                {{ __('The property owner does not need an IRDI account to respond.') }}
                            </li>
                            <li>
                                {{ __('The reviewer must verify access to the email address used for the review.') }}
                            </li>
                            <li>
                                {{ __('Invitations currently expire 30 days after creation.') }}
                            </li>
                            <li>
                                {{ __('An invitation is intended for a single review and cannot be reused after a review is submitted.') }}
                            </li>
                        </ul>

                        <h3 class="mt-7 text-lg font-semibold text-zinc-900">
                            {{ __('Public Rating') }}
                        </h3>

                        <p class="mt-3 text-sm leading-6 text-zinc-600">
                            {{ __('A public Property Owner Rating is displayed after you have at least three visible Property Owner Reviews. Before that threshold, your public profile may show the number of visible reviews without displaying an average rating.') }}
                        </p>

                        <p class="mt-4 text-sm leading-6 text-zinc-600">
                            {{ __('IRDI verifies the reviewer’s access to the submitted email address, but does not independently verify property ownership or guarantee the accuracy of a review.') }}
                        </p>

                        @auth
                            <div class="mt-6">
                                <flux:button
                                    href="{{ route('account.reviews') }}"
                                    variant="primary"
                                    icon="star"
                                >
                                    {{ __('My Property Owner Feedback') }}
                                </flux:button>
                            </div>
                        @endauth

                    </section>


                    {{-- 10. Resources --}}
                    <section
                        id="resources"
                        class="scroll-mt-8 border-t border-zinc-200 py-10"
                    >

                        <p class="text-sm font-semibold uppercase tracking-wider text-irdi-gold">
                            {{ __('Section 10') }}
                        </p>

                        <h2 class="mt-2 text-2xl font-bold text-irdi-green">
                            {{ __('Resources') }}
                        </h2>

                        <p class="mt-4 leading-7 text-zinc-600">
                            {{ __('IRDI Resources provides public access to the Code of Ethics, Best Practices, frequently asked questions, the IRDI Manual, membership guidance, and member verification information.') }}
                        </p>

                        <div class="mt-6">
                            <flux:button
                                href="{{ route('resources') }}"
                                variant="primary"
                                icon="book-open"
                            >
                                {{ __('Open Resources') }}
                            </flux:button>
                        </div>

                    </section>


                    {{-- 11. Membership Status --}}
                    <section
                        id="accountability"
                        class="scroll-mt-8 border-t border-zinc-200 py-10"
                    >

                        <p class="text-sm font-semibold uppercase tracking-wider text-irdi-gold">
                            {{ __('Section 11') }}
                        </p>

                        <h2 class="mt-2 text-2xl font-bold text-irdi-green">
                            {{ __('Changes to Your Membership Status') }}
                        </h2>

                        <p class="mt-4 leading-7 text-zinc-600">
                            {{ __('Your Account page is the primary place to review your current IRDI membership status. If an accountability condition applies to your membership, the Account page provides the member-facing information associated with that condition.') }}
                        </p>

                        <div class="mt-6 space-y-4">

                            <flux:card class="p-6">
                                <h3 class="font-semibold text-irdi-green">
                                    {{ __('Formal Warning') }}
                                </h3>

                                <p class="mt-3 text-sm leading-6 text-zinc-600">
                                    {{ __('A formal warning does not by itself suspend your membership or remove your Member in Good Standing status. Review any notice shown on your Account page for the information that applies to the warning.') }}
                                </p>
                            </flux:card>

                            <flux:card class="p-6">
                                <h3 class="font-semibold text-irdi-green">
                                    {{ __('Probation or Administrative Review') }}
                                </h3>

                                <p class="mt-3 text-sm leading-6 text-zinc-600">
                                    {{ __('Your membership may remain active while Good Standing is not current. Your Account page will display the applicable member notice and available information about your status.') }}
                                </p>
                            </flux:card>

                            <flux:card class="p-6">
                                <h3 class="font-semibold text-irdi-green">
                                    {{ __('Suspended Membership') }}
                                </h3>

                                <p class="mt-3 text-sm leading-6 text-zinc-600">
                                    {{ __('While suspended, your account and historical information are preserved, but active-member privileges are unavailable. Your public profile and Member Directory listing are unavailable, you cannot use your Member Card, request new Property Owner Feedback, access active-member resources, or send new member-to-member messages. You can still access your account settings and existing account information.') }}
                                </p>
                            </flux:card>

                        </div>

                        <p class="mt-6 text-sm leading-6 text-zinc-600">
                            {{ __('If your membership status changes, follow the information displayed directly in your IRDI account and any membership notice sent to your registered email address.') }}
                        </p>

                    </section>


                    {{-- 12. Personal information privacy --}}
                    <section
                        id="privacy"
                        class="scroll-mt-8 border-t border-zinc-200 py-10"
                    >

                        <p class="text-sm font-semibold uppercase tracking-wider text-irdi-gold">
                            {{ __('Section 12') }}
                        </p>

                        <h2 class="mt-2 text-2xl font-bold text-irdi-green">
                            {{ __('privacy.title') }}
                        </h2>

                        <p class="mt-4 leading-7 text-zinc-600">
                            {{ __('privacy.how_to') }}
                        </p>

                        <div class="mt-6 rounded-xl border border-zinc-200 bg-zinc-50 p-5">

                            <div class="flex gap-3">

                                <flux:icon.shield-check class="mt-0.5 size-5 shrink-0 text-irdi-green" />

                                <div>

                                    <h3 class="font-semibold text-irdi-green">
                                        {{ __('privacy.title') }}
                                    </h3>

                                    <p class="mt-2 text-sm leading-6 text-zinc-700">
                                        {{ __('privacy.no_deletion') }}
                                    </p>

                                </div>

                            </div>

                        </div>

                        <p class="mt-4 text-sm leading-6 text-zinc-600">{{ __('privacy.summary') }}</p>
                        <p class="mt-4 text-sm leading-6 text-zinc-600">{{ __('privacy.contact_controls') }}</p>
                        <p class="mt-4 text-sm leading-6 text-zinc-600">{{ __('privacy.verification') }}</p>
                        <p class="mt-4 text-sm leading-6 text-zinc-600">{{ __('privacy.restore') }}</p>
                        <p class="mt-4 text-sm leading-6 text-zinc-600">{{ __('privacy.limits') }}</p>
                        <p class="mt-4 text-sm leading-6 text-zinc-600">{{ __('privacy.requests') }}</p>

                        @auth
                            <div class="mt-6">
                                <flux:button
                                    href="{{ route('account') }}"
                                    variant="primary"
                                    icon="shield-check"
                                >
                                    {{ __('privacy.manage') }}
                                </flux:button>
                            </div>
                        @endauth

                    </section>


                    {{-- 13. Help --}}
                    <section
                        id="help"
                        class="scroll-mt-8 border-y border-zinc-200 py-10"
                    >

                        <p class="text-sm font-semibold uppercase tracking-wider text-irdi-gold">
                            {{ __('Section 13') }}
                        </p>

                        <h2 class="mt-2 text-2xl font-bold text-irdi-green">
                            {{ __('Getting Help') }}
                        </h2>

                        <p class="mt-4 leading-7 text-zinc-600">
                            {{ __('If you have a question about your IRDI account, membership, Member Profile, messages, Property Owner Feedback, or another member feature, review the IRDI FAQ or contact IRDI for assistance.') }}
                        </p>

                        <div class="mt-6 flex flex-wrap gap-3">

                            <flux:button
                                href="{{ route('faq') }}"
                                variant="outline"
                                icon="question-mark-circle"
                            >
                                {{ __('View FAQ') }}
                            </flux:button>

                            <flux:button
                                href="{{ route('contact') }}"
                                variant="primary"
                                icon="envelope"
                            >
                                {{ __('Contact IRDI') }}
                            </flux:button>

                        </div>

                    </section>


                    {{-- Closing --}}
                    <section class="pt-10">

                        <div class="rounded-2xl bg-irdi-green p-8 text-center">

                            <flux:icon.shield-check class="mx-auto size-10 text-irdi-gold-light" />

                            <h2 class="mt-4 text-2xl font-bold text-white">
                                {{ __('Responsible Membership Starts With You') }}
                            </h2>

                            <p class="mx-auto mt-4 max-w-2xl leading-7 text-zinc-200">
                                {{ __('IRDI membership represents an ongoing commitment to ethical detecting, responsible stewardship, respect for landowners, and protecting our shared history.') }}
                            </p>

                            <div class="mt-6 flex flex-wrap justify-center gap-3">
                                @auth
                                    <flux:button
                                        href="{{ route('account') }}"
                                        variant="primary"
                                        icon="user"
                                    >
                                        {{ __('Return to My Account') }}
                                    </flux:button>
                                @else
                                    <flux:button
                                        href="{{ route('register') }}"
                                        variant="primary"
                                        icon="user-plus"
                                    >
                                        {{ __('Create an IRDI Account') }}
                                    </flux:button>

                                    <flux:button
                                        href="{{ route('login') }}"
                                        variant="outline"
                                    >
                                        {{ __('Log In') }}
                                    </flux:button>
                                @endauth
                            </div>

                        </div>

                    </section>

                </main>

            </div>

        </div>

    </section>

</x-layouts.public>
