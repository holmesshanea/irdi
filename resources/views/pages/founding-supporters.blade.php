<x-layouts.public
    title="Founding Supporters | IRDI"
    description="Recognizing the Kickstarter backers who helped bring the IRDI Landowner Introduction & Verification Portal Version 1.0 to life."
>
    <section class="bg-irdi-green px-6 py-16 text-center text-white">
        <div class="mx-auto max-w-4xl">
            <p class="text-sm font-semibold uppercase tracking-widest text-irdi-gold-light">IRDI Portal Version 1.0</p>
            <h1 class="mt-4 text-4xl font-bold sm:text-5xl">Our Founding Supporters</h1>
            <p class="mx-auto mt-5 max-w-2xl text-lg text-zinc-100">Thank you to the people helping us build tools that support responsible metal detecting and informed landowner permission decisions.</p>
        </div>
    </section>

    <section class="mx-auto max-w-5xl space-y-12 px-6 py-16">
        @foreach (['founder' => ['Founder Supporters', '$50'], 'charter' => ['Charter Supporters', '$25'], 'launch' => ['Launch Supporters', '$10']] as $tier => [$heading, $amount])
            <section aria-labelledby="{{ $tier }}-heading" class="rounded-2xl border border-zinc-200 bg-white p-6 shadow-sm sm:p-8">
                <div class="flex flex-wrap items-baseline justify-between gap-3 border-b border-zinc-200 pb-4">
                    <h2 id="{{ $tier }}-heading" class="text-2xl font-bold text-irdi-green">{{ $heading }}</h2>
                    <span class="text-sm font-medium text-zinc-600">Kickstarter contribution {{ $amount }}</span>
                </div>
                @if (($supporters[$tier] ?? collect())->isEmpty())
                    <p class="mt-5 text-zinc-600">Supporter names will appear here after the campaign and after each supporter opts in to public recognition.</p>
                @else
                    <ul class="mt-6 grid gap-3 sm:grid-cols-2 lg:grid-cols-3">
                        @foreach ($supporters[$tier] as $supporter)
                            <li class="rounded-lg bg-zinc-50 px-4 py-3 font-medium text-zinc-800">{{ $supporter->display_name }}</li>
                        @endforeach
                    </ul>
                @endif
            </section>
        @endforeach
        <p class="text-center text-sm text-zinc-600">Recognition is optional and commemorates support for this Kickstarter project. It does not indicate IRDI membership, verified standing, professional credentials, or landowner endorsement. Supporters are displayed in one recognition level only.</p>
    </section>
</x-layouts.public>
