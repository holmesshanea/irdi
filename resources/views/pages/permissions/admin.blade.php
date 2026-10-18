<x-layouts.public title="Permission oversight | IRDI" :noindex="true">
<section class="mx-auto max-w-6xl space-y-6 px-6 py-12">
<h1 class="text-3xl font-bold">Landowner permission oversight</h1>
<p>Private administrative records. IRDI does not grant access to private property.</p>
<nav class="flex flex-wrap gap-3" aria-label="Permission status filters">
@foreach(['all', 'pending', 'approved', 'declined', 'withdrawn'] as $filter)
<a href="{{ route('admin.landowner-permissions.index', ['status' => $filter]) }}" @if($status === $filter) aria-current="page" @endif class="rounded border px-3 py-2">{{ ucfirst($filter) }}</a>
@endforeach
</nav>
<div class="overflow-x-auto"><table class="w-full text-left"><thead><tr><th class="p-2">Request</th><th class="p-2">Member</th><th class="p-2">Status</th><th class="p-2">Dates</th><th class="p-2">Submitted</th></tr></thead><tbody>
@forelse($permissions as $permission)
<tr class="border-t"><td class="p-2">#{{ $permission->id }} — {{ $permission->property_label }}</td><td class="p-2">{{ $permission->memberProfile->publicName() }}</td><td class="p-2">{{ ucfirst($permission->effectiveStatus()) }}</td><td class="p-2">{{ $permission->starts_on->toDateString() }} – {{ $permission->ends_on->toDateString() }}</td><td class="p-2">{{ $permission->created_at->toDateString() }}</td></tr>
@empty<tr><td colspan="5" class="p-3">No permission records found.</td></tr>@endforelse
</tbody></table></div>{{ $permissions->links() }}
</section></x-layouts.public>
