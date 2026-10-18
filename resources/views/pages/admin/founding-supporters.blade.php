<x-layouts.public title="Manage Founding Supporters | IRDI" description="Manage optional Kickstarter supporter recognition.">
    <main class="mx-auto max-w-6xl space-y-8 px-6 py-12">
        <div>
            <h1 class="text-3xl font-bold text-irdi-green">Founding Supporters</h1>
            <p class="mt-2 text-zinc-600">Only publish a supporter after receiving their explicit consent. Recognition is separate from IRDI membership.</p>
            <a href="{{ route('founding-supporters') }}" class="mt-2 inline-block underline">View public recognition page</a>
        </div>
        @if(session('status')) <p role="status" class="rounded bg-green-50 p-3 text-green-900">{{ session('status') }}</p> @endif
        @if($errors->any())
            <div role="alert" class="rounded border border-red-300 bg-red-50 p-4 text-red-900">
                <p class="font-semibold">Please correct the following:</p>
                <ul class="list-disc pl-5">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
            </div>
        @endif
        <section class="rounded-xl border border-zinc-200 p-6">
            <h2 class="mb-4 text-xl font-semibold">Add supporter</h2>
            <form method="POST" action="{{ route('admin.founding-supporters.store') }}" class="grid gap-4 md:grid-cols-2">
                @csrf
                <label class="block">Display name <input name="display_name" required maxlength="100" value="{{ old('display_name') }}" class="mt-1 w-full rounded border p-2"></label>
                <label class="block">Kickstarter backer ID (optional, internal only) <input name="kickstarter_backer_id" maxlength="255" value="{{ old('kickstarter_backer_id') }}" class="mt-1 w-full rounded border p-2"></label>
                <label class="block">Recognition level <select name="tier" class="mt-1 w-full rounded border p-2"><option value="launch">Launch ($10)</option><option value="charter">Charter ($25)</option><option value="founder">Founder ($50)</option></select></label>
                <label class="flex items-center gap-2"><input type="hidden" name="is_public" value="0"><input type="checkbox" name="is_public" value="1" @checked(old('is_public') === '1')> Supporter has explicitly opted in to public recognition</label>
                <div class="md:col-span-2"><button type="submit" class="rounded bg-irdi-green px-5 py-2 text-white">Add supporter</button></div>
            </form>
        </section>
        <section class="space-y-4">
            <h2 class="text-xl font-semibold">Existing supporters</h2>
            @forelse($supporters as $supporter)
                <div class="rounded-xl border border-zinc-200 p-5" id="supporter-{{ $supporter->id }}">
                    <form method="POST" action="{{ route('admin.founding-supporters.update', $supporter) }}" class="grid gap-3 md:grid-cols-2">
                        @csrf @method('PUT')
                        <label>Display name <input name="display_name" required maxlength="100" value="{{ $supporter->display_name }}" class="mt-1 w-full rounded border p-2"></label>
                        <label>Backer ID <input name="kickstarter_backer_id" maxlength="255" value="{{ $supporter->kickstarter_backer_id }}" class="mt-1 w-full rounded border p-2"></label>
                        <label>Level <select name="tier" class="mt-1 w-full rounded border p-2">@foreach(['launch' => 'Launch', 'charter' => 'Charter', 'founder' => 'Founder'] as $key => $label)<option value="{{ $key }}" @selected($supporter->tier === $key)>{{ $label }}</option>@endforeach</select></label>
                        <label class="flex items-center gap-2"><input type="hidden" name="is_public" value="0"><input type="checkbox" name="is_public" value="1" @checked($supporter->is_public)> Public recognition consent confirmed</label>
                        <div><button class="rounded bg-irdi-green px-4 py-2 text-white">Save changes</button></div>
                    </form>
                    <form method="POST" action="{{ route('admin.founding-supporters.destroy', $supporter) }}" class="mt-3">@csrf @method('DELETE')<button class="text-red-700 underline" onclick="return confirm('Remove this supporter?')">Remove supporter</button></form>
                </div>
            @empty
                <p class="text-zinc-600">No supporters have been added yet.</p>
            @endforelse
            {{ $supporters->links() }}
        </section>
    </main>
</x-layouts.public>
