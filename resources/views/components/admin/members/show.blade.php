<?php

use App\Models\MembershipEnforcement;
use App\Models\MessageReport;
use App\Models\MessagingEnforcement;
use App\Models\User;
use App\Notifications\MembershipRestoredNotification;
use App\Notifications\MembershipSuspendedNotification;
use App\Notifications\MessagingRestoredNotification;
use App\Notifications\MessagingRestrictedNotification;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Component;



new
#[Layout('components.layouts.public')]
class extends Component
{
    public User $user;
    public string $restrictionType = '7_days';
    public string $enforcementReason = '';
    public string $restoreReason = '';
    public string $suspensionReason = '';
    public string $membershipRestoreReason = '';

    public function mount(User $user): void
    {
        $this->user = $user;

        $this->loadUser();
    }

    public function disableMessaging(): void
    {
        $this->validate([
            'restrictionType' => ['required', 'in:7_days,30_days,permanent'],
            'enforcementReason' => ['required', 'string', 'min:10', 'max:1000'],
        ], [
            'restrictionType.required' => 'Please choose a restriction duration.',
            'restrictionType.in' => 'Please choose a valid restriction duration.',
            'enforcementReason.required' => 'Please enter a reason for disabling messaging.',
            'enforcementReason.min' => 'The enforcement reason must be at least 10 characters.',
            'enforcementReason.max' => 'The enforcement reason must be 1,000 characters or fewer.',
        ]);

        $expiresAt = match ($this->restrictionType) {
            '7_days' => now()->addDays(7),
            '30_days' => now()->addDays(30),
            'permanent' => null,
        };

        $this->user->messaging_disabled_at = now();
        $this->user->messaging_disabled_until = $expiresAt;
        $this->user->save();

        MessagingEnforcement::create([
            'user_id' => $this->user->id,
            'admin_id' => Auth::id(),
            'message_report_id' => null,
            'action' => 'disabled',
            'reason' => $this->enforcementReason,
            'expires_at' => $expiresAt,
        ]);

        $this->user->notify(
            new MessagingRestrictedNotification($expiresAt)
        );

        $this->enforcementReason = '';
        $this->restrictionType = '7_days';

        $this->loadUser();

        session()->flash('status', 'Member messaging has been disabled.');
    }

    public function restoreMessaging(): void
    {
        $this->validate([
            'restoreReason' => ['required', 'string', 'min:10', 'max:1000'],
        ], [
            'restoreReason.required' => 'Please enter a reason for restoring messaging.',
            'restoreReason.min' => 'The restore reason must be at least 10 characters.',
            'restoreReason.max' => 'The restore reason must be 1,000 characters or fewer.',
        ]);

        $this->user->messaging_disabled_at = null;
        $this->user->messaging_disabled_until = null;
        $this->user->save();

        MessagingEnforcement::create([
            'user_id' => $this->user->id,
            'admin_id' => Auth::id(),
            'message_report_id' => null,
            'action' => 'restored',
            'reason' => $this->restoreReason,
            'expires_at' => null,
        ]);

        $this->user->notify(
            new MessagingRestoredNotification
        );

        $this->restoreReason = '';

        $this->loadUser();

        session()->flash('status', 'Member messaging has been restored.');
    }

    public function suspendMembership(): void
    {
        if ($this->user->id === Auth::id()) {
            $this->addError(
                'suspensionReason',
                'You cannot suspend your own administrator account.'
            );

            return;
        }

        if ($this->user->membership_status !== 'active') {
            $this->addError(
                'suspensionReason',
                'Only an active membership can be suspended.'
            );

            return;
        }

        $this->validate([
            'suspensionReason' => ['required', 'string', 'min:10', 'max:1000'],
        ], [
            'suspensionReason.required' => 'Please enter a reason for suspending this membership.',
            'suspensionReason.min' => 'The suspension reason must be at least 10 characters.',
            'suspensionReason.max' => 'The suspension reason must be 1,000 characters or fewer.',
        ]);

        $this->user->membership_status = 'suspended';
        $this->user->save();

        MembershipEnforcement::create([
            'user_id' => $this->user->id,
            'admin_id' => Auth::id(),
            'action' => 'suspended',
            'reason' => $this->suspensionReason,
        ]);

        $this->user->notify(
            new MembershipSuspendedNotification
        );

        $this->suspensionReason = '';

        $this->loadUser();

        session()->flash('status', 'Membership has been suspended.');
    }

