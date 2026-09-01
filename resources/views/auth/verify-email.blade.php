<x-layouts.public :noindex="true">
    <section class="bg-zinc-50">
        <div class="mx-auto max-w-7xl px-6 py-16 lg:px-8 lg:py-20">

            <div class="mx-auto max-w-md">

                <div class="text-center">
                    <h1 class="text-3xl font-bold tracking-tight text-irdi-green sm:text-4xl">
                        Verify Your Email
                    </h1>

                    <div class="mx-auto mt-4 h-1 w-20 bg-irdi-gold"></div>

                    <p class="mt-6 text-zinc-600">
                        We sent a verification link to
                        <span class="font-medium text-zinc-900">
        {{ auth()->user()->email }}
    </span>.
                        Please click that link before continuing.
                    </p>
                </div>

                <flux:card class="mt-10 p-8">

                    @if (session('status') === 'verification-link-sent')
                        <div class="mb-6 rounded-lg bg-green-50 p-4 text-sm text-green-800">
                            A new verification link has been sent to your email address.
                        </div>
                    @endif

                    <form method="POST" action="{{ route('verification.send') }}">
                        @csrf

                        <flux:button
                            type="submit"
                            variant="primary"
                            class="w-full"
                        >
                            Resend Verification Email
                        </flux:button>
                    </form>

                    <form
                        method="POST"
                        action="{{ route('logout') }}"
                        class="mt-4"
                    >
                        @csrf

                        <flux:button
                            type="submit"
                            variant="ghost"
                            class="w-full"
                        >
                            Log Out
                        </flux:button>
                    </form>

                </flux:card>

            </div>

        </div>
    </section>

</x-layouts.public>
