<x-layouts.public>

    <section class="bg-zinc-50">
        <div class="mx-auto max-w-7xl px-6 py-16 lg:px-8 lg:py-20">

            <div class="mx-auto max-w-md">

                <div class="text-center">
                    <h1 class="text-3xl font-bold tracking-tight text-irdi-green sm:text-4xl">
                        Create Your IRDI Account
                    </h1>

                    <div class="mx-auto mt-4 h-1 w-20 bg-irdi-gold"></div>

                    <p class="mt-6 text-zinc-600">
                        Create an account to begin your IRDI membership journey.
                    </p>
                </div>

                <flux:card class="mt-10 p-8">

                    <form method="POST" action="{{ route('register') }}">
                        @csrf

                        <div class="space-y-6">

                            <flux:input
                                name="name"
                                label="Name"
                                type="text"
                                value="{{ old('name') }}"
                                required
                                autofocus
                                autocomplete="name"
                            />

                            <flux:input
                                name="email"
                                label="Email Address"
                                type="email"
                                value="{{ old('email') }}"
                                required
                                autocomplete="email"
                            />

                            <flux:input
                                name="password"
                                label="Password"
                                type="password"
                                required
                                autocomplete="new-password"
                                viewable
                            />

                            <flux:input
                                name="password_confirmation"
                                label="Confirm Password"
                                type="password"
                                required
                                autocomplete="new-password"
                                viewable
                            />

                            <flux:button
                                type="submit"
                                variant="primary"
                                class="w-full"
                            >
                                Create Account
                            </flux:button>

                        </div>
                    </form>

                    <div class="mt-6 text-center text-sm text-zinc-600">
                        Already have an account?

                        <a
                            href="{{ route('login') }}"
                            class="font-medium text-irdi-green hover:underline"
                        >
                            Log in
                        </a>
                    </div>

                </flux:card>

            </div>

        </div>
    </section>

</x-layouts.public>
