<x-layouts.public title="Landowner introduction &amp; permission packet | IRDI" :noindex="true">
<section class="mx-auto max-w-5xl space-y-8 px-6 py-12">
    <h1 class="text-3xl font-bold">Landowner Introduction &amp; Permission Packet</h1>
    <p>Meet the landowner in person. Introduce yourself with your personalized QR flyer, discuss their conditions, and complete the companion <strong>Landowner Metal Detecting Permission Agreement</strong> together. Both parties sign and keep a copy.</p>
    <p class="rounded-lg border border-amber-300 bg-amber-50 p-4 text-amber-950">The flyer and unsigned agreement do not grant permission. Only an authorized landowner or representative can grant access. IRDI does not certify ownership or authorize entry.</p>
    @if(session('status'))<p role="status">{{ session('status') }}</p>@endif
    @if($profiles->isEmpty())
        <p>No member profiles are available for printing.</p>
    @else
        <div class="grid gap-4 sm:grid-cols-2">
            @foreach($profiles as $profile)
                <div class="rounded-xl border p-5 space-y-3">
                    <h2 class="text-lg font-semibold">{{ $profile->publicName() }}</h2>
                    <p class="text-sm">Print your personalized introduction flyer and its companion handwritten permission agreement as one packet.</p>
                    <a class="inline-block rounded bg-irdi-green px-5 py-3 text-white" href="{{ route('member-profiles.introduction', $profile) }}" target="_blank" rel="noopener">Print introduction &amp; agreement</a>
                </div>
            @endforeach
        </div>
    @endif
    <h2 class="text-2xl font-bold">Earlier digital permission requests</h2>
    <p class="text-sm">Existing email-based requests remain available here for reference. The printed, jointly signed agreement is the recommended approach for new in-person introductions. A signed paper agreement is not automatically recorded or verified by IRDI.</p>
    <div class="space-y-3">@forelse($permissions as $permission)<a class="block rounded border p-4" href="{{ route('account.permissions.show', $permission) }}"><strong>{{ $permission->property_label }}</strong> — {{ ucfirst($permission->effectiveStatus()) }} — {{ $permission->starts_on->toDateString() }} to {{ $permission->ends_on->toDateString() }}</a>@empty<p>No earlier digital requests.</p>@endforelse</div>
    {{ $permissions->links() }}
</section>
</x-layouts.public>