    public function restoreMembership(): void
    {
        if ($this->user->membership_status !== 'suspended') {
            $this->addError(
                'membershipRestoreReason',
                'Only a suspended membership can be restored.'
            );

            return;
        }

        $this->validate([
            'membershipRestoreReason' => ['required', 'string', 'min:10', 'max:1000'],
        ], [
            'membershipRestoreReason.required' => 'Please enter a reason for restoring this membership.',
            'membershipRestoreReason.min' => 'The restoration reason must be at least 10 characters.',
            'membershipRestoreReason.max' => 'The restoration reason must be 1,000 characters or fewer.',
        ]);

        $this->user->membership_status = 'active';
        $this->user->save();

        MembershipEnforcement::create([
            'user_id' => $this->user->id,
            'admin_id' => Auth::id(),
            'action' => 'restored',
            'reason' => $this->membershipRestoreReason,
        ]);

        $this->user->notify(
            new MembershipRestoredNotification
        );

        $this->membershipRestoreReason = '';

        $this->loadUser();

        session()->flash('status', 'Membership has been restored.');
    }

    private function loadUser(): void
    {
        $this->user = $this->user->fresh([
            'memberProfile',
            'messagingEnforcements.admin',
            'messagingEnforcements.messageReport',
            'membershipEnforcements.admin',
        ]);
    }

    public function with(): array
    {
        $reports = MessageReport::query()
            ->with([
                'message.sender.memberProfile',
                'message.recipient.memberProfile',
                'reporter.memberProfile',
            ])
            ->whereHas('message', function ($query) {
                $query->where(function ($query) {
                    $query
                        ->where('sender_id', $this->user->id)
                        ->orWhere('recipient_id', $this->user->id);
                });
            })
            ->where('reporter_id', '!=', $this->user->id)
            ->latest()
            ->get();

        return [
            'reports' => $reports,

            'totalReports' => $reports->count(),

            'pendingReports' => $reports
                ->where('status', 'pending')
                ->count(),

            'reviewedReports' => $reports
                ->where('status', 'reviewed')
                ->count(),

            'dismissedReports' => $reports
                ->where('status', 'dismissed')
                ->count(),
        ];
    }
};
?>

