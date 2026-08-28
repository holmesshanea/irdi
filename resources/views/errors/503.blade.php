<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1"
    >

    <title>Temporarily Unavailable | IRDI</title>

    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>

<body class="min-h-screen bg-white text-zinc-900">

<main class="flex min-h-screen items-center justify-center px-6 py-16">

    <div class="w-full max-w-2xl text-center">

        <p class="text-sm font-semibold uppercase tracking-[0.2em] text-irdi-gold">
            Error 503
        </p>

        <h1 class="mt-4 text-4xl font-bold tracking-tight text-irdi-green sm:text-5xl">
            IRDI is temporarily unavailable.
        </h1>

        <p class="mx-auto mt-6 max-w-xl text-base leading-7 text-zinc-600">
            We may be performing maintenance or making improvements to the
            website. Please check back shortly.
        </p>

        <div class="mx-auto mt-8 h-1 w-16 rounded-full bg-irdi-gold"></div>

        <div class="mt-10 rounded-2xl border border-zinc-200 bg-zinc-50 p-8">

            <h2 class="text-xl font-semibold text-zinc-900">
                We’ll be back soon.
            </h2>

            <p class="mt-2 text-sm leading-6 text-zinc-600">
                Thank you for your patience while we work to restore
                access to IRDI.
            </p>

            <div class="mt-6">

                <a
                    href="/"
                    class="inline-flex items-center justify-center rounded-lg bg-irdi-green px-5 py-2.5 text-sm font-semibold text-white transition hover:opacity-90"
                >
                    Try Again
                </a>

            </div>

        </div>

        <p class="mt-8 text-sm text-zinc-500">
            International Responsible Detectorist Institute
        </p>

    </div>

</main>

</body>
</html>
