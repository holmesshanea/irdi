<x-layouts.public title="Landowner permissions | IRDI" :noindex="true">
<section class="mx-auto max-w-5xl space-y-8 px-6 py-12"><h1 class="text-3xl font-bold">Landowner permissions</h1>
<p>Only the landowner can grant or withdraw access. Never enter a private property address here; use a general label.</p>
@if(session('status'))<p role="status">{{ session('status') }}</p>@endif
@if($errors->any())<ul class="text-red-700">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>@endif
<form method="POST" action="{{ route('account.permissions.store') }}" class="grid gap-4 rounded-xl border p-6">@csrf
<label>Member profile <select name="member_profile_id" required class="w-full border p-2">@foreach($profiles as $profile)<option value="{{ $profile->id }}">{{ $profile->profile_name }}</option>@endforeach</select></label>
<label>Landowner email <input class="w-full border p-2" type="email" name="landowner_email" value="{{ old('landowner_email') }}" required></label>
<label>Property label (not street address) <input class="w-full border p-2" name="property_label" maxlength="150" required value="{{ old('property_label') }}"></label>
<label>Proposed activity <textarea class="w-full border p-2" name="proposed_activity" required>{{ old('proposed_activity') }}</textarea></label>
<label>Requested conditions <textarea class="w-full border p-2" name="requested_conditions">{{ old('requested_conditions') }}</textarea></label>
<label>Start date <input class="border p-2" type="date" name="starts_on" required></label>
<label>End date <input class="border p-2" type="date" name="ends_on" required></label>
<button class="rounded bg-irdi-green px-5 py-3 text-white">Email permission request</button></form>
<h2 class="text-2xl font-bold">Requests</h2><div class="space-y-3">@forelse($permissions as $permission)<a class="block rounded border p-4" href="{{ route('account.permissions.show', $permission) }}"><strong>{{ $permission->property_label }}</strong> — {{ ucfirst($permission->effectiveStatus()) }} — {{ $permission->starts_on->toDateString() }} to {{ $permission->ends_on->toDateString() }}</a>@empty<p>No requests yet.</p>@endforelse</div>{{ $permissions->links() }}
</section></x-layouts.public>