<section class="bg-zinc-50">
    <div class="mx-auto max-w-7xl px-6 py-16 lg:px-8 lg:py-20">

        @if (session('status'))
            <div class="mb-6 rounded-xl border border-green-200 bg-green-50 px-5 py-4 text-sm font-medium text-green-800">
                {{ session('status') }}
            </div>
        @endif

        {{-- Back --}}
        <div class="mb-8">
            <a
                href="{{ route('admin.members.index') }}"
                class="text-sm font-medium text-irdi-green hover:underline"
            >
                ← Back to Member Management
            </a>
        </div>

        {{-- Header --}}
        <div class="mb-10">
            <p class="text-sm font-semibold uppercase tracking-wide text-irdi-green">
                Administration
            </p>

            <div class="mt-2 flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
                <div>
                    <h1 class="text-3xl font-bold tracking-tight text-zinc-900 sm:text-4xl">
                        {{ $user->name }}
                    </h1>

                    <p class="mt-2 text-zinc-600">
                        Member account, messaging, reports, and enforcement history.
                    </p>
                </div>

                @if ($user->membership_status === 'active')
                    <flux:badge color="green" size="lg">
                        Active Member
                    </flux:badge>
                @elseif ($user->membership_status === 'pending')
                    <flux:badge color="amber" size="lg">
                        Pending
                    </flux:badge>
                @else
                    <flux:badge color="zinc" size="lg">
                        {{ ucfirst($user->membership_status) }}
                    </flux:badge>
                @endif
            </div>
        </div>

        {{-- Summary --}}
        <div class="mb-8 grid gap-6 lg:grid-cols-2">

            {{-- Account --}}
            <div class="rounded-xl border border-zinc-200 bg-white p-6 shadow-sm">
                <h2 class="text-lg font-semibold text-zinc-900">
                    Account
                </h2>

                <dl class="mt-6 space-y-4">

                    <div>
                        <dt class="text-sm font-medium text-zinc-500">
                            Name
                        </dt>

                        <dd class="mt-1 text-zinc-900">
                            {{ $user->name }}
                        </dd>
                    </div>

                    <div>
                        <dt class="text-sm font-medium text-zinc-500">
                            Email
                        </dt>

                        <dd class="mt-1 text-zinc-900">
                            {{ $user->email }}
                        </dd>
                    </div>

                    <div>
                        <dt class="text-sm font-medium text-zinc-500">
                            Email verification
                        </dt>

                        <dd class="mt-1">
                            @if ($user->email_verified_at)
                                <flux:badge color="green">
                                    Verified
                                </flux:badge>

                                <span class="ml-2 text-sm text-zinc-500">
                                    {{ $user->email_verified_at->format('M j, Y') }}
                                </span>
                            @else
                                <flux:badge color="amber">
                                    Not Verified
                                </flux:badge>
                            @endif
                        </dd>
                    </div>

                    <div>
                        <dt class="text-sm font-medium text-zinc-500">
                            Membership
                        </dt>

                        <dd class="mt-1">
                            @if ($user->membership_status === 'active')
                                <flux:badge color="green">
                                    Active
                                </flux:badge>
                            @elseif ($user->membership_status === 'pending')
                                <flux:badge color="amber">
                                    Pending
                                </flux:badge>
                            @else
                                <flux:badge color="zinc">
                                    {{ ucfirst($user->membership_status) }}
                                </flux:badge>
                            @endif
                        </dd>
                    </div>

                    @if ($user->member_since)
                        <div>
                            <dt class="text-sm font-medium text-zinc-500">
                                Member Since
                            </dt>

                            <dd class="mt-1 text-zinc-900">
                                {{ $user->member_since->format('F j, Y') }}
                            </dd>
                        </div>
                    @endif

                    <div>
                        <dt class="text-sm font-medium text-zinc-500">
                            Code of Ethics
                        </dt>

                        <dd class="mt-1">
                            @if ($user->ethics_agreed_at)
                                <flux:badge color="green">
                                    Accepted
                                </flux:badge>

                                <span class="ml-2 text-sm text-zinc-500">
                                    {{ $user->ethics_agreed_at->format('M j, Y') }}
                                </span>
                            @else
                                <flux:badge color="amber">
                                    Not Accepted
                                </flux:badge>
                            @endif
                        </dd>
                    </div>

                    <div>
                        <dt class="text-sm font-medium text-zinc-500">
                            Best Practices
                        </dt>

                        <dd class="mt-1">
                            @if ($user->best_practices_agreed_at)
                                <flux:badge color="green">
                                    Accepted
                                </flux:badge>

                                <span class="ml-2 text-sm text-zinc-500">
                                    {{ $user->best_practices_agreed_at->format('M j, Y') }}
                                </span>
                            @else
                                <flux:badge color="amber">
                                    Not Accepted
                                </flux:badge>
                            @endif
                        </dd>
                    </div>

                </dl>
            </div>

            {{-- Profile --}}
            <div class="rounded-xl border border-zinc-200 bg-white p-6 shadow-sm">
                <h2 class="text-lg font-semibold text-zinc-900">
                    Member Profile
                </h2>

                @if ($user->memberProfile)

                    <dl class="mt-6 space-y-4">

                        <div>
                            <dt class="text-sm font-medium text-zinc-500">
                                Profile Name
                            </dt>

                            <dd class="mt-1 text-zinc-900">
                                {{ $user->memberProfile->profile_name }}
                            </dd>
                        </div>

                        <div>
                            <dt class="text-sm font-medium text-zinc-500">
                                Username
                            </dt>

                            <dd class="mt-1 text-zinc-900">
                                {{ '@'.$user->memberProfile->username }}
                            </dd>
                        </div>

                        <div>
                            <dt class="text-sm font-medium text-zinc-500">
                                Directory
                            </dt>

                            <dd class="mt-1">
                                @if ($user->memberProfile->directory_visible)
                                    <flux:badge color="green">
                                        Visible
                                    </flux:badge>
                                @else
                                    <flux:badge color="zinc">
                                        Hidden
                                    </flux:badge>
                                @endif
                            </dd>
                        </div>

                        <div>
                            <dt class="text-sm font-medium text-zinc-500">
                                Allows Member Messages
                            </dt>

                            <dd class="mt-1">
                                @if ($user->memberProfile->allow_member_messages)
                                    <flux:badge color="green">
                                        Yes
                                    </flux:badge>
                                @else
                                    <flux:badge color="zinc">
                                        No
                                    </flux:badge>
                                @endif
                            </dd>
                        </div>

                    </dl>

                    <div class="mt-6 border-t border-zinc-100 pt-6">
                        <flux:button
                            :href="route('member-profiles.show', $user->memberProfile)"
                            variant="outline"
                            target="_blank"
                        >
                            View Public Profile
                        </flux:button>
                    </div>

                @else

                    <div class="mt-6 rounded-lg bg-zinc-50 p-5 text-sm text-zinc-600">
                        This member has not created a member profile.
                    </div>

                @endif
            </div>

        </div>

        {{-- Messaging --}}
        <div class="mb-8 rounded-xl border border-zinc-200 bg-white p-6 shadow-sm">

            <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">

                <div>
                    <h2 class="text-lg font-semibold text-zinc-900">
                        Messaging
                    </h2>

                    <p class="mt-1 text-sm text-zinc-500">
                        Administrative messaging restrictions for this member.
                    </p>
                </div>

                <div>
                    @if ($user->messagingIsDisabled())
                        <flux:badge color="red" size="lg">
                            Restricted
                        </flux:badge>
                    @else
                        <flux:badge color="green" size="lg">
                            Active
                        </flux:badge>
                    @endif
                </div>

            </div>

            @if ($user->messagingIsDisabled())
                <div class="mt-6 rounded-lg border border-red-200 bg-red-50 p-5">

                    @if ($user->messagingRestrictionIsTemporary())
                        <div class="font-medium text-red-900">
                            Messaging is temporarily disabled.
                        </div>

                        <div class="mt-1 text-sm text-red-700">
                            Restriction expires
                            {{ $user->messaging_disabled_until->format('F j, Y \a\t g:i A') }}.
                        </div>
                    @else
                        <div class="font-medium text-red-900">
                            Messaging is disabled indefinitely.
                        </div>
                    @endif

                </div>
            @else
                <div class="mt-6 rounded-lg border border-green-200 bg-green-50 p-5">
                    <div class="font-medium text-green-900">
                        No administrative messaging restriction is currently active.
                    </div>
                </div>
            @endif

            <div class="mt-6 border-t border-zinc-200 pt-6">

                @if ($user->messagingIsDisabled())

                    <flux:textarea
                        wire:model="restoreReason"
                        label="Reason for restoring messaging"
                        placeholder="Explain why this member's messaging access should be restored..."
                        rows="3"
                    />

                    <div class="mt-4 flex justify-end">
                        <flux:button
                            type="button"
                            variant="primary"
                            wire:click="restoreMessaging"
                            wire:confirm="Restore this member's ability to send IRDI messages?"
                        >
                            Restore Messaging
                        </flux:button>
                    </div>

                @else

                    <div class="grid gap-5 md:grid-cols-2">

                        <flux:select
                            wire:model="restrictionType"
                            label="Restriction duration"
                        >
                            <option value="7_days">7 days</option>
                            <option value="30_days">30 days</option>
                            <option value="permanent">Permanent</option>
                        </flux:select>

                        <div class="rounded-xl bg-zinc-50 p-4 text-sm leading-6 text-zinc-600">
                            A temporary restriction automatically expires.
                            A permanent restriction remains in effect until an
                            administrator restores messaging.
                        </div>

                    </div>

                    <div class="mt-5">
                        <flux:textarea
                            wire:model="enforcementReason"
                            label="Reason for disabling messaging"
                            placeholder="Explain why this member's messaging access is being restricted..."
                            rows="4"
                        />
                    </div>

                    <div class="mt-4 flex justify-end">
                        <flux:button
                            type="button"
                            variant="danger"
                            wire:click="disableMessaging"
                            wire:confirm="Disable this member's ability to send IRDI messages?"
                        >
                            Disable Messaging
                        </flux:button>
                    </div>

                @endif

            </div>

        </div>

            {{-- Membership Enforcement --}}
            <div class="mb-8 rounded-xl border border-zinc-200 bg-white p-6 shadow-sm">

                <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">

                    <div>
                        <h2 class="text-lg font-semibold text-zinc-900">
                            Membership Enforcement
                        </h2>

                        <p class="mt-1 text-sm text-zinc-500">
                            Suspend or restore this member's IRDI membership.
                        </p>
                    </div>

                    <div>
                        @if ($user->membership_status === 'active')
                            <flux:badge color="green" size="lg">
                                Active
                            </flux:badge>
                        @elseif ($user->membership_status === 'suspended')
                            <flux:badge color="red" size="lg">
                                Suspended
                            </flux:badge>
                        @else
                            <flux:badge color="zinc" size="lg">
                                {{ ucfirst($user->membership_status) }}
                            </flux:badge>
                        @endif
                    </div>

                </div>

                @if ($user->membership_status === 'suspended')

                    <div class="mt-6 rounded-lg border border-red-200 bg-red-50 p-5">
                        <div class="font-medium text-red-900">
                            This IRDI membership is currently suspended.
                        </div>

                        <p class="mt-1 text-sm text-red-700">
                            The member is no longer treated as an active IRDI member until
                            an administrator restores the membership.
                        </p>
                    </div>

                    <div class="mt-6 border-t border-zinc-200 pt-6">

                        <flux:textarea
                            wire:model="membershipRestoreReason"
                            label="Reason for restoring membership"
                            placeholder="Explain why this member's IRDI membership should be restored..."
                            rows="4"
                        />

                        <div class="mt-4 flex justify-end">
                            <flux:button
                                type="button"
                                variant="primary"
                                wire:click="restoreMembership"
                                wire:confirm="Restore this member's IRDI membership?"
                            >
                                Restore Membership
                            </flux:button>
                        </div>

                    </div>

                @elseif ($user->membership_status === 'active')

                    @if ($user->id === auth()->id())

                        <div class="mt-6 rounded-lg border border-amber-200 bg-amber-50 p-5">
                            <div class="font-medium text-amber-900">
                                You cannot suspend your own administrator account.
                            </div>

                            <p class="mt-1 text-sm text-amber-700">
                                Another administrator must perform an enforcement action
                                against this account.
                            </p>
                        </div>

                    @else

                        <div class="mt-6 rounded-lg border border-amber-200 bg-amber-50 p-5">
                            <div class="font-medium text-amber-900">
                                Suspending membership is an account-level enforcement action.
                            </div>

                            <p class="mt-1 text-sm text-amber-700">
                                The member's account and historical records will be preserved,
                                but the member will no longer be treated as an active IRDI member.
                            </p>
                        </div>

                        <div class="mt-6 border-t border-zinc-200 pt-6">

                            <flux:textarea
                                wire:model="suspensionReason"
                                label="Reason for suspending membership"
                                placeholder="Explain why this member's IRDI membership is being suspended..."
                                rows="4"
                            />

                            <div class="mt-4 flex justify-end">
                                <flux:button
                                    type="button"
                                    variant="danger"
                                    wire:click="suspendMembership"
                                    wire:confirm="Suspend this member's IRDI membership? This is an account-level enforcement action."
                                >
                                    Suspend Membership
                                </flux:button>
                            </div>

                        </div>

                    @endif

                @else

                    <div class="mt-6 rounded-lg bg-zinc-50 p-5 text-sm text-zinc-600">
                        Membership enforcement is not available while this account has a
                        {{ $user->membership_status }} membership status.
                    </div>

                @endif

            </div>

        {{-- Reports --}}
        <div class="mb-8 rounded-xl border border-zinc-200 bg-white p-6 shadow-sm">

            <div>
                <h2 class="text-lg font-semibold text-zinc-900">
                    Message Reports
                </h2>

                <p class="mt-1 text-sm text-zinc-500">
                    Message reports filed against this member.
                </p>
            </div>

            <div class="mt-6 grid gap-4 sm:grid-cols-2 lg:grid-cols-4">

                <div class="rounded-lg bg-zinc-50 p-4">
                    <div class="text-2xl font-bold text-zinc-900">
                        {{ $totalReports }}
                    </div>

                    <div class="mt-1 text-sm text-zinc-500">
                        Total Reports
                    </div>
                </div>

                <div class="rounded-lg bg-zinc-50 p-4">
                    <div class="text-2xl font-bold text-zinc-900">
                        {{ $pendingReports }}
                    </div>

                    <div class="mt-1 text-sm text-zinc-500">
                        Pending
                    </div>
                </div>

                <div class="rounded-lg bg-zinc-50 p-4">
                    <div class="text-2xl font-bold text-zinc-900">
                        {{ $reviewedReports }}
                    </div>

                    <div class="mt-1 text-sm text-zinc-500">
                        Reviewed
                    </div>
                </div>

                <div class="rounded-lg bg-zinc-50 p-4">
                    <div class="text-2xl font-bold text-zinc-900">
                        {{ $dismissedReports }}
                    </div>

                    <div class="mt-1 text-sm text-zinc-500">
                        Dismissed
                    </div>
                </div>

            </div>

            @if ($reports->isNotEmpty())
                <div class="mt-6 overflow-hidden rounded-lg border border-zinc-200">

                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-zinc-200">

                            <thead class="bg-zinc-50">
                            <tr>
                                <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wide text-zinc-600">
                                    Report
                                </th>

                                <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wide text-zinc-600">
                                    Reporter
                                </th>

                                <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wide text-zinc-600">
                                    Status
                                </th>

                                <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wide text-zinc-600">
                                    Date
                                </th>

                                <th class="px-5 py-3 text-right text-xs font-semibold uppercase tracking-wide text-zinc-600">
                                    Action
                                </th>
                            </tr>
                            </thead>

                            <tbody class="divide-y divide-zinc-100 bg-white">

                            @foreach ($reports as $report)
                                <tr wire:key="report-{{ $report->id }}">

                                    <td class="px-5 py-4">
                                        <div class="font-medium text-zinc-900">
                                            {{ ucfirst(str_replace('_', ' ', $report->reason)) }}
                                        </div>

                                        @if ($report->message)
                                            <div class="mt-1 text-sm text-zinc-500">
                                                {{ $report->message->subject }}
                                            </div>
                                        @endif
                                    </td>

                                    <td class="px-5 py-4 text-sm text-zinc-700">
                                        {{ $report->reporter?->name ?? 'Unknown' }}
                                    </td>

                                    <td class="px-5 py-4">
                                        @if ($report->status === 'pending')
                                            <flux:badge color="amber">
                                                Pending
                                            </flux:badge>
                                        @elseif ($report->status === 'reviewed')
                                            <flux:badge color="green">
                                                Reviewed
                                            </flux:badge>
                                        @elseif ($report->status === 'dismissed')
                                            <flux:badge color="zinc">
                                                Dismissed
                                            </flux:badge>
                                        @else
                                            <flux:badge color="zinc">
                                                {{ ucfirst($report->status) }}
                                            </flux:badge>
                                        @endif
                                    </td>

                                    <td class="px-5 py-4 text-sm text-zinc-500">
                                        {{ $report->created_at->format('M j, Y') }}
                                    </td>

                                    <td class="px-5 py-4 text-right">
                                        <flux:button
                                            :href="route('admin.message-reports.show', $report)"
                                            variant="outline"
                                            size="sm"
                                        >
                                            View Report
                                        </flux:button>
                                    </td>

                                </tr>
                            @endforeach

                            </tbody>
                        </table>
                    </div>

                </div>
            @else
                <div class="mt-6 rounded-lg bg-zinc-50 p-6 text-center text-sm text-zinc-500">
                    No message reports have been filed against this member.
                </div>
            @endif

        </div>

        {{-- Messaging Enforcement History --}}
        <div class="rounded-xl border border-zinc-200 bg-white p-6 shadow-sm">

            <div>
                <h2 class="text-lg font-semibold text-zinc-900">
                    Messaging Enforcement History
                </h2>

                <p class="mt-1 text-sm text-zinc-500">
                    Administrative messaging actions previously applied to this member.
                </p>
            </div>

            @if ($user->messagingEnforcements->isNotEmpty())

                <div class="mt-6 space-y-4">

                    @foreach ($user->messagingEnforcements->sortByDesc('created_at') as $enforcement)

                        <div
                            wire:key="enforcement-{{ $enforcement->id }}"
                            class="rounded-lg border border-zinc-200 p-5"
                        >
                            <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">

                                <div>
                                    <div class="font-semibold text-zinc-900">
                                        {{ ucfirst(str_replace('_', ' ', $enforcement->action)) }}
                                    </div>

                                    <div class="mt-1 text-sm text-zinc-500">
                                        {{ $enforcement->created_at->format('F j, Y \a\t g:i A') }}

                                        @if ($enforcement->admin)
                                            · {{ $enforcement->admin->name }}
                                        @endif
                                    </div>
                                </div>

                                @if ($enforcement->expires_at)
                                    <flux:badge color="amber">
                                        Expires {{ $enforcement->expires_at->format('M j, Y') }}
                                    </flux:badge>
                                @endif

                            </div>

                            @if ($enforcement->reason)
                                <div class="mt-4 text-sm text-zinc-700">
                                    {{ $enforcement->reason }}
                                </div>
                            @endif

                            @if ($enforcement->messageReport)
                                <div class="mt-4">
                                    <a
                                        href="{{ route('admin.message-reports.show', $enforcement->messageReport) }}"
                                        class="text-sm font-medium text-irdi-green hover:underline"
                                    >
                                        View related message report
                                    </a>
                                </div>
                            @endif

                        </div>

                    @endforeach

                </div>

            @else

                <div class="mt-6 rounded-lg bg-zinc-50 p-6 text-center text-sm text-zinc-500">
                    No messaging enforcement actions have been recorded for this member.
                </div>

            @endif

        </div>

            {{-- Membership Enforcement History --}}
            <div class="mt-8 rounded-xl border border-zinc-200 bg-white p-6 shadow-sm">

                <div>
                    <h2 class="text-lg font-semibold text-zinc-900">
                        Membership Enforcement History
                    </h2>

                    <p class="mt-1 text-sm text-zinc-500">
                        Account-level membership actions previously applied to this member.
                    </p>
                </div>

                @if ($user->membershipEnforcements->isNotEmpty())

                    <div class="mt-6 space-y-4">

                        @foreach ($user->membershipEnforcements->sortByDesc('created_at') as $enforcement)

                            <div
                                wire:key="membership-enforcement-{{ $enforcement->id }}"
                                class="rounded-lg border border-zinc-200 p-5"
                            >
                                <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">

                                    <div>
                                        <div class="font-semibold text-zinc-900">
                                            Membership {{ ucfirst($enforcement->action) }}
                                        </div>

                                        <div class="mt-1 text-sm text-zinc-500">
                                            {{ $enforcement->created_at->format('F j, Y \a\t g:i A') }}

                                            @if ($enforcement->admin)
                                                · {{ $enforcement->admin->name }}
                                            @endif
                                        </div>
                                    </div>

                                    @if ($enforcement->action === 'suspended')
                                        <flux:badge color="red">
                                            Suspended
                                        </flux:badge>
                                    @elseif ($enforcement->action === 'restored')
                                        <flux:badge color="green">
                                            Restored
                                        </flux:badge>
                                    @endif

                                </div>

                                <div class="mt-4 text-sm text-zinc-700">
                                    {{ $enforcement->reason }}
                                </div>

                            </div>

                        @endforeach

                    </div>

                @else

                    <div class="mt-6 rounded-lg bg-zinc-50 p-6 text-center text-sm text-zinc-500">
                        No membership enforcement actions have been recorded for this member.
                    </div>

                @endif

            </div>

    </div>
</section>
