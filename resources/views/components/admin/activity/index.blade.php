<?php

use App\Models\StaffActivityLog;
use App\Models\User;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithPagination;

new
#[Layout('components.layouts.public')]
class extends Component
{
    use WithPagination;

    public string $search = '';

    public string $action = 'all';

    public string $category = 'all';

    public string $actor = 'all';

    public string $targetUser = 'all';

    public string $fromDate = '';

    public string $toDate = '';

    public string $sort = 'newest';

    public int $perPage = 25;

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function updatedAction(): void
    {
        $this->resetPage();
    }

    public function updatedCategory(): void
    {
        $this->resetPage();
    }

    public function updatedActor(): void
    {
        $this->resetPage();
    }

    public function updatedTargetUser(): void
    {
        $this->resetPage();
    }

    public function updatedFromDate(): void
    {
        $this->resetPage();
    }

    public function updatedToDate(): void
    {
        $this->resetPage();
    }

    public function updatedSort(): void
    {
        $this->resetPage();
    }

    public function updatedPerPage(): void
    {
        $this->resetPage();
    }

    public function clearFilters(): void
    {
        $this->search = '';
        $this->action = 'all';
        $this->category = 'all';
        $this->actor = 'all';
        $this->targetUser = 'all';
        $this->fromDate = '';
        $this->toDate = '';
        $this->sort = 'newest';
        $this->perPage = 25;

        $this->resetPage();
    }

    public function with(): array
    {
        $query = StaffActivityLog::query()
            ->with([
                'actor',
                'targetUser.memberProfile',
            ]);

        if ($this->search !== '') {
            $search = '%'.$this->search.'%';

            $query->where(function ($query) use ($search) {
                $query
                    ->where('action', 'like', $search)
                    ->orWhere('description', 'like', $search)
                    ->orWhere('ip_address', 'like', $search)
                    ->orWhereHas('actor', function ($query) use ($search) {
                        $query
                            ->where('name', 'like', $search)
                            ->orWhere('email', 'like', $search);
                    })
                    ->orWhereHas('targetUser', function ($query) use ($search) {
                        $query
                            ->where('name', 'like', $search)
                            ->orWhere('email', 'like', $search);
                    });
            });
        }

        if ($this->action !== 'all') {
            $query->where('action', $this->action);
        }


        if ($this->category !== 'all') {
            $categoryActions = match ($this->category) {
                'staff_access' => [
                    'moderator_assigned',
                    'moderator_removed',
                    'administrator_assigned',
                    'administrator_removed',
                ],
                'messaging' => [
                    'messaging_disabled',
                    'messaging_restored',
                    'messaging_disabled_from_report',
                    'messaging_restored_from_report',
                ],
                'membership' => [
                    'membership_suspended',
                    'membership_restored',
                    'member_banned',
                    'member_unbanned',
                ],
                'reviews' => [
                    'review_marked_reviewed',
                    'review_hidden',
                    'review_restored',
                ],
                'message_reports' => [
                    'message_report_reviewed',
                    'message_report_dismissed',
                    'message_report_reopened',
                ],
                default => [],
            };

            if ($categoryActions !== []) {
                $query->whereIn('action', $categoryActions);
            }
        }

        if ($this->actor !== 'all') {
            $query->where('actor_id', (int) $this->actor);
        }

        if ($this->targetUser !== 'all') {
            $query->where('target_user_id', (int) $this->targetUser);
        }

        if ($this->fromDate !== '') {
            $query->whereDate('created_at', '>=', $this->fromDate);
        }

        if ($this->toDate !== '') {
            $query->whereDate('created_at', '<=', $this->toDate);
        }

        if ($this->sort === 'oldest') {
            $query->oldest();
        } else {
            $query->latest();
        }

        return [
            'logs' => $query->paginate($this->perPage),

            'actions' => StaffActivityLog::query()
                ->select('action')
                ->distinct()
                ->orderBy('action')
                ->pluck('action'),

            'actors' => User::query()
                ->whereIn(
                    'id',
                    StaffActivityLog::query()
                        ->whereNotNull('actor_id')
                        ->select('actor_id')
                )
                ->orderBy('name')
                ->get(['id', 'name']),

            'targetUsers' => User::query()
                ->whereIn(
                    'id',
                    StaffActivityLog::query()
                        ->whereNotNull('target_user_id')
                        ->select('target_user_id')
                )
                ->orderBy('name')
                ->get(['id', 'name']),
        ];
    }
};
?>

