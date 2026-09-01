<x-layouts.public :noindex="true">

    <section class="bg-zinc-50">
        <div class="mx-auto max-w-7xl px-6 py-16 lg:px-8 lg:py-20">

            <div class="mx-auto max-w-md">

                <div class="text-center">
                    <h1 class="text-3xl font-bold tracking-tight text-irdi-green sm:text-4xl">
                        Reset Your Password
                    </h1>

                    <div class="mx-auto mt-4 h-1 w-20 bg-irdi-gold"></div>

                    <p class="mt-6 text-zinc-600">
                        Enter a new password for your IRDI account.
                    </p>
                </div>

                <flux:card class="mt-10 p-8">

                    <form method="POST" action="{{ route('password.update') }}">
                        @csrf

                        <input
                            type="hidden"
                            name="token"
                            value="{{ $request->route('token') }}"
                        >

                        <div class="space-y-6">

                            <flux:input
                                name="email"
                                label="Email Address"
                                type="email"
                                value="{{ old('email', $request->email) }}"
                                required
                                autocomplete="email"
                            />

                            <flux:input
                                name="password"
                                label="New Password"
                                type="password"
                                required
                                autocomplete="new-password"
                                viewable
                            />

                            <flux:input
                                name="password_confirmation"
                                label="Confirm New Password"
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
                                Reset Password
                            </flux:button>

                        </div>
                    </form>

                </flux:card>

            </div>

        </div>
    </section>

</x-layouts.public>
