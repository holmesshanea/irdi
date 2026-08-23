<x-layouts.public>

<section class="bg-zinc-50">
        <div class="mx-auto max-w-7xl px-6 py-16 lg:px-8 lg:py-20">

            <div class="mx-auto max-w-md">

                <div class="text-center">
                    <h1 class="text-3xl font-bold tracking-tight text-irdi-green sm:text-4xl">
Log In to IRDI
</h1>

                    <div class="mx-auto mt-4 h-1 w-20 bg-irdi-gold"></div>

                    <p class="mt-6 text-zinc-600">
Access your IRDI account and continue your membership journey.
                    </p>
                </div>

                <flux:card class="mt-10 p-8">
                    @if (session('status'))
                        <div class="mb-6 rounded-lg bg-green-50 p-4 text-sm text-green-800">
                            {{ session('status') }}
                        </div>
                    @endif
                    <form method="POST" action="{{ route('login') }}">
@csrf

<div class="space-y-6">

    <flux:input
        name="email"
        label="Email Address"
        type="email"
        value="{{ old('email') }}"
        required
        autofocus
        autocomplete="email"
    />

    <flux:input
        name="password"
        label="Password"
        type="password"
        required
        autocomplete="current-password"
        viewable
    />

    <div class="flex items-center justify-between">

        <flux:checkbox
            name="remember"
            label="Remember me"
        />

        <a
            href="{{ route('password.request') }}"
            class="text-sm font-medium text-irdi-green hover:underline"
        >
            Forgot password?
        </a>

    </div>

    <flux:button
        type="submit"
        variant="primary"
        class="w-full"
    >
        Log In
    </flux:button>

</div>
</form>

<div class="mt-6 text-center text-sm text-zinc-600">
    Don't have an account?

    <a
        href="{{ route('register') }}"
        class="font-medium text-irdi-green hover:underline"
    >
        Create one
    </a>
</div>

</flux:card>

</div>

</div>
</section>

</x-layouts.public>