<section class="bg-zinc-50">
    <div class="mx-auto max-w-7xl px-6 py-16 lg:px-8 lg:py-20">

        <div class="mb-10">
            <p class="text-sm font-semibold uppercase tracking-wide text-irdi-green">
                Administration
            </p>

            <h1 class="mt-2 text-3xl font-bold tracking-tight text-zinc-900 sm:text-4xl">
                Staff Activity Log
            </h1>

            <p class="mt-3 max-w-3xl text-zinc-600">
                Review administrative and moderation actions performed by IRDI Administrators and Moderators.
            </p>
        </div>

        <div class="mb-8 rounded-xl border border-zinc-200 bg-white p-6 shadow-sm">

            <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-4">

                <div class="md:col-span-2">
                    <flux:input
                        wire:model.live.debounce.300ms="search"
                        label="Search"
                        placeholder="Staff, member, action, description, or IP"
                        icon="magnifying-glass"
                    />
                </div>

                <flux:select
                    wire:model.live="category"
                    label="Category"
                >
                    <flux:select.option value="all">All categories</flux:select.option>
                    <flux:select.option value="staff_access">Staff Access</flux:select.option>
                    <flux:select.option value="messaging">Messaging</flux:select.option>
                    <flux:select.option value="membership">Membership Enforcement</flux:select.option>
                    <flux:select.option value="reviews">Reviews</flux:select.option>
                    <flux:select.option value="message_reports">Message Reports</flux:select.option>
                </flux:select>

                <flux:select
                    wire:model.live="action"
                    label="Exact action"
                >
                    <flux:select.option value="all">
                        All actions
                    </flux:select.option>

                    @foreach ($actions as $actionOption)
                        <flux:select.option value="{{ $actionOption }}">
                            {{ str($actionOption)->replace('_', ' ')->title() }}
                        </flux:select.option>
                    @endforeach
                </flux:select>

                <flux:select
                    wire:model.live="actor"
                    label="Staff member"
                >
                    <flux:select.option value="all">All staff</flux:select.option>

                    @foreach ($actors as $actorOption)
                        <flux:select.option value="{{ $actorOption->id }}">
                            {{ $actorOption->name }}
                        </flux:select.option>
                    @endforeach
                </flux:select>

                <flux:select
                    wire:model.live="targetUser"
                    label="Affected member"
                >
                    <flux:select.option value="all">All members</flux:select.option>

                    @foreach ($targetUsers as $targetUserOption)
                        <flux:select.option value="{{ $targetUserOption->id }}">
                            {{ $targetUserOption->name }}
                        </flux:select.option>
                    @endforeach
                </flux:select>

                <flux:input
                    wire:model.live="fromDate"
                    type="date"
                    label="From date"
                />

                <flux:input
                    wire:model.live="toDate"
                    type="date"
                    label="To date"
                />

                <flux:select
                    wire:model.live="sort"
                    label="Sort"
                >
                    <flux:select.option value="newest">Newest first</flux:select.option>
                    <flux:select.option value="oldest">Oldest first</flux:select.option>
                </flux:select>

                <flux:select
                    wire:model.live="perPage"
                    label="Per page"
                >
                    <flux:select.option value="25">25</flux:select.option>
                    <flux:select.option value="50">50</flux:select.option>
                    <flux:select.option value="100">100</flux:select.option>
                </flux:select>

            </div>

            <div class="mt-5 flex justify-end">
                <flux:button
                    type="button"
                    variant="outline"
                    wire:click="clearFilters"
                    icon="x-mark"
                >
                    Clear Filters
                </flux:button>
            </div>

        </div>

        <div class="mb-4 flex flex-wrap items-center justify-between gap-3">
            <p class="text-sm text-zinc-600">
                @if ($logs->total() > 0)
                    Showing
                    <span class="font-semibold text-zinc-900">{{ $logs->firstItem() }}</span>
                    –
                    <span class="font-semibold text-zinc-900">{{ $logs->lastItem() }}</span>
                    of
                    <span class="font-semibold text-zinc-900">{{ $logs->total() }}</span>
                    {{ Str::plural('activity', $logs->total()) }}
                @else
                    No activities found
                @endif
            </p>
        </div>

        <div class="overflow-hidden rounded-xl border border-zinc-200 bg-white shadow-sm">

            <div class="overflow-x-auto">

                <table class="min-w-full divide-y divide-zinc-200">

                    <thead class="bg-zinc-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wide text-zinc-600">
                            Date / Time
                        </th>

                        <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wide text-zinc-600">
                            Staff Member
                        </th>

                        <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wide text-zinc-600">
                            Action
                        </th>

                        <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wide text-zinc-600">
                            Affected Member
                        </th>

                        <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wide text-zinc-600">
                            IP Address
                        </th>
                    </tr>
                    </thead>

                    <tbody class="divide-y divide-zinc-100 bg-white">

                    @forelse ($logs as $log)

                        <tr wire:key="staff-activity-{{ $log->id }}" class="align-top">

                            <td class="whitespace-nowrap px-6 py-5 text-sm text-zinc-600">
                                <div class="font-medium text-zinc-900">
                                    {{ $log->created_at->format('M j, Y') }}
                                </div>

                                <div class="mt-1 text-xs text-zinc-500">
                                    {{ $log->created_at->format('g:i A') }}
                                </div>
                            </td>

                            <td class="px-6 py-5">

                                @if ($log->actor)
                                    <div class="font-semibold text-zinc-900">
                                        {{ $log->actor->name }}
                                    </div>

                                    <div class="mt-1 flex flex-wrap gap-2">
                                        @if ($log->actor->is_admin)
                                            <flux:badge size="sm" color="red">
                                                Administrator
                                            </flux:badge>
                                        @elseif ($log->actor->is_moderator)
                                            <flux:badge size="sm" color="blue">
                                                Moderator
                                            </flux:badge>
                                        @else
                                            <flux:badge size="sm" color="zinc">
                                                Former Staff
                                            </flux:badge>
                                        @endif
                                    </div>
                                @else
                                    <span class="text-sm text-zinc-500">
                                        Former / deleted user
                                    </span>
                                @endif

                            </td>

                            <td class="min-w-80 px-6 py-5">

                                <div class="flex flex-wrap items-center gap-2">
                                    <flux:badge color="zinc">
                                        {{ str($log->action)->replace('_', ' ')->title() }}
                                    </flux:badge>

                                    <span class="text-xs text-zinc-400">
                                        #{{ $log->id }}
                                    </span>
                                </div>

                                <p class="mt-3 text-sm leading-6 text-zinc-700">
                                    {{ $log->description }}
                                </p>

                                @if ($log->metadata)
                                    <details class="mt-3">
                                        <summary class="cursor-pointer text-sm font-medium text-irdi-green hover:underline">
                                            View details
                                        </summary>

                                        <div class="mt-3 rounded-lg bg-zinc-50 p-4">
                                            <dl class="space-y-2 text-sm">

                                                @foreach ($log->metadata as $key => $value)
                                                    <div class="grid gap-1 sm:grid-cols-[12rem_minmax(0,1fr)]">
                                                        <dt class="font-medium text-zinc-500">
                                                            {{ str($key)->replace('_', ' ')->title() }}
                                                        </dt>

                                                        <dd class="break-words text-zinc-700">
                                                            @if (is_bool($value))
                                                                {{ $value ? 'Yes' : 'No' }}
                                                            @elseif (is_array($value))
                                                                {{ json_encode($value, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) }}
                                                            @elseif ($value === null)
                                                                —
                                                            @else
                                                                {{ $value }}
                                                            @endif
                                                        </dd>
                                                    </div>
                                                @endforeach

                                            </dl>
                                        </div>
                                    </details>
                                @endif

                            </td>

                            <td class="px-6 py-5">

                                @if ($log->targetUser)
                                    <div class="font-semibold text-zinc-900">
                                        {{ $log->targetUser->name }}
                                    </div>

                                    @if ($log->targetUser->memberProfile)
                                        <div class="mt-1 text-sm text-zinc-500">
                                            {{ '@'.$log->targetUser->memberProfile->username }}
                                        </div>
                                    @endif

                                    <div class="mt-3">
                                        <flux:button
                                            :href="route('admin.members.show', $log->targetUser)"
                                            variant="outline"
                                            size="sm"
                                        >
                                            View Member
                                        </flux:button>
                                    </div>
                                @else
                                    <span class="text-sm text-zinc-500">
                                        No member target
                                    </span>
                                @endif

                            </td>

                            <td class="whitespace-nowrap px-6 py-5 text-sm text-zinc-600">
                                {{ $log->ip_address ?? '—' }}
                            </td>

                        </tr>

                    @empty

                        <tr>
                            <td colspan="5" class="px-6 py-14 text-center">
                                <div class="text-sm font-medium text-zinc-900">
                                    No staff activity found
                                </div>

                                <p class="mt-2 text-sm text-zinc-500">
                                    Try changing your search or action filter.
                                </p>
                            </td>
                        </tr>

                    @endforelse

                    </tbody>

                </table>

            </div>

        </div>

        @if ($logs->hasPages())
            <div class="mt-8">
                {{ $logs->links() }}
            </div>
        @endif

    </div>
</section>
