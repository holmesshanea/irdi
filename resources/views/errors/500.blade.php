<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1"
    >

    <title>Something Went Wrong | IRDI</title>

    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>

<body class="min-h-screen bg-white text-zinc-900">

<main class="flex min-h-screen items-center justify-center px-6 py-16">

    <div class="w-full max-w-2xl text-center">

        <p class="text-sm font-semibold uppercase tracking-[0.2em] text-irdi-gold">
            Error 500
        </p>

        <h1 class="mt-4 text-4xl font-bold tracking-tight text-irdi-green sm:text-5xl">
            Something went off the trail.
        </h1>

        <p class="mx-auto mt-6 max-w-xl text-base leading-7 text-zinc-600">
            IRDI encountered an unexpected problem while trying to process
            your request. The issue may be temporary.
        </p>

        <div class="mx-auto mt-8 h-1 w-16 rounded-full bg-irdi-gold"></div>

        <div class="mt-10 rounded-2xl border border-zinc-200 bg-zinc-50 p-8">

            <h2 class="text-xl font-semibold text-zinc-900">
                Please try again.
            </h2>

            <p class="mt-2 text-sm leading-6 text-zinc-600">
                Return to IRDI and try again. If the problem continues,
                please contact us and let us know what happened.
            </p>

            <div class="mt-6 flex flex-col justify-center gap-3 sm:flex-row">

                <a
                    href="/"
                    class="inline-flex items-center justify-center rounded-lg bg-irdi-green px-5 py-2.5 text-sm font-semibold text-white transition hover:opacity-90"
                >
                    Return Home
                </a>

                <a
                    href="/contact"
                    class="inline-flex items-center justify-center rounded-lg border border-zinc-300 bg-white px-5 py-2.5 text-sm font-semibold text-zinc-900 transition hover:bg-zinc-50"
                >
                    Contact IRDI
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
