<?php

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
    public string $membershipStatus = 'all';
    public string $messagingStatus = 'all';
    public string $designation = 'all';

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function updatedMembershipStatus(): void
    {
        $this->resetPage();
    }

    public function updatedMessagingStatus(): void
    {
        $this->resetPage();
    }

    public function updatedDesignation(): void
    {
        $this->resetPage();
    }

    public function with(): array
    {
        $query = User::query()
            ->with('memberProfile')
            ->select('users.*')
            ->selectSub(function ($query) {
                $query
                    ->from('message_reports')
                    ->join('messages', 'messages.id', '=', 'message_reports.message_id')
                    ->selectRaw('COUNT(*)')
                    ->where(function ($query) {
                        $query
                            ->where(function ($query) {
                                $query
                                    ->whereColumn('messages.sender_id', 'users.id')
                                    ->whereColumn('message_reports.reporter_id', '!=', 'users.id');
                            })
                            ->orWhere(function ($query) {
                                $query
                                    ->whereColumn('messages.recipient_id', 'users.id')
                                    ->whereColumn('message_reports.reporter_id', '!=', 'users.id');
                            });
                    });
            }, 'reports_against_count');

        if ($this->search !== '') {
            $search = '%'.$this->search.'%';

            $query->where(function ($query) use ($search) {
                $query
                    ->where('name', 'like', $search)
                    ->orWhere('email', 'like', $search)
                    ->orWhereHas('memberProfile', function ($query) use ($search) {
                        $query
                            ->where('profile_name', 'like', $search)
                            ->orWhere('username', 'like', $search);
                    });
            });
        }

        if ($this->membershipStatus !== 'all') {
            $query->where('membership_status', $this->membershipStatus);
        }

        if ($this->designation === 'charter') {
            $query->where('is_charter_member', true);
        }

        if ($this->designation === 'admin') {
            $query->where('is_admin', true);
        }

        if ($this->designation === 'moderator') {
            $query->where('is_moderator', true);
        }

        if ($this->messagingStatus === 'active') {
            $query->where(function ($query) {
                $query
                    ->whereNull('messaging_disabled_at')
                    ->orWhere(function ($query) {
                        $query
                            ->whereNotNull('messaging_disabled_until')
                            ->where('messaging_disabled_until', '<=', now());
                    });
            });
        }

        if ($this->messagingStatus === 'restricted') {
            $query->whereNotNull('messaging_disabled_at')
                ->where(function ($query) {
                    $query
                        ->whereNull('messaging_disabled_until')
                        ->orWhere('messaging_disabled_until', '>', now());
                });
        }

        return [
            'members' => $query
                ->orderBy('name')
                ->paginate(20),
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
                Member Management
            </h1>

            <p class="mt-3 max-w-3xl text-zinc-600">
                View IRDI members, membership status, messaging restrictions,
                reports, and administrative history.
            </p>
        </div>

        <div class="mb-8 rounded-xl border border-zinc-200 bg-white p-6 shadow-sm">
            <div class="grid gap-4 md:grid-cols-2 lg:grid-cols-4">

                <flux:input
                    wire:model.live.debounce.300ms="search"
                    label="Search"
                    placeholder="Name, email, profile, or username"
                />

                <flux:select
                    wire:model.live="membershipStatus"
                    label="Membership status"
                >
                    <flux:select.option value="all">
                        All statuses
                    </flux:select.option>

                    <flux:select.option value="active">
                        Active
                    </flux:select.option>

                    <flux:select.option value="pending">
                        Pending
                    </flux:select.option>

                    <flux:select.option value="suspended">
                        Suspended
                    </flux:select.option>

                    <flux:select.option value="inactive">
                        Inactive
                    </flux:select.option>
                </flux:select>

                <flux:select
                    wire:model.live="messagingStatus"
                    label="Messaging status"
                >
                    <flux:select.option value="all">
                        All messaging statuses
                    </flux:select.option>

                    <flux:select.option value="active">
                        Active
                    </flux:select.option>

                    <flux:select.option value="restricted">
                        Restricted
                    </flux:select.option>
                </flux:select>

                <flux:select
                    wire:model.live="designation"
                    label="Staff / Recognition"
                >
                    <flux:select.option value="all">
                        All members
                    </flux:select.option>

                    <flux:select.option value="charter">
                        Charter Members
                    </flux:select.option>

                    <flux:select.option value="admin">
                        Administrators
                    </flux:select.option>

                    <flux:select.option value="moderator">
                        Moderators
                    </flux:select.option>
                </flux:select>

            </div>
        </div>

        <div class="overflow-hidden rounded-xl border border-zinc-200 bg-white shadow-sm">

            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-zinc-200">

                    <thead class="bg-zinc-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wide text-zinc-600">
                            Member
                        </th>

                        <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wide text-zinc-600">
                            Membership
                        </th>

                        <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wide text-zinc-600">
                            Messaging
                        </th>

                        <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wide text-zinc-600">
                            Reports
                        </th>

                        <th class="px-6 py-3 text-right text-xs font-semibold uppercase tracking-wide text-zinc-600">
                            Action
                        </th>
                    </tr>
                    </thead>

                    <tbody class="divide-y divide-zinc-100 bg-white">

                    @forelse ($members as $member)
                        <tr wire:key="member-{{ $member->id }}">

                            <td class="px-6 py-5">
                                <div class="font-semibold text-zinc-900">
                                    {{ $member->name }}
                                </div>

                                <div class="mt-1 text-sm text-zinc-500">
                                    {{ $member->email }}
                                </div>

                                @if ($member->memberProfile)
                                    <div class="mt-2 text-sm text-zinc-600">
                                        {{ $member->memberProfile->profile_name }}

                                        <span class="text-zinc-400">
                                                ·
                                            </span>

                                        <span>
                                                {{ '@'.$member->memberProfile->username }}
                                            </span>
                                    </div>
                                @else
                                    <div class="mt-2 text-sm text-zinc-400">
                                        No member profile
                                    </div>
                                @endif
                            </td>

                            <td class="px-6 py-5">
                                @if ($member->membership_status === 'active')
                                    <flux:badge color="green">
                                        Active
                                    </flux:badge>
                                @elseif ($member->membership_status === 'suspended')
                                    <flux:badge color="red">
                                        Suspended
                                    </flux:badge>
                                @elseif ($member->membership_status === 'pending')
                                    <flux:badge color="amber">
                                        Pending
                                    </flux:badge>
                                @else
                                    <flux:badge color="zinc">
                                        {{ ucfirst($member->membership_status) }}
                                    </flux:badge>
                                @endif

                                @if ($member->member_since)
                                    <div class="mt-2 text-xs text-zinc-500">
                                        Since {{ $member->member_since->format('M j, Y') }}
                                    </div>
                                @endif
                            </td>

                            <td class="px-6 py-5">
                                @if ($member->messagingIsDisabled())
                                    <flux:badge color="red">
                                        Restricted
                                    </flux:badge>

                                    @if ($member->messagingRestrictionIsTemporary())
                                        <div class="mt-2 text-xs text-zinc-500">
                                            Until
                                            {{ $member->messaging_disabled_until->format('M j, Y g:i A') }}
                                        </div>
                                    @else
                                        <div class="mt-2 text-xs text-zinc-500">
                                            Indefinite
                                        </div>
                                    @endif
                                @else
                                    <flux:badge color="green">
                                        Active
                                    </flux:badge>
                                @endif
                            </td>

                            <td class="px-6 py-5">
                                <div class="text-sm font-semibold text-zinc-900">
                                    {{ $member->reports_against_count }}
                                </div>

                                <div class="mt-1 text-xs text-zinc-500">
                                    {{ Str::plural('report', $member->reports_against_count) }}
                                </div>
                            </td>

                            <td class="px-6 py-5 text-right">
                                <flux:button
                                    :href="route('admin.members.show', $member)"
                                    variant="primary"
                                    size="sm"
                                >
                                    View Member
                                </flux:button>
                            </td>

                        </tr>
                    @empty
                        <tr>
                            <td
                                colspan="5"
                                class="px-6 py-12 text-center text-sm text-zinc-500"
                            >
                                No members match your filters.
                            </td>
                        </tr>
                    @endforelse

                    </tbody>
                </table>
            </div>

        </div>

        @if ($members->hasPages())
            <div class="mt-8">
                {{ $members->links() }}
            </div>
        @endif

    </div>
</section>
