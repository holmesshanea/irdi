<?php

use App\Models\BanIdentifier;
use App\Models\MembershipEnforcement;
use App\Models\MessageReport;
use App\Models\MessagingEnforcement;
use App\Models\User;
use App\Notifications\MembershipRestoredNotification;
use App\Notifications\MembershipSuspendedNotification;
use App\Notifications\MessagingRestoredNotification;
use App\Notifications\MessagingRestrictedNotification;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
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
    public string $banReason = '';
    public string $unbanReason = '';

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


    public function banMember(): void
    {
        if ($this->user->id === Auth::id()) {
            $this->addError('banReason', 'You cannot ban your own administrator account.');

            return;
        }

        if (! in_array($this->user->membership_status, ['active', 'suspended'], true)) {
            $this->addError('banReason', 'Only an active or suspended membership can be banned.');

            return;
        }

        $this->validate([
            'banReason' => ['required', 'string', 'min:10', 'max:1000'],
        ], [
            'banReason.required' => 'Please enter a reason for banning this member.',
            'banReason.min' => 'The ban reason must be at least 10 characters.',
            'banReason.max' => 'The ban reason must be 1,000 characters or fewer.',
        ]);

        DB::transaction(function (): void {
            $this->user->membership_status = 'banned';
            $this->user->save();

            $enforcement = MembershipEnforcement::create([
                'user_id' => $this->user->id,
                'admin_id' => Auth::id(),
                'action' => 'banned',
                'reason' => $this->banReason,
            ]);

            BanIdentifier::updateOrCreate(
                [
                    'type' => 'email',
                    'value' => strtolower(trim($this->user->email)),
                ],
                [
                    'user_id' => $this->user->id,
                    'membership_enforcement_id' => $enforcement->id,
                    'created_by' => Auth::id(),
                    'reason' => $this->banReason,
                ]
            );

            collect([
                $this->user->registration_ip,
                $this->user->last_login_ip,
            ])
                ->filter()
                ->map(fn ($ip) => trim((string) $ip))
                ->unique()
                ->filter(fn (string $ip) => $this->shouldStoreBanIp($ip))
                ->each(function (string $ip) use ($enforcement): void {
                    BanIdentifier::updateOrCreate(
                        [
                            'type' => 'ip',
                            'value' => $ip,
                        ],
                        [
                            'user_id' => $this->user->id,
                            'membership_enforcement_id' => $enforcement->id,
                            'created_by' => Auth::id(),
                            'reason' => $this->banReason,
                        ]
                    );
                });
        });

        $this->banReason = '';
        $this->loadUser();

        session()->flash(
            'status',
            'Member has been banned. Their email address and any known non-loopback IP addresses have been added to the ban list.'
        );
    }

    private function shouldStoreBanIp(string $ip): bool
    {
        if (filter_var($ip, FILTER_VALIDATE_IP) === false) {
            return false;
        }

        if ($ip === '::1' || $ip === '0.0.0.0') {
            return false;
        }

        if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4) !== false) {
            $firstOctet = (int) explode('.', $ip)[0];

            if ($firstOctet === 127) {
                return false;
            }
        }

        return true;
    }

    public function unbanMember(): void
    {
        if ($this->user->membership_status !== 'banned') {
            $this->addError('unbanReason', 'Only a banned membership can be unbanned.');

            return;
        }

        $this->validate([
            'unbanReason' => ['required', 'string', 'min:10', 'max:1000'],
        ], [
            'unbanReason.required' => 'Please enter a reason for unbanning this member.',
            'unbanReason.min' => 'The unban reason must be at least 10 characters.',
            'unbanReason.max' => 'The unban reason must be 1,000 characters or fewer.',
        ]);

        DB::transaction(function (): void {
            $this->user->membership_status = 'active';
            $this->user->save();

            MembershipEnforcement::create([
                'user_id' => $this->user->id,
                'admin_id' => Auth::id(),
                'action' => 'unbanned',
                'reason' => $this->unbanReason,
            ]);

            BanIdentifier::query()
                ->where(function ($query) {
                    $query
                        ->where('user_id', $this->user->id)
                        ->orWhere(function ($query) {
                            $query
                                ->where('type', 'email')
                                ->where('value', strtolower(trim($this->user->email)));
                        });
                })
                ->delete();
        });

        $this->unbanReason = '';
        $this->loadUser();

        session()->flash(
            'status',
            'Member has been unbanned and their ban identifiers have been removed from the ban list.'
        );
    }

    private function ensureAdministrator(): void
    {
        if (! auth()->user()?->is_admin) {
            abort(403);
        }
    }

    public function makeModerator(): void
    {
        $this->ensureAdministrator();

        if ($this->user->is_admin) {
            $this->addError(
                'staffRole',
                'Remove Administrator access before assigning Moderator access.'
            );

            return;
        }

        if ($this->user->is_moderator) {
            return;
        }

        $this->user->is_moderator = true;
        $this->user->save();

        $this->loadUser();

        session()->flash('status', 'Member has been made a Moderator.');
    }

    public function removeModerator(): void
    {
        $this->ensureAdministrator();

        if (! $this->user->is_moderator) {
            return;
        }

        $this->user->is_moderator = false;
        $this->user->save();

        $this->loadUser();

        session()->flash('status', 'Moderator access has been removed.');
    }

    public function makeAdministrator(): void
    {
        $this->ensureAdministrator();

        if ($this->user->is_admin) {
            return;
        }

        $this->user->is_admin = true;
        $this->user->is_moderator = false;
        $this->user->save();

        $this->loadUser();

        session()->flash('status', 'Member has been made an Administrator.');
    }

    public function removeAdministrator(): void
    {
        $this->ensureAdministrator();

        if ($this->user->id === Auth::id()) {
            $this->addError(
                'staffRole',
                'You cannot remove your own Administrator access.'
            );

            return;
        }

        if (! $this->user->is_admin) {
            return;
        }

        $this->user->is_admin = false;
        $this->user->save();

        $this->loadUser();

        session()->flash('status', 'Administrator access has been removed.');
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
                @elseif ($user->membership_status === 'suspended')
                    <flux:badge color="red" size="lg">
                        Suspended
                    </flux:badge>
                @elseif ($user->membership_status === 'banned')
                    <flux:badge color="red" size="lg">
                        Banned
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
                            @elseif (in_array($user->membership_status, ['suspended', 'banned'], true))
                                <flux:badge color="red">
                                    {{ ucfirst($user->membership_status) }}
                                </flux:badge>
                            @else
                                <flux:badge color="zinc">
                                    {{ ucfirst($user->membership_status) }}
                                </flux:badge>
                            @endif
                        </dd>
                    </div>

                    <div>
                        <dt class="text-sm font-medium text-zinc-500">
                            Staff Access
                        </dt>

                        <dd class="mt-1 flex flex-wrap gap-2">
                            @if ($user->is_admin)
                                <flux:badge color="red">
                                    Administrator
                                </flux:badge>
                            @elseif ($user->is_moderator)
                                <flux:badge color="blue">
                                    Moderator
                                </flux:badge>
                            @else
                                <flux:badge color="zinc">
                                    None
                                </flux:badge>
                            @endif

                            @if ($user->is_charter_member)
                                <flux:badge color="amber">
                                    Charter Member
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
                        @if ($user->membership_status === 'active')
                            <flux:button
                                :href="route('member-profiles.show', $user->memberProfile)"
                                variant="outline"
                                target="_blank"
                            >
                                View Public Profile
                            </flux:button>
                        @else
                            <p class="text-sm text-zinc-500">
                                Public profile unavailable while membership is {{ $user->membership_status }}.
                            </p>
                        @endif
                    </div>

                @else

                    <div class="mt-6 rounded-lg bg-zinc-50 p-5 text-sm text-zinc-600">
                        This member has not created a member profile.
                    </div>

                @endif
            </div>

        </div>

        @if (auth()->user()->is_admin)

            {{-- Staff Management --}}
            <div class="mb-8 rounded-xl border border-zinc-200 bg-white p-6 shadow-sm">

                <div>
                    <h2 class="text-lg font-semibold text-zinc-900">
                        Staff Management
                    </h2>

                    <p class="mt-1 text-sm text-zinc-500">
                        Administrator-only controls for Moderator and Administrator access.
                    </p>
                </div>

                @error('staffRole')
                <div class="mt-5 rounded-lg border border-red-200 bg-red-50 p-4 text-sm text-red-800">
                    {{ $message }}
                </div>
                @enderror

                <div class="mt-6 grid gap-6 md:grid-cols-2">

                    <div class="rounded-xl border border-zinc-200 bg-zinc-50 p-5">
                        <h3 class="font-semibold text-zinc-900">
                            Moderator
                        </h3>

                        <p class="mt-2 text-sm leading-6 text-zinc-600">
                            Moderators can manage members, moderate reviews, handle message reports,
                            and perform member enforcement actions including suspension and bans.
                        </p>

                        <div class="mt-5">
                            @if ($user->is_admin)
                                <p class="text-sm text-zinc-500">
                                    Administrators already have all Moderator permissions.
                                </p>
                            @elseif ($user->is_moderator)
                                <flux:button
                                    type="button"
                                    variant="danger"
                                    wire:click="removeModerator"
                                    wire:confirm="Remove Moderator access from this member?"
                                >
                                    Remove Moderator
                                </flux:button>
                            @else
                                <flux:button
                                    type="button"
                                    variant="primary"
                                    wire:click="makeModerator"
                                    wire:confirm="Make this member an IRDI Moderator?"
                                >
                                    Make Moderator
                                </flux:button>
                            @endif
                        </div>
                    </div>

                    <div class="rounded-xl border border-zinc-200 bg-zinc-50 p-5">
                        <h3 class="font-semibold text-zinc-900">
                            Administrator
                        </h3>

                        <p class="mt-2 text-sm leading-6 text-zinc-600">
                            Administrators have full moderation access and can assign or remove
                            Moderator and Administrator privileges.
                        </p>

                        <div class="mt-5">
                            @if ($user->is_admin)
                                @if ($user->id === auth()->id())
                                    <p class="text-sm text-zinc-500">
                                        You cannot remove your own Administrator access.
                                    </p>
                                @else
                                    <flux:button
                                        type="button"
                                        variant="danger"
                                        wire:click="removeAdministrator"
                                        wire:confirm="Remove Administrator access from this member?"
                                    >
                                        Remove Administrator
                                    </flux:button>
                                @endif
                            @else
                                <flux:button
                                    type="button"
                                    variant="primary"
                                    wire:click="makeAdministrator"
                                    wire:confirm="Make this member an IRDI Administrator? Moderator access will be removed because Administrator includes all Moderator permissions."
                                >
                                    Make Administrator
                                </flux:button>
                            @endif
                        </div>
                    </div>

                </div>

            </div>

        @endif

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
                        Suspend, restore, ban, or unban this member's IRDI membership.
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
                    @elseif ($user->membership_status === 'banned')
                        <flux:badge color="red" size="lg">
                            Banned
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

                @if ($user->id !== auth()->id())
                    <div class="mt-6 border-t border-red-200 pt-6">
                        <div class="rounded-lg border border-red-300 bg-red-50 p-5">
                            <div class="font-medium text-red-900">
                                Escalate this suspension to a permanent account ban.
                            </div>
                            <p class="mt-1 text-sm leading-6 text-red-700">
                                Banning prevents this account from participating in IRDI and adds the member's
                                current email address and any known non-loopback IP addresses to the ban list for registration enforcement.
                            </p>
                        </div>

                        <div class="mt-5">
                            <flux:textarea
                                wire:model="banReason"
                                label="Reason for banning member"
                                placeholder="Explain why this member should be banned from IRDI..."
                                rows="4"
                            />
                        </div>

                        <div class="mt-4 flex justify-end">
                            <flux:button
                                type="button"
                                variant="danger"
                                wire:click="banMember"
                                wire:confirm="Ban this member from IRDI? Their email address and any known non-loopback IP addresses will be added to the ban list."
                            >
                                Ban Member
                            </flux:button>
                        </div>
                    </div>
                @endif

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

                    <div class="mt-6 border-t border-red-200 pt-6">
                        <div class="rounded-lg border border-red-300 bg-red-50 p-5">
                            <div class="font-medium text-red-900">
                                Ban this member from IRDI.
                            </div>
                            <p class="mt-1 text-sm leading-6 text-red-700">
                                This is the strongest account-level enforcement action. The account and historical
                                records are preserved, and the member's current email address and any known non-loopback IP addresses are added to the ban list.
                            </p>
                        </div>

                        <div class="mt-5">
                            <flux:textarea
                                wire:model="banReason"
                                label="Reason for banning member"
                                placeholder="Explain why this member should be banned from IRDI..."
                                rows="4"
                            />
                        </div>

                        <div class="mt-4 flex justify-end">
                            <flux:button
                                type="button"
                                variant="danger"
                                wire:click="banMember"
                                wire:confirm="Ban this member from IRDI? Their email address and any known non-loopback IP addresses will be added to the ban list."
                            >
                                Ban Member
                            </flux:button>
                        </div>
                    </div>

                @endif

            @elseif ($user->membership_status === 'banned')

                <div class="mt-6 rounded-lg border border-red-300 bg-red-50 p-5">
                    <div class="font-medium text-red-900">
                        This member is banned from IRDI.
                    </div>

                    <p class="mt-1 text-sm leading-6 text-red-700">
                        The account is preserved for enforcement history, and the member's email address
                        and any known IP ban identifiers are on the ban list. Unbanning restores the membership to
                        active and removes this account's ban identifiers.
                    </p>
                </div>

                <div class="mt-6 border-t border-zinc-200 pt-6">
                    <flux:textarea
                        wire:model="unbanReason"
                        label="Reason for unbanning member"
                        placeholder="Explain why this member should be allowed to return to IRDI..."
                        rows="4"
                    />

                    <div class="mt-4 flex justify-end">
                        <flux:button
                            type="button"
                            variant="primary"
                            wire:click="unbanMember"
                            wire:confirm="Unban this member and restore their IRDI membership to active?"
                        >
                            Unban Member
                        </flux:button>
                    </div>
                </div>

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
                                @elseif ($enforcement->action === 'banned')
                                    <flux:badge color="red">
                                        Banned
                                    </flux:badge>
                                @elseif ($enforcement->action === 'unbanned')
                                    <flux:badge color="green">
                                        Unbanned
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
