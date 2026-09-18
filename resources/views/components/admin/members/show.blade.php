<?php

use App\Models\BanIdentifier;
use App\Models\MembershipEnforcement;
use App\Models\MessageReport;
use App\Models\MessagingEnforcement;
use App\Models\User;
use App\Notifications\MembershipReinstatedNotification;
use App\Notifications\MembershipReinstatementDeniedNotification;
use App\Notifications\MembershipProbationContinuedNotification;
use App\Notifications\MembershipProbationResolvedNotification;
use App\Notifications\MembershipWarningWithdrawnNotification;
use App\Notifications\MembershipWarningResolvedNotification;
use App\Notifications\MembershipBannedNotification;
use App\Notifications\MembershipAdministrativeReviewClosedNotification;
use App\Notifications\MembershipAdministrativeReviewStartedNotification;
use App\Notifications\MembershipSuspensionContinuedNotification;
use App\Notifications\MembershipProbationNotification;
use App\Notifications\MembershipRestoredNotification;
use App\Notifications\MembershipWarningIssuedNotification;
use App\Notifications\MembershipSuspendedNotification;
use App\Notifications\MessagingRestoredNotification;
use App\Notifications\MessagingRestrictedNotification;
use App\Support\MembershipEnforcementAuthorization;
use App\Support\StaffActivity;
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
    public string $suspensionReviewPeriod = '90';
    public string $suspensionContinueReason = '';
    public string $suspensionContinuePeriod = '90';
    public string $membershipRestoreReason = '';
    public string $banReason = '';
    public bool $banPermanentlyIneligible = false;
    public string $unbanReason = '';
    public string $reinstatementDenialReason = '';
    public string $reinstatementProbationReason = '';
    public string $reinstatementProbationRequirements = '';
    public string $reinstatementProbationReviewDays = '90';
    public string $warningReason = '';
    public string $warningDetails = '';
    public string $warningRequirements = '';
    public string $probationReason = '';
    public string $probationDetails = '';
    public string $probationRequirements = '';
    public string $probationReviewDays = '90';
    public string $probationRestoreReason = '';
    public string $probationContinueReason = '';
    public string $administrativeHoldReason = '';
    public string $administrativeHoldDetails = '';
    public string $administrativeHoldResolutionReason = '';

    public function mount(User $user): void
    {
        $this->user = $user;

        $this->loadUser();
    }

    public function disableMessaging(): void
    {
        $this->ensureCanEnforceTarget();

        if ($this->user->messaging_disabled_at !== null) {
            $this->addError(
                'restrictionType',
                'This member already has a messaging restriction.'
            );

            return;
        }

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

        DB::transaction(function () use ($expiresAt): void {
            $this->user->messaging_disabled_at = now();
            $this->user->messaging_disabled_until = $expiresAt;
            $this->user->save();

            $enforcement = MessagingEnforcement::create([
                'user_id' => $this->user->id,
                'admin_id' => Auth::id(),
                'message_report_id' => null,
                'action' => 'disabled',
                'reason' => $this->enforcementReason,
                'expires_at' => $expiresAt,
            ]);

            StaffActivity::record(
                action: 'messaging_disabled',
                description: 'Disabled messaging for '.$this->user->name.'.',
                targetUserId: $this->user->id,
                subject: $enforcement,
                metadata: [
                    'reason' => $this->enforcementReason,
                    'restriction_type' => $this->restrictionType,
                    'expires_at' => $expiresAt?->toIso8601String(),
                ],
            );
        });

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
        $this->ensureCanEnforceTarget();
        if ($this->user->messaging_disabled_at === null) {
            $this->addError(
                'restoreReason',
                'This member does not currently have a messaging restriction.'
            );

            return;
        }
        $this->validate([
            'restoreReason' => ['required', 'string', 'min:10', 'max:1000'],
        ], [
            'restoreReason.required' => 'Please enter a reason for restoring messaging.',
            'restoreReason.min' => 'The restore reason must be at least 10 characters.',
            'restoreReason.max' => 'The restore reason must be 1,000 characters or fewer.',
        ]);

        DB::transaction(function (): void {
            $this->user->messaging_disabled_at = null;
            $this->user->messaging_disabled_until = null;
            $this->user->save();

            $enforcement = MessagingEnforcement::create([
                'user_id' => $this->user->id,
                'admin_id' => Auth::id(),
                'message_report_id' => null,
                'action' => 'restored',
                'reason' => $this->restoreReason,
                'expires_at' => null,
            ]);

            StaffActivity::record(
                action: 'messaging_restored',
                description: 'Restored messaging for '.$this->user->name.'.',
                targetUserId: $this->user->id,
                subject: $enforcement,
                metadata: [
                    'reason' => $this->restoreReason,
                ],
            );
        });

        $this->user->notify(
            new MessagingRestoredNotification
        );

        $this->restoreReason = '';

        $this->loadUser();

        session()->flash('status', 'Member messaging has been restored.');
    }

    public function issueWarning(): void
    {
        $actor = auth()->user();

        if (
            $actor === null
            || ! MembershipEnforcementAuthorization::canIssueWarning($actor, $this->user)
        ) {
            abort(403);
        }

        if ($this->blockOrdinaryDisciplineDuringAdministrativeReview('warningReason')) {
            return;
        }

        if ($this->user->membership_status !== 'active') {
            $this->addError('warningReason', 'Only an active member can receive a formal warning.');

            return;
        }

        $this->validate([
            'warningReason' => ['required', 'string', 'min:10', 'max:1000'],
            'warningDetails' => ['nullable', 'string', 'max:2000'],
            'warningRequirements' => ['nullable', 'string', 'max:2000'],
        ], [
            'warningReason.required' => 'Please enter a reason for issuing this warning.',
            'warningReason.min' => 'The warning reason must be at least 10 characters.',
            'warningReason.max' => 'The warning reason must be 1,000 characters or fewer.',
            'warningDetails.max' => 'The warning details must be 2,000 characters or fewer.',
            'warningRequirements.max' => 'The corrective requirements must be 2,000 characters or fewer.',
        ]);

        $enforcement = DB::transaction(function (): MembershipEnforcement {
            $enforcement = MembershipEnforcement::create([
                'user_id' => $this->user->id,
                'admin_id' => Auth::id(),
                'action' => 'warning',
                'status' => 'active',
                'reason' => $this->warningReason,
                'details' => filled($this->warningDetails) ? $this->warningDetails : null,
                'starts_at' => now(),
                'restoration_requirements' => filled($this->warningRequirements)
                    ? $this->warningRequirements
                    : null,
            ]);

            StaffActivity::record(
                action: 'membership_warning_issued',
                description: 'Issued a formal membership warning to '.$this->user->name.'.',
                targetUserId: $this->user->id,
                subject: $enforcement,
                metadata: [
                    'reason' => $this->warningReason,
                    'details' => filled($this->warningDetails) ? $this->warningDetails : null,
                    'requirements' => filled($this->warningRequirements)
                        ? $this->warningRequirements
                        : null,
                ],
            );

            return $enforcement;
        });

        $this->user->notify(
            new MembershipWarningIssuedNotification(
                reason: $enforcement->reason,
                requirements: $enforcement->restoration_requirements,
            )
        );

        $this->warningReason = '';
        $this->warningDetails = '';
        $this->warningRequirements = '';

        $this->loadUser();

        session()->flash('status', 'Formal warning has been issued.');
    }

    public function resolveWarning(int $warningId): void
    {
        $actor = auth()->user();

        if (
            $actor === null
            || ! MembershipEnforcementAuthorization::canIssueWarning($actor, $this->user)
        ) {
            abort(403);
        }

        $warning = MembershipEnforcement::query()
            ->whereKey($warningId)
            ->where('user_id', $this->user->id)
            ->where('action', 'warning')
            ->where('status', 'active')
            ->whereNull('resolved_at')
            ->firstOrFail();

        DB::transaction(function () use ($warning): void {
            $warning->update([
                'status' => 'resolved',
                'resolved_at' => now(),
                'resolved_by' => Auth::id(),
            ]);

            StaffActivity::record(
                action: 'membership_warning_resolved',
                description: 'Resolved a formal membership warning for '.$this->user->name.'.',
                targetUserId: $this->user->id,
                subject: $warning,
                metadata: [
                    'warning_id' => $warning->id,
                    'reason' => $warning->reason,
                ],
            );
        });

        $this->user->notify(
            new MembershipWarningResolvedNotification()
        );

        $this->loadUser();

        session()->flash('status', 'Formal warning has been resolved.');
    }

    public function withdrawWarning(int $warningId): void
    {
        $actor = auth()->user();

        if (
            $actor === null
            || ! MembershipEnforcementAuthorization::canIssueWarning($actor, $this->user)
        ) {
            abort(403);
        }

        $warning = MembershipEnforcement::query()
            ->whereKey($warningId)
            ->where('user_id', $this->user->id)
            ->where('action', 'warning')
            ->where('status', 'active')
            ->whereNull('resolved_at')
            ->firstOrFail();

        DB::transaction(function () use ($warning): void {
            $warning->update([
                'status' => 'withdrawn',
                'resolved_at' => now(),
                'resolved_by' => Auth::id(),
            ]);

            StaffActivity::record(
                action: 'membership_warning_withdrawn',
                description: 'Withdrew a formal membership warning for '.$this->user->name.'.',
                targetUserId: $this->user->id,
                subject: $warning,
                metadata: [
                    'warning_id' => $warning->id,
                    'reason' => $warning->reason,
                ],
            );
        });

        $this->user->notify(
            new MembershipWarningWithdrawnNotification()
        );

        $this->loadUser();

        session()->flash('status', 'Formal warning has been withdrawn.');
    }

    public function placeOnProbation(): void
    {
        $actor = auth()->user();

        if (
            $actor === null
            || ! MembershipEnforcementAuthorization::canManageProbation($actor, $this->user)
        ) {
            abort(403);
        }

        if ($this->blockOrdinaryDisciplineDuringAdministrativeReview('probationReason')) {
            return;
        }

        if ($this->user->membership_status !== 'active') {
            $this->addError('probationReason', 'Only an active member can be placed on probation.');

            return;
        }

        if ($this->user->isOnProbation()) {
            $this->addError('probationReason', 'This member is already on probation.');

            return;
        }

        $this->validate([
            'probationReason' => ['required', 'string', 'min:10', 'max:1000'],
            'probationDetails' => ['nullable', 'string', 'max:2000'],
            'probationRequirements' => ['required', 'string', 'min:10', 'max:2000'],
            'probationReviewDays' => ['required', 'in:90,180'],
        ], [
            'probationReason.required' => 'Please enter a reason for placing this member on probation.',
            'probationReason.min' => 'The probation reason must be at least 10 characters.',
            'probationReason.max' => 'The probation reason must be 1,000 characters or fewer.',
            'probationDetails.max' => 'The probation details must be 2,000 characters or fewer.',
            'probationRequirements.required' => 'Please enter the requirements for restoring Good Standing.',
            'probationRequirements.min' => 'The restoration requirements must be at least 10 characters.',
            'probationRequirements.max' => 'The restoration requirements must be 2,000 characters or fewer.',
            'probationReviewDays.required' => 'Please choose when this probation becomes eligible for review.',
            'probationReviewDays.in' => 'Please choose a valid probation review period.',
        ]);

        $reviewDays = (int) $this->probationReviewDays;

        $enforcement = DB::transaction(function () use ($reviewDays): MembershipEnforcement {
            $startsAt = now();

            $enforcement = MembershipEnforcement::create([
                'user_id' => $this->user->id,
                'admin_id' => Auth::id(),
                'action' => 'probation',
                'status' => 'active',
                'reason' => $this->probationReason,
                'details' => filled($this->probationDetails) ? $this->probationDetails : null,
                'starts_at' => $startsAt,
                'eligible_for_review_at' => $startsAt->copy()->addDays($reviewDays),
                'restoration_requirements' => $this->probationRequirements,
            ]);

            StaffActivity::record(
                action: 'membership_probation_started',
                description: 'Placed '.$this->user->name.' on membership probation.',
                targetUserId: $this->user->id,
                subject: $enforcement,
                metadata: [
                    'reason' => $this->probationReason,
                    'details' => filled($this->probationDetails) ? $this->probationDetails : null,
                    'requirements' => $this->probationRequirements,
                    'eligible_for_review_at' => $enforcement->eligible_for_review_at?->toIso8601String(),
                ],
            );

            return $enforcement;
        });

        $this->user->notify(
            new MembershipProbationNotification(
                reason: $enforcement->reason,
                requirements: $enforcement->restoration_requirements,
                eligibleForReviewAt: $enforcement->eligible_for_review_at,
            )
        );

        $this->probationReason = '';
        $this->probationDetails = '';
        $this->probationRequirements = '';
        $this->probationReviewDays = '90';

        $this->loadUser();

        session()->flash('status', 'Member has been placed on probation.');
    }

    public function restoreFromProbation(int $probationId): void
    {
        $actor = auth()->user();

        if (
            $actor === null
            || ! MembershipEnforcementAuthorization::canManageProbation($actor, $this->user)
        ) {
            abort(403);
        }

        $this->validate([
            'probationRestoreReason' => ['required', 'string', 'min:10', 'max:1000'],
        ], [
            'probationRestoreReason.required' => 'Please enter a reason for restoring this member to Good Standing.',
            'probationRestoreReason.min' => 'The restoration reason must be at least 10 characters.',
            'probationRestoreReason.max' => 'The restoration reason must be 1,000 characters or fewer.',
        ]);

        $probation = MembershipEnforcement::query()
            ->whereKey($probationId)
            ->where('user_id', $this->user->id)
            ->where('action', 'probation')
            ->where('status', 'active')
            ->whereNull('resolved_at')
            ->firstOrFail();

        DB::transaction(function () use ($probation): void {
            $probation->update([
                'status' => 'resolved',
                'resolved_at' => now(),
                'resolved_by' => Auth::id(),
            ]);

            StaffActivity::record(
                action: 'membership_probation_resolved',
                description: 'Resolved membership probation for '.$this->user->name.'.',
                targetUserId: $this->user->id,
                subject: $probation,
                metadata: [
                    'probation_id' => $probation->id,
                    'reason' => $this->probationRestoreReason,
                    'original_reason' => $probation->reason,
                ],
            );

        });



        $this->probationRestoreReason = '';

        $this->loadUser();

        $this->user->notify(
            new MembershipProbationResolvedNotification(
                inGoodStanding: $this->user->isInGoodStanding(),
            )
        );

        session()->flash(
            'status',
            $this->user->isInGoodStanding()
                ? 'Probation has been resolved and the member has been restored to Good Standing.'
                : 'Probation has been resolved. The member is not currently in Good Standing because another active accountability action remains.'
        );

    }

    public function continueProbation(int $probationId): void
    {
        $actor = auth()->user();

        if (
            $actor === null
            || ! MembershipEnforcementAuthorization::canManageProbation($actor, $this->user)
        ) {
            abort(403);
        }

        $this->validate([
            'probationContinueReason' => ['required', 'string', 'min:10', 'max:1000'],
        ], [
            'probationContinueReason.required' => 'Please enter a reason for continuing this probation.',
            'probationContinueReason.min' => 'The continuation reason must be at least 10 characters.',
            'probationContinueReason.max' => 'The continuation reason must be 1,000 characters or fewer.',
        ]);

        $probation = MembershipEnforcement::query()
            ->whereKey($probationId)
            ->where('user_id', $this->user->id)
            ->where('action', 'probation')
            ->where('status', 'active')
            ->whereNull('resolved_at')
            ->firstOrFail();

        $previousReviewDate = $probation->eligible_for_review_at;
        $previousReviewRequestedAt = $probation->review_requested_at;
        $nextReviewDate = now()->addDays(90);

        $probation = DB::transaction(function () use (
            $probation,
            $previousReviewDate,
            $previousReviewRequestedAt,
            $nextReviewDate
        ): MembershipEnforcement {
            $probation->update([
                'eligible_for_review_at' => $nextReviewDate,
                'review_requested_at' => null,
            ]);

            StaffActivity::record(
                action: 'membership_probation_continued',
                description: 'Continued membership probation for '.$this->user->name.'.',
                targetUserId: $this->user->id,
                subject: $probation,
                metadata: [
                    'probation_id' => $probation->id,
                    'reason' => $this->probationContinueReason,
                    'previous_eligible_for_review_at' => $previousReviewDate?->toIso8601String(),
                    'previous_review_requested_at' => $previousReviewRequestedAt?->toIso8601String(),
                    'next_eligible_for_review_at' => $nextReviewDate->toIso8601String(),
                ],
            );
            return $probation;
        });

        $this->user->notify(
            new MembershipProbationContinuedNotification(
                eligibleForReviewAt: $probation->eligible_for_review_at,
            )
        );



        $this->probationContinueReason = '';

        $this->loadUser();

        session()->flash('status', 'Probation has been continued. The next formal review will be available in 90 days.');
    }

    public function placeOnAdministrativeHold(): void
    {
        $actor = auth()->user();

        if (
            $actor === null
            || ! MembershipEnforcementAuthorization::canManageAdministrativeHold(
                $actor,
                $this->user
            )
        ) {
            abort(403);
        }

        if ($this->user->membership_status !== 'active') {
            $this->addError(
                'administrativeHoldReason',
                'Only an active member can be placed under administrative review.'
            );

            return;
        }

        if ($this->user->isUnderAdministrativeReview()) {
            $this->addError(
                'administrativeHoldReason',
                'This member is already under administrative review.'
            );

            return;
        }

        $this->validate([
            'administrativeHoldReason' => [
                'required',
                'string',
                'min:10',
                'max:1000',
            ],
            'administrativeHoldDetails' => [
                'nullable',
                'string',
                'max:2000',
            ],
        ], [
            'administrativeHoldReason.required' =>
                'Please enter a reason for placing this member under administrative review.',
            'administrativeHoldReason.min' =>
                'The administrative review reason must be at least 10 characters.',
            'administrativeHoldReason.max' =>
                'The administrative review reason must be 1,000 characters or fewer.',
            'administrativeHoldDetails.max' =>
                'The administrative review details must be 2,000 characters or fewer.',
        ]);

        $enforcement = DB::transaction(function (): MembershipEnforcement {
            $supersededProbation = $this->resolveActiveProbationsForEscalation(
                outcome: 'administrative_review',
                outcomeLabel: 'administrative review',
                reason: $this->administrativeHoldReason,
            );

            $supersededProbationRemainingDays = null;

            if ($supersededProbation?->eligible_for_review_at !== null) {
                $supersededProbationRemainingDays = max(
                    0,
                    now()->startOfDay()->diffInDays(
                        $supersededProbation->eligible_for_review_at->startOfDay(),
                        false
                    )
                );
            }

            $enforcement = MembershipEnforcement::create([

                'user_id' => $this->user->id,
                'admin_id' => Auth::id(),
                'superseded_enforcement_id' => $supersededProbation?->id,
                'superseded_probation_remaining_days' => $supersededProbationRemainingDays,
                'action' => 'administrative_hold',
                'status' => 'active',
                'reason' => $this->administrativeHoldReason,
                'details' => filled($this->administrativeHoldDetails)
                    ? $this->administrativeHoldDetails
                    : null,
                'starts_at' => now(),
            ]);

            StaffActivity::record(
                action: 'membership_administrative_hold_started',
                description: 'Placed '.$this->user->name.' under administrative review.',
                targetUserId: $this->user->id,
                subject: $enforcement,
                metadata: [
                    'reason' => $this->administrativeHoldReason,
                    'details' => filled($this->administrativeHoldDetails)
                        ? $this->administrativeHoldDetails
                        : null,
                ],
            );

            return $enforcement;
        });

        $this->user->notify(
            new MembershipAdministrativeReviewStartedNotification(
                reason: $enforcement->reason,
            )
        );

        $this->administrativeHoldReason = '';
        $this->administrativeHoldDetails = '';

        $this->loadUser();

        session()->flash(
            'status',
            'Member has been placed under administrative review.'
        );
    }

    public function resolveAdministrativeHoldNoAction(int $holdId): void
    {
        $actor = auth()->user();

        if (
            $actor === null
            || ! MembershipEnforcementAuthorization::canManageAdministrativeHold(
                $actor,
                $this->user
            )
        ) {
            abort(403);
        }

        $this->validate([
            'administrativeHoldResolutionReason' => [
                'required',
                'string',
                'min:10',
                'max:1000',
            ],
        ], [
            'administrativeHoldResolutionReason.required' =>
                'Please document why this administrative review is being closed with no action.',
            'administrativeHoldResolutionReason.min' =>
                'The resolution reason must be at least 10 characters.',
            'administrativeHoldResolutionReason.max' =>
                'The resolution reason must be 1,000 characters or fewer.',
        ]);

        $hold = MembershipEnforcement::query()
            ->whereKey($holdId)
            ->where('user_id', $this->user->id)
            ->where('action', 'administrative_hold')
            ->where('status', 'active')
            ->whereNull('resolved_at')
            ->firstOrFail();

        DB::transaction(function () use ($hold): void {
            $hold->update([
                'status' => 'resolved',
                'resolved_at' => now(),
                'resolved_by' => Auth::id(),
            ]);

            $supersededProbation = $hold->supersededEnforcement;

            if (
                $supersededProbation !== null
                && $supersededProbation->action === 'probation'
                && $supersededProbation->user_id === $this->user->id
                && $supersededProbation->status === 'resolved'
            ) {
                $supersededProbation->update([
                    'status' => 'active',
                    'resolved_at' => null,
                    'resolved_by' => null,
                    'eligible_for_review_at' => $hold->superseded_probation_remaining_days !== null
                        ? now()->addDays($hold->superseded_probation_remaining_days)
                        : null,
                ]);

                StaffActivity::record(
                    action: 'membership_probation_restored',
                    description: 'Restored membership probation for '.$this->user->name.' after administrative review closed with no further action.',
                    targetUserId: $this->user->id,
                    subject: $supersededProbation,
                    metadata: [
                        'probation_id' => $supersededProbation->id,
                        'administrative_hold_id' => $hold->id,
                        'reason' => $this->administrativeHoldResolutionReason,
                    ],
                );
            }

            StaffActivity::record(
                action: 'membership_administrative_hold_resolved_no_action',
                description: 'Closed administrative review for '.$this->user->name.' with no further action.',
                targetUserId: $this->user->id,
                subject: $hold,
                metadata: [
                    'hold_id' => $hold->id,
                    'resolution' => 'no_action',
                    'reason' => $this->administrativeHoldResolutionReason,
                    'original_reason' => $hold->reason,
                ],
            );
        });

        $this->user->notify(
            new MembershipAdministrativeReviewClosedNotification()
        );

        $this->administrativeHoldResolutionReason = '';

        $this->loadUser();

        session()->flash(
            'status',
            'Administrative review has been closed with no further action.'
        );
    }

    public function resolveAdministrativeHoldWithWarning(int $holdId): void
    {
        $actor = auth()->user();

        if (
            $actor === null
            || ! MembershipEnforcementAuthorization::canManageAdministrativeHold(
                $actor,
                $this->user
            )
            || ! MembershipEnforcementAuthorization::canIssueWarning(
                $actor,
                $this->user
            )
        ) {
            abort(403);
        }

        if ($this->user->membership_status !== 'active') {
            $this->addError(
                'warningReason',
                'Only an active member can receive a formal warning.'
            );

            return;
        }

        $this->validate([
            'warningReason' => ['required', 'string', 'min:10', 'max:1000'],
            'warningDetails' => ['nullable', 'string', 'max:2000'],
            'warningRequirements' => ['nullable', 'string', 'max:2000'],
        ], [
            'warningReason.required' => 'Please enter a reason for issuing this warning.',
            'warningReason.min' => 'The warning reason must be at least 10 characters.',
            'warningReason.max' => 'The warning reason must be 1,000 characters or fewer.',
            'warningDetails.max' => 'The warning details must be 2,000 characters or fewer.',
            'warningRequirements.max' => 'The corrective requirements must be 2,000 characters or fewer.',
        ]);

        $hold = MembershipEnforcement::query()
            ->whereKey($holdId)
            ->where('user_id', $this->user->id)
            ->where('action', 'administrative_hold')
            ->where('status', 'active')
            ->whereNull('resolved_at')
            ->firstOrFail();

        $warning = DB::transaction(function () use ($hold): MembershipEnforcement {
            $hold->update([
                'status' => 'resolved',
                'resolved_at' => now(),
                'resolved_by' => Auth::id(),
            ]);

            StaffActivity::record(
                action: 'membership_administrative_hold_resolved_warning',
                description: 'Closed administrative review for '.$this->user->name.' with a formal warning.',
                targetUserId: $this->user->id,
                subject: $hold,
                metadata: [
                    'hold_id' => $hold->id,
                    'resolution' => 'warning',
                    'original_reason' => $hold->reason,
                ],
            );

            $warning = MembershipEnforcement::create([
                'user_id' => $this->user->id,
                'admin_id' => Auth::id(),
                'action' => 'warning',
                'status' => 'active',
                'reason' => $this->warningReason,
                'details' => filled($this->warningDetails)
                    ? $this->warningDetails
                    : null,
                'starts_at' => now(),
                'restoration_requirements' => filled($this->warningRequirements)
                    ? $this->warningRequirements
                    : null,
            ]);

            StaffActivity::record(
                action: 'membership_warning_issued',
                description: 'Issued a formal membership warning to '.$this->user->name.'.',
                targetUserId: $this->user->id,
                subject: $warning,
                metadata: [
                    'reason' => $this->warningReason,
                    'details' => filled($this->warningDetails)
                        ? $this->warningDetails
                        : null,
                    'requirements' => filled($this->warningRequirements)
                        ? $this->warningRequirements
                        : null,
                    'administrative_hold_id' => $hold->id,
                ],
            );

            return $warning;
        });

        $this->user->notify(
            new MembershipWarningIssuedNotification(
                reason: $warning->reason,
                requirements: $warning->restoration_requirements,
            )
        );

        $this->warningReason = '';
        $this->warningDetails = '';
        $this->warningRequirements = '';

        $this->loadUser();

        session()->flash(
            'status',
            'Administrative review has been closed with a formal warning.'
        );
    }

    public function resolveAdministrativeHoldWithProbation(int $holdId): void
    {
        $actor = auth()->user();

        if (
            $actor === null
            || ! MembershipEnforcementAuthorization::canManageAdministrativeHold(
                $actor,
                $this->user
            )
            || ! MembershipEnforcementAuthorization::canManageProbation(
                $actor,
                $this->user
            )
        ) {
            abort(403);
        }

        if ($this->user->membership_status !== 'active') {
            $this->addError(
                'probationReason',
                'Only an active member can be placed on probation.'
            );

            return;
        }

        if ($this->user->isOnProbation()) {
            $this->addError(
                'probationReason',
                'This member is already on probation.'
            );

            return;
        }

        $this->validate([
            'probationReason' => ['required', 'string', 'min:10', 'max:1000'],
            'probationDetails' => ['nullable', 'string', 'max:2000'],
            'probationRequirements' => ['required', 'string', 'min:10', 'max:2000'],
            'probationReviewDays' => ['required', 'in:90,180'],
        ], [
            'probationReason.required' => 'Please enter a reason for placing this member on probation.',
            'probationReason.min' => 'The probation reason must be at least 10 characters.',
            'probationReason.max' => 'The probation reason must be 1,000 characters or fewer.',
            'probationDetails.max' => 'The probation details must be 2,000 characters or fewer.',
            'probationRequirements.required' => 'Please enter the requirements for restoring Good Standing.',
            'probationRequirements.min' => 'The restoration requirements must be at least 10 characters.',
            'probationRequirements.max' => 'The restoration requirements must be 2,000 characters or fewer.',
            'probationReviewDays.required' => 'Please choose when this probation becomes eligible for review.',
            'probationReviewDays.in' => 'Please choose a valid probation review period.',
        ]);

        $hold = MembershipEnforcement::query()
            ->whereKey($holdId)
            ->where('user_id', $this->user->id)
            ->where('action', 'administrative_hold')
            ->where('status', 'active')
            ->whereNull('resolved_at')
            ->firstOrFail();

        $reviewDays = (int) $this->probationReviewDays;

        $probation = DB::transaction(function () use (
            $hold,
            $reviewDays
        ): MembershipEnforcement {
            $hold->update([
                'status' => 'resolved',
                'resolved_at' => now(),
                'resolved_by' => Auth::id(),
            ]);

            StaffActivity::record(
                action: 'membership_administrative_hold_resolved_probation',
                description: 'Closed administrative review for '.$this->user->name.' with membership probation.',
                targetUserId: $this->user->id,
                subject: $hold,
                metadata: [
                    'hold_id' => $hold->id,
                    'resolution' => 'probation',
                    'original_reason' => $hold->reason,
                ],
            );

            $startsAt = now();

            $probation = MembershipEnforcement::create([
                'user_id' => $this->user->id,
                'admin_id' => Auth::id(),
                'action' => 'probation',
                'status' => 'active',
                'reason' => $this->probationReason,
                'details' => filled($this->probationDetails)
                    ? $this->probationDetails
                    : null,
                'starts_at' => $startsAt,
                'eligible_for_review_at' => $startsAt->copy()->addDays($reviewDays),
                'restoration_requirements' => $this->probationRequirements,
            ]);

            StaffActivity::record(
                action: 'membership_probation_started',
                description: 'Placed '.$this->user->name.' on membership probation.',
                targetUserId: $this->user->id,
                subject: $probation,
                metadata: [
                    'reason' => $this->probationReason,
                    'details' => filled($this->probationDetails)
                        ? $this->probationDetails
                        : null,
                    'requirements' => $this->probationRequirements,
                    'eligible_for_review_at' => $probation->eligible_for_review_at?->toIso8601String(),
                    'administrative_hold_id' => $hold->id,
                ],
            );

            return $probation;
        });

        $this->user->notify(
            new MembershipProbationNotification(
                reason: $probation->reason,
                requirements: $probation->restoration_requirements,
                eligibleForReviewAt: $probation->eligible_for_review_at,
            )
        );

        $this->probationReason = '';
        $this->probationDetails = '';
        $this->probationRequirements = '';
        $this->probationReviewDays = '90';

        $this->loadUser();

        session()->flash(
            'status',
            'Administrative review has been closed and the member has been placed on probation.'
        );
    }

    public function resolveAdministrativeHoldWithSuspension(int $holdId): void
    {
        $actor = auth()->user();

        if (
            $actor === null
            || ! MembershipEnforcementAuthorization::canManageAdministrativeHold(
                $actor,
                $this->user
            )
            || ! MembershipEnforcementAuthorization::canSuspend(
                $actor,
                $this->user
            )
        ) {
            abort(403);
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
            'suspensionReviewPeriod' => ['required', 'in:30,90,180,indefinite'],
        ], [
            'suspensionReason.required' => 'Please enter a reason for suspending this membership.',
            'suspensionReason.min' => 'The suspension reason must be at least 10 characters.',
            'suspensionReason.max' => 'The suspension reason must be 1,000 characters or fewer.',
            'suspensionReviewPeriod.required' => 'Please choose a suspension review period.',
            'suspensionReviewPeriod.in' => 'Please choose a valid suspension review period.',
        ]);

        $hold = MembershipEnforcement::query()
            ->whereKey($holdId)
            ->where('user_id', $this->user->id)
            ->where('action', 'administrative_hold')
            ->where('status', 'active')
            ->whereNull('resolved_at')
            ->firstOrFail();

        $reviewAt = $this->suspensionReviewPeriod === 'indefinite'
            ? null
            : now()->addDays((int) $this->suspensionReviewPeriod);

        DB::transaction(function () use ($hold, $reviewAt): void {
            $hold->update([
                'status' => 'resolved',
                'resolved_at' => now(),
                'resolved_by' => Auth::id(),
            ]);

            StaffActivity::record(
                action: 'membership_administrative_hold_resolved_suspension',
                description: 'Closed administrative review for '.$this->user->name.' with membership suspension.',
                targetUserId: $this->user->id,
                subject: $hold,
                metadata: [
                    'hold_id' => $hold->id,
                    'resolution' => 'suspension',
                    'original_reason' => $hold->reason,
                ],
            );

            $this->resolveActiveProbationsForEscalation(
                outcome: 'suspension',
                outcomeLabel: 'suspension',
            );

            $this->user->membership_status = 'suspended';
            $this->user->save();

            $enforcement = MembershipEnforcement::create([
                'user_id' => $this->user->id,
                'admin_id' => Auth::id(),
                'action' => 'suspended',
                'status' => 'active',
                'reason' => $this->suspensionReason,
                'starts_at' => now(),
                'eligible_for_review_at' => $reviewAt,
            ]);

            StaffActivity::record(
                action: 'membership_suspended',
                description: 'Suspended IRDI membership for '.$this->user->name.'.',
                targetUserId: $this->user->id,
                subject: $enforcement,
                metadata: [
                    'reason' => $this->suspensionReason,
                    'eligible_for_review_at' => $reviewAt?->toIso8601String(),
                    'administrative_hold_id' => $hold->id,
                ],
            );
        });

        $this->user->notify(
            new MembershipSuspendedNotification
        );

        $this->suspensionReason = '';
        $this->suspensionReviewPeriod = '90';

        $this->loadUser();

        session()->flash(
            'status',
            'Administrative review has been closed and the membership has been suspended.'
        );
    }

    public function resolveAdministrativeHoldWithBan(int $holdId): void
    {
        $actor = auth()->user();

        if (
            $actor === null
            || ! MembershipEnforcementAuthorization::canManageAdministrativeHold(
                $actor,
                $this->user
            )
            || ! MembershipEnforcementAuthorization::canBan(
                $actor,
                $this->user
            )
        ) {
            abort(403);
        }

        if ($this->user->membership_status !== 'active') {
            $this->addError(
                'banReason',
                'Only an active membership under administrative review can be banned through this outcome.'
            );

            return;
        }

        $this->validate([
            'banReason' => ['required', 'string', 'min:10', 'max:1000'],
        ], [
            'banReason.required' => 'Please enter a reason for banning this member.',
            'banReason.min' => 'The ban reason must be at least 10 characters.',
            'banReason.max' => 'The ban reason must be 1,000 characters or fewer.',
        ]);

        $hold = MembershipEnforcement::query()
            ->whereKey($holdId)
            ->where('user_id', $this->user->id)
            ->where('action', 'administrative_hold')
            ->where('status', 'active')
            ->whereNull('resolved_at')
            ->firstOrFail();

        $enforcement = DB::transaction(function () use ($hold): MembershipEnforcement {
            $hold->update([
                'status' => 'resolved',
                'resolved_at' => now(),
                'resolved_by' => Auth::id(),
            ]);

            StaffActivity::record(
                action: 'membership_administrative_hold_resolved_ban',
                description: 'Closed administrative review for '.$this->user->name.' with an IRDI ban.',
                targetUserId: $this->user->id,
                subject: $hold,
                metadata: [
                    'hold_id' => $hold->id,
                    'resolution' => 'ban',
                    'original_reason' => $hold->reason,
                ],
            );

            $this->resolveActiveProbationsForEscalation(
                outcome: 'ban',
                outcomeLabel: 'ban',
            );

            $this->user->membership_status = 'banned';
            $this->user->save();

            $enforcement = MembershipEnforcement::create([
                'user_id' => $this->user->id,
                'admin_id' => Auth::id(),
                'action' => 'banned',
                'status' => 'active',
                'reason' => $this->banReason,
                'starts_at' => now(),
                'reinstatement_eligible_at' => $this->banPermanentlyIneligible
                    ? null
                    : now()->addYears(3),
                'permanently_ineligible' => $this->banPermanentlyIneligible,
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

            StaffActivity::record(
                action: 'member_banned',
                description: 'Banned '.$this->user->name.' from IRDI.',
                targetUserId: $this->user->id,
                subject: $enforcement,
                metadata: [
                    'reason' => $this->banReason,
                    'email_banned' => strtolower(trim($this->user->email)),
                    'registration_ip' => $this->user->registration_ip,
                    'last_login_ip' => $this->user->last_login_ip,
                    'permanently_ineligible' => $this->banPermanentlyIneligible,
                    'reinstatement_eligible_at' => $enforcement->reinstatement_eligible_at?->toIso8601String(),
                    'administrative_hold_id' => $hold->id,
                ],
            );
            return $enforcement;
        });

        $this->user->notify(
            new MembershipBannedNotification(
                reason: $enforcement->reason,
                permanentlyIneligible: $enforcement->permanently_ineligible,
                reinstatementEligibleAt: $enforcement->reinstatement_eligible_at?->format('F j, Y'),
            )
        );

        $this->banReason = '';

        $this->banPermanentlyIneligible = false;

        $this->loadUser();

        session()->flash(
            'status',
            'Administrative review has been closed and the member has been banned from IRDI.'
        );
    }

    public function suspendMembership(): void
    {

        $actor = auth()->user();

        if (
            $actor === null
            || ! MembershipEnforcementAuthorization::canSuspend($actor, $this->user)
        ) {
            abort(403);
        }

        if ($this->blockOrdinaryDisciplineDuringAdministrativeReview('suspensionReason')) {
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
            'suspensionReviewPeriod' => ['required', 'in:30,90,180,indefinite'],
        ], [
            'suspensionReason.required' => 'Please enter a reason for suspending this membership.',
            'suspensionReason.min' => 'The suspension reason must be at least 10 characters.',
            'suspensionReason.max' => 'The suspension reason must be 1,000 characters or fewer.',
            'suspensionReviewPeriod.required' => 'Please choose a suspension review period.',
            'suspensionReviewPeriod.in' => 'Please choose a valid suspension review period.',
        ]);

        $reviewAt = $this->suspensionReviewPeriod === 'indefinite'
            ? null
            : now()->addDays((int) $this->suspensionReviewPeriod);

        DB::transaction(function () use ($reviewAt): void {
            $this->user->membership_status = 'suspended';
            $this->user->save();

            $this->resolveActiveProbationsForEscalation(
                outcome: 'suspension',
                outcomeLabel: 'suspension',
            );

            $enforcement = MembershipEnforcement::create([
                'user_id' => $this->user->id,
                'admin_id' => Auth::id(),
                'action' => 'suspended',
                'status' => 'active',
                'reason' => $this->suspensionReason,
                'starts_at' => now(),
                'eligible_for_review_at' => $reviewAt,
            ]);

            StaffActivity::record(
                action: 'membership_suspended',
                description: 'Suspended IRDI membership for '.$this->user->name.'.',
                targetUserId: $this->user->id,
                subject: $enforcement,
                metadata: [
                    'reason' => $this->suspensionReason,
                    'eligible_for_review_at' => $reviewAt?->toIso8601String(),
                ],
            );
        });

        $this->user->notify(
            new MembershipSuspendedNotification
        );

        $this->suspensionReason = '';
        $this->suspensionReviewPeriod = '90';

        $this->loadUser();

        session()->flash('status', 'Membership has been suspended.');
    }

    public function continueSuspension(int $suspensionId): void
    {
        $actor = auth()->user();

        if (
            $actor === null
            || ! MembershipEnforcementAuthorization::canRestoreSuspension($actor, $this->user)
        ) {
            abort(403);
        }

        if ($this->user->membership_status !== 'suspended') {
            $this->addError(
                'suspensionContinueReason',
                'Only a suspended membership can have its suspension continued.'
            );

            return;
        }

        $this->validate([
            'suspensionContinueReason' => ['required', 'string', 'min:10', 'max:1000'],
            'suspensionContinuePeriod' => ['required', 'in:30,90,180,indefinite'],
        ], [
            'suspensionContinueReason.required' => 'Please enter a reason for continuing this suspension.',
            'suspensionContinueReason.min' => 'The continuation reason must be at least 10 characters.',
            'suspensionContinueReason.max' => 'The continuation reason must be 1,000 characters or fewer.',
            'suspensionContinuePeriod.required' => 'Please choose the next suspension review period.',
            'suspensionContinuePeriod.in' => 'Please choose a valid suspension review period.',
        ]);

        $suspension = MembershipEnforcement::query()
            ->whereKey($suspensionId)
            ->where('user_id', $this->user->id)
            ->where('action', 'suspended')
            ->where('status', 'active')
            ->whereNull('resolved_at')
            ->firstOrFail();

        $previousReviewDate = $suspension->eligible_for_review_at;
        $previousReviewRequestedAt = $suspension->review_requested_at;

        $nextReviewDate = $this->suspensionContinuePeriod === 'indefinite'
            ? null
            : now()->addDays((int) $this->suspensionContinuePeriod);

        DB::transaction(function () use (
            $suspension,
            $previousReviewDate,
            $previousReviewRequestedAt,
            $nextReviewDate
        ): void {
            $suspension->update([
                'eligible_for_review_at' => $nextReviewDate,
                'review_requested_at' => null,
            ]);

            StaffActivity::record(
                action: 'membership_suspension_continued',
                description: 'Continued membership suspension for '.$this->user->name.'.',
                targetUserId: $this->user->id,
                subject: $suspension,
                metadata: [
                    'suspension_id' => $suspension->id,
                    'reason' => $this->suspensionContinueReason,
                    'previous_eligible_for_review_at' => $previousReviewDate?->toIso8601String(),
                    'previous_review_requested_at' => $previousReviewRequestedAt?->toIso8601String(),
                    'next_eligible_for_review_at' => $nextReviewDate?->toIso8601String(),
                ],
            );
        });

        $this->user->notify(
            new MembershipSuspensionContinuedNotification(
                reason: $this->suspensionContinueReason,
                eligibleForReviewAt: $nextReviewDate,
            )
        );

        $this->suspensionContinueReason = '';
        $this->suspensionContinuePeriod = '90';

        $this->loadUser();

        session()->flash(
            'status',
            $nextReviewDate === null
                ? 'Suspension has been continued indefinitely and the member has been notified.'
                : 'Suspension has been continued, a new formal review date has been set, and the member has been notified.'
        );
    }

    public function resolveSuspensionWithProbation(int $suspensionId): void
    {
        $actor = auth()->user();

        if (
            $actor === null
            || ! MembershipEnforcementAuthorization::canRestoreSuspension($actor, $this->user)
            || ! MembershipEnforcementAuthorization::canManageProbation($actor, $this->user)
        ) {
            abort(403);
        }

        if ($this->user->membership_status !== 'suspended') {
            $this->addError(
                'probationReason',
                'Only a suspended membership can be moved from suspension to probation.'
            );

            return;
        }

        $this->validate([
            'probationReason' => ['required', 'string', 'min:10', 'max:1000'],
            'probationDetails' => ['nullable', 'string', 'max:2000'],
            'probationRequirements' => ['required', 'string', 'min:10', 'max:2000'],
            'probationReviewDays' => ['required', 'in:90,180'],
        ], [
            'probationReason.required' => 'Please enter a reason for placing this member on probation.',
            'probationReason.min' => 'The probation reason must be at least 10 characters.',
            'probationReason.max' => 'The probation reason must be 1,000 characters or fewer.',
            'probationDetails.max' => 'The probation details must be 2,000 characters or fewer.',
            'probationRequirements.required' => 'Please enter the requirements for restoring Good Standing.',
            'probationRequirements.min' => 'The restoration requirements must be at least 10 characters.',
            'probationRequirements.max' => 'The restoration requirements must be 2,000 characters or fewer.',
            'probationReviewDays.required' => 'Please choose when this probation becomes eligible for review.',
            'probationReviewDays.in' => 'Please choose a valid probation review period.',
        ]);

        $suspension = MembershipEnforcement::query()
            ->whereKey($suspensionId)
            ->where('user_id', $this->user->id)
            ->where('action', 'suspended')
            ->where('status', 'active')
            ->whereNull('resolved_at')
            ->firstOrFail();

        $reviewDays = (int) $this->probationReviewDays;

        $probation = DB::transaction(function () use (
            $suspension,
            $reviewDays
        ): MembershipEnforcement {
            $suspension->update([
                'status' => 'resolved',
                'resolved_at' => now(),
                'resolved_by' => Auth::id(),
            ]);

            StaffActivity::record(
                action: 'membership_suspension_resolved_probation',
                description: 'Resolved membership suspension for '.$this->user->name.' with probation.',
                targetUserId: $this->user->id,
                subject: $suspension,
                metadata: [
                    'suspension_id' => $suspension->id,
                    'resolution' => 'probation',
                    'original_reason' => $suspension->reason,
                    'probation_reason' => $this->probationReason,
                ],
            );

            $this->user->membership_status = 'active';
            $this->user->save();

            $startsAt = now();

            $probation = MembershipEnforcement::create([
                'user_id' => $this->user->id,
                'admin_id' => Auth::id(),
                'action' => 'probation',
                'status' => 'active',
                'reason' => $this->probationReason,
                'details' => filled($this->probationDetails) ? $this->probationDetails : null,
                'starts_at' => $startsAt,
                'eligible_for_review_at' => $startsAt->copy()->addDays($reviewDays),
                'restoration_requirements' => $this->probationRequirements,
            ]);

            StaffActivity::record(
                action: 'membership_probation_started',
                description: 'Placed '.$this->user->name.' on membership probation after suspension review.',
                targetUserId: $this->user->id,
                subject: $probation,
                metadata: [
                    'reason' => $this->probationReason,
                    'details' => filled($this->probationDetails) ? $this->probationDetails : null,
                    'requirements' => $this->probationRequirements,
                    'eligible_for_review_at' => $probation->eligible_for_review_at?->toIso8601String(),
                    'suspension_id' => $suspension->id,
                ],
            );

            return $probation;
        });

        $this->user->notify(
            new MembershipProbationNotification(
                reason: $probation->reason,
                requirements: $probation->restoration_requirements,
                eligibleForReviewAt: $probation->eligible_for_review_at,
            )
        );

        $this->probationReason = '';
        $this->probationDetails = '';
        $this->probationRequirements = '';
        $this->probationReviewDays = '90';

        $this->loadUser();

        session()->flash(
            'status',
            'Suspension has been resolved and the member has been placed on probation.'
        );
    }

    public function resolveSuspensionWithBan(int $suspensionId): void
    {
        $actor = auth()->user();

        if (
            $actor === null
            || ! MembershipEnforcementAuthorization::canRestoreSuspension($actor, $this->user)
            || ! MembershipEnforcementAuthorization::canBan($actor, $this->user)
        ) {
            abort(403);
        }

        if ($this->user->membership_status !== 'suspended') {
            $this->addError(
                'banReason',
                'Only a suspended membership can be banned through a suspension review.'
            );

            return;
        }

        $this->validate([
            'banReason' => ['required', 'string', 'min:10', 'max:1000'],
        ], [
            'banReason.required' => 'Please enter a reason for banning this member.',
            'banReason.min' => 'The ban reason must be at least 10 characters.',
            'banReason.max' => 'The ban reason must be 1,000 characters or fewer.',
        ]);

        $suspension = MembershipEnforcement::query()
            ->whereKey($suspensionId)
            ->where('user_id', $this->user->id)
            ->where('action', 'suspended')
            ->where('status', 'active')
            ->whereNull('resolved_at')
            ->firstOrFail();

        $enforcement = DB::transaction(function () use ($suspension): MembershipEnforcement {
            $suspension->update([
                'status' => 'resolved',
                'resolved_at' => now(),
                'resolved_by' => Auth::id(),
            ]);

            StaffActivity::record(
                action: 'membership_suspension_resolved_ban',
                description: 'Resolved membership suspension for '.$this->user->name.' with an IRDI ban.',
                targetUserId: $this->user->id,
                subject: $suspension,
                metadata: [
                    'suspension_id' => $suspension->id,
                    'resolution' => 'ban',
                    'reason' => $this->banReason,
                    'original_reason' => $suspension->reason,
                ],
            );

            $this->user->membership_status = 'banned';
            $this->user->save();

            $enforcement = MembershipEnforcement::create([
                'user_id' => $this->user->id,
                'admin_id' => Auth::id(),
                'action' => 'banned',
                'status' => 'active',
                'reason' => $this->banReason,
                'starts_at' => now(),
                'reinstatement_eligible_at' => $this->banPermanentlyIneligible
                    ? null
                    : now()->addYears(3),
                'permanently_ineligible' => $this->banPermanentlyIneligible,
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

            StaffActivity::record(
                action: 'member_banned',
                description: 'Banned '.$this->user->name.' from IRDI.',
                targetUserId: $this->user->id,
                subject: $enforcement,
                metadata: [
                    'reason' => $this->banReason,
                    'email_banned' => strtolower(trim($this->user->email)),
                    'registration_ip' => $this->user->registration_ip,
                    'last_login_ip' => $this->user->last_login_ip,
                    'permanently_ineligible' => $this->banPermanentlyIneligible,
                    'reinstatement_eligible_at' => $enforcement->reinstatement_eligible_at?->toIso8601String(),
                    'suspension_id' => $suspension->id,
                ],
            );

            return $enforcement;
        });

        $this->user->notify(
            new MembershipBannedNotification(
                reason: $enforcement->reason,
                permanentlyIneligible: $enforcement->permanently_ineligible,
                reinstatementEligibleAt: $enforcement->reinstatement_eligible_at?->format('F j, Y'),
            )
        );

        $this->banReason = '';
        $this->banPermanentlyIneligible = false;

        $this->loadUser();

        session()->flash(
            'status',
            'Suspension has been resolved and the member has been banned from IRDI.'
        );
    }

    public function restoreMembership(): void
    {
        $actor = auth()->user();

        if (
            $actor === null
            || ! MembershipEnforcementAuthorization::canRestoreSuspension($actor, $this->user)
        ) {
            abort(403);
        }

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

        DB::transaction(function (): void {
            $activeSuspensions = MembershipEnforcement::query()
                ->where('user_id', $this->user->id)
                ->where('action', 'suspended')
                ->where('status', 'active')
                ->whereNull('resolved_at')
                ->get();

            foreach ($activeSuspensions as $suspension) {
                $suspension->update([
                    'status' => 'resolved',
                    'resolved_at' => now(),
                    'resolved_by' => Auth::id(),
                ]);

                StaffActivity::record(
                    action: 'membership_suspension_resolved_good_standing',
                    description: 'Resolved membership suspension for '.$this->user->name.' through membership restoration.',
                    targetUserId: $this->user->id,
                    subject: $suspension,
                    metadata: [
                        'suspension_id' => $suspension->id,
                        'resolution' => 'good_standing',
                        'reason' => $this->membershipRestoreReason,
                        'original_reason' => $suspension->reason,
                    ],
                );
            }

            $this->user->membership_status = 'active';
            $this->user->save();

            $enforcement = MembershipEnforcement::create([
                'user_id' => $this->user->id,
                'admin_id' => Auth::id(),
                'action' => 'restored',
                'reason' => $this->membershipRestoreReason,
            ]);

            StaffActivity::record(
                action: 'membership_restored',
                description: 'Restored IRDI membership for '.$this->user->name.'.',
                targetUserId: $this->user->id,
                subject: $enforcement,
                metadata: [
                    'reason' => $this->membershipRestoreReason,
                ],
            );
        });

        $this->user->notify(
            new MembershipRestoredNotification
        );

        $this->membershipRestoreReason = '';

        $this->loadUser();

        session()->flash('status', 'Membership has been restored.');
    }


    public function banMember(): void
    {

        $actor = auth()->user();

        if (
            $actor === null
            || ! MembershipEnforcementAuthorization::canBan($actor, $this->user)
        ) {
            abort(403);
        }

        if ($this->blockOrdinaryDisciplineDuringAdministrativeReview('banReason')) {
            return;
        }


        if ($this->user->membership_status !== 'active') {
            $this->addError(
                'banReason',
                'Only an active membership can be banned through this action.'
            );

            return;
        }

        $this->validate([
            'banReason' => ['required', 'string', 'min:10', 'max:1000'],
        ], [
            'banReason.required' => 'Please enter a reason for banning this member.',
            'banReason.min' => 'The ban reason must be at least 10 characters.',
            'banReason.max' => 'The ban reason must be 1,000 characters or fewer.',
        ]);


        $enforcement = DB::transaction(function (): MembershipEnforcement {


            $this->resolveActiveProbationsForEscalation(
                outcome: 'ban',
                outcomeLabel: 'ban',
                reason: $this->banReason,
            );

            $this->user->membership_status = 'banned';
            $this->user->save();

            $enforcement = MembershipEnforcement::create([
                'user_id' => $this->user->id,
                'admin_id' => Auth::id(),
                'action' => 'banned',
                'status' => 'active',
                'reason' => $this->banReason,
                'starts_at' => now(),
                'reinstatement_eligible_at' => $this->banPermanentlyIneligible
                    ? null
                    : now()->addYears(3),
                'permanently_ineligible' => $this->banPermanentlyIneligible,
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

            StaffActivity::record(
                action: 'member_banned',
                description: 'Banned '.$this->user->name.' from IRDI.',
                targetUserId: $this->user->id,
                subject: $enforcement,
                metadata: [
                    'reason' => $this->banReason,
                    'email_banned' => strtolower(trim($this->user->email)),
                    'registration_ip' => $this->user->registration_ip,
                    'last_login_ip' => $this->user->last_login_ip,
                    'permanently_ineligible' => $this->banPermanentlyIneligible,
                    'reinstatement_eligible_at' => $enforcement->reinstatement_eligible_at?->toIso8601String(),
                ],
            );
            return $enforcement;
        });

        $this->user->notify(
            new MembershipBannedNotification(
                reason: $enforcement->reason,
                permanentlyIneligible: $enforcement->permanently_ineligible,
                reinstatementEligibleAt: $enforcement->reinstatement_eligible_at?->format('F j, Y'),
            )
        );

        $this->banReason = '';
        $this->banPermanentlyIneligible = false;
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

    private function currentBanEnforcement(): MembershipEnforcement
    {
        return MembershipEnforcement::query()
            ->where('user_id', $this->user->id)
            ->where('action', 'banned')
            ->where('status', 'active')
            ->whereNull('resolved_at')
            ->latest('id')
            ->firstOrFail();
    }

    private function removeBanIdentifiers(): void
    {
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
    }

    public function denyReinstatement(): void
    {
        $actor = auth()->user();

        if (
            $actor === null
            || ! MembershipEnforcementAuthorization::canUnban($actor, $this->user)
        ) {
            abort(403);
        }

        if ($this->user->membership_status !== 'banned') {
            $this->addError('reinstatementDenialReason', 'Only a banned membership can have reinstatement denied.');

            return;
        }

        $this->validate([
            'reinstatementDenialReason' => ['required', 'string', 'min:10', 'max:1000'],
        ]);

        $ban = $this->currentBanEnforcement();

        if ($ban->review_requested_at === null) {
            $this->addError(
                'reinstatementDenialReason',
                'There is no pending reinstatement petition to deny.'
            );

            return;
        }

        $denial = MembershipEnforcement::create([
            'user_id' => $this->user->id,
            'admin_id' => Auth::id(),
            'action' => 'reinstatement_denied',
            'status' => 'resolved',
            'reason' => $this->reinstatementDenialReason,
            'details' => $ban->member_response,
            'starts_at' => now(),
            'resolved_at' => now(),
            'resolved_by' => Auth::id(),
        ]);

        StaffActivity::record(
            action: 'membership_reinstatement_denied',
            description: 'Denied reinstatement petition for '.$this->user->name.'.',
            targetUserId: $this->user->id,
            subject: $denial,
            metadata: [
                'ban_id' => $ban->id,
                'reason' => $this->reinstatementDenialReason,
                'petition_requested_at' => $ban->review_requested_at?->toIso8601String(),
            ],
        );

        $this->user->notify(
            new MembershipReinstatementDeniedNotification(
                reason: $denial->reason,
            )
        );

        $this->reinstatementDenialReason = '';
        $this->loadUser();

        session()->flash('status', 'The reinstatement petition has been denied. The IRDI ban remains active.');
    }

    private function currentReinstatementPetitionWasDenied(MembershipEnforcement $ban): bool
    {
        if ($ban->review_requested_at === null) {
            return false;
        }

        return MembershipEnforcement::query()
            ->where('user_id', $this->user->id)
            ->where('action', 'reinstatement_denied')
            ->where('created_at', '>=', $ban->review_requested_at)
            ->exists();
    }

    public function reinstateWithProbation(): void
    {
        $actor = auth()->user();

        if (
            $actor === null
            || ! MembershipEnforcementAuthorization::canUnban($actor, $this->user)
            || ! MembershipEnforcementAuthorization::canManageProbation($actor, $this->user)
        ) {
            abort(403);
        }

        if ($this->user->membership_status !== 'banned') {
            $this->addError('reinstatementProbationReason', 'Only a banned membership can be reinstated.');

            return;
        }

        $this->validate([
            'reinstatementProbationReason' => ['required', 'string', 'min:10', 'max:1000'],
            'reinstatementProbationRequirements' => ['required', 'string', 'min:10', 'max:2000'],
            'reinstatementProbationReviewDays' => ['required', 'in:90,180'],
        ]);

        $ban = $this->currentBanEnforcement();

        if ($ban->permanently_ineligible) {
            $this->addError(
                'reinstatementProbationReason',
                'This ban is permanently ineligible for reinstatement.'
            );

            return;
        }

        if ($ban->review_requested_at === null) {
            $this->addError(
                'reinstatementProbationReason',
                'There is no pending reinstatement petition to approve.'
            );

            return;
        }

        if ($this->currentReinstatementPetitionWasDenied($ban)) {
            $this->addError(
                'reinstatementProbationReason',
                'This reinstatement petition has already been denied.'
            );

            return;
        }

        $reviewDays = (int) $this->reinstatementProbationReviewDays;

        $probation = DB::transaction(function () use ($ban, $reviewDays): MembershipEnforcement {
            $ban->update([
                'status' => 'resolved',
                'resolved_at' => now(),
                'resolved_by' => Auth::id(),
            ]);

            $this->user->membership_status = 'active';
            $this->user->save();

            $this->removeBanIdentifiers();

            $startsAt = now();

            $probation = MembershipEnforcement::create([
                'user_id' => $this->user->id,
                'admin_id' => Auth::id(),
                'action' => 'probation',
                'status' => 'active',
                'reason' => $this->reinstatementProbationReason,
                'starts_at' => $startsAt,
                'eligible_for_review_at' => $startsAt->copy()->addDays($reviewDays),
                'restoration_requirements' => $this->reinstatementProbationRequirements,
            ]);

            StaffActivity::record(
                action: 'membership_reinstated_probation',
                description: 'Reinstated '.$this->user->name.' to IRDI on membership probation.',
                targetUserId: $this->user->id,
                subject: $probation,
                metadata: [
                    'ban_id' => $ban->id,
                    'reason' => $this->reinstatementProbationReason,
                    'requirements' => $this->reinstatementProbationRequirements,
                    'eligible_for_review_at' => $probation->eligible_for_review_at?->toIso8601String(),
                ],
            );

            return $probation;
        });

        $this->user->notify(
            new MembershipProbationNotification(
                reason: $probation->reason,
                requirements: $probation->restoration_requirements,
                eligibleForReviewAt: $probation->eligible_for_review_at,
            )
        );

        $this->reinstatementProbationReason = '';
        $this->reinstatementProbationRequirements = '';
        $this->reinstatementProbationReviewDays = '90';
        $this->loadUser();

        session()->flash('status', 'The member has been reinstated to IRDI on membership probation.');
    }

    public function reinstateToGoodStanding(): void
    {
        $actor = auth()->user();

        if (
            $actor === null
            || ! MembershipEnforcementAuthorization::canUnban($actor, $this->user)
        ) {
            abort(403);
        }

        if ($this->user->membership_status !== 'banned') {
            $this->addError('unbanReason', 'Only a banned membership can be reinstated.');

            return;
        }

        $this->validate([
            'unbanReason' => ['required', 'string', 'min:10', 'max:1000'],
        ], [
            'unbanReason.required' => 'Please enter a reason for exceptional direct reinstatement.',
            'unbanReason.min' => 'The reinstatement reason must be at least 10 characters.',
            'unbanReason.max' => 'The reinstatement reason must be 1,000 characters or fewer.',
        ]);

        $ban = $this->currentBanEnforcement();

        if ($ban->permanently_ineligible) {
            $this->addError('unbanReason', 'This ban is permanently ineligible for reinstatement.');

            return;
        }

        if ($ban->review_requested_at === null) {
            $this->addError('unbanReason', 'There is no pending reinstatement petition to approve.');

            return;
        }

        if ($this->currentReinstatementPetitionWasDenied($ban)) {
            $this->addError('unbanReason', 'This reinstatement petition has already been denied.');

            return;
        }

        $enforcement = DB::transaction(function () use ($ban): MembershipEnforcement {
            $ban->update([
                'status' => 'resolved',
                'resolved_at' => now(),
                'resolved_by' => Auth::id(),
            ]);

            $this->user->membership_status = 'active';
            $this->user->save();

            $this->removeBanIdentifiers();

            $enforcement = MembershipEnforcement::create([
                'user_id' => $this->user->id,
                'admin_id' => Auth::id(),
                'action' => 'reinstated_good_standing',
                'status' => 'resolved',
                'reason' => $this->unbanReason,
                'starts_at' => now(),
                'resolved_at' => now(),
                'resolved_by' => Auth::id(),
            ]);

            StaffActivity::record(
                action: 'membership_reinstated_good_standing',
                description: 'Exceptionally reinstated '.$this->user->name.' directly to active IRDI membership.',
                targetUserId: $this->user->id,
                subject: $enforcement,
                metadata: [
                    'ban_id' => $ban->id,
                    'reason' => $this->unbanReason,
                ],
            );
            return $enforcement;
        });

        $this->user->notify(
            new MembershipReinstatedNotification(
                reason: $enforcement->reason,
            )
        );

        $this->unbanReason = '';
        $this->loadUser();

        session()->flash(
            'status',
            'The member has been exceptionally reinstated directly to active IRDI membership and their ban identifiers have been removed.'
        );
    }

    private function blockOrdinaryDisciplineDuringAdministrativeReview(string $field): bool
    {
        if (! $this->user->isUnderAdministrativeReview()) {
            return false;
        }

        $this->addError(
            $field,
            'This member is under Administrative Review. Complete that review using one of its documented outcomes.'
        );

        return true;
    }

    private function resolveActiveProbationsForEscalation(
        string $outcome,
        string $outcomeLabel,
        ?string $reason = null,
    ): ?MembershipEnforcement {
        $activeProbations = MembershipEnforcement::query()
            ->where('user_id', $this->user->id)
            ->where('action', 'probation')
            ->where('status', 'active')
            ->whereNull('resolved_at')
            ->get();

        foreach ($activeProbations as $probation) {
            $probation->update([
                'status' => 'resolved',
                'resolved_at' => now(),
                'resolved_by' => Auth::id(),
            ]);

            StaffActivity::record(
                action: 'membership_probation_escalated',
                description: 'Closed membership probation for '.$this->user->name.' due to '.$outcomeLabel.'.',
                targetUserId: $this->user->id,
                subject: $probation,
                metadata: [
                    'probation_id' => $probation->id,
                    'original_reason' => $probation->reason,
                    'outcome' => $outcome,
                    'reason' => $reason,
                ],
            );
        }
        return $activeProbations->first();
    }

    private function ensureAdministrator(): void
    {
        if (! auth()->user()?->isAdministrator()) {
            abort(403);
        }
    }

    private function staffRank(User $user): int
    {
        return match (true) {
            $user->is_super_admin => 3,
            $user->is_admin => 2,
            $user->is_moderator => 1,
            default => 0,
        };
    }

    private function ensureCanEnforceTarget(): void
    {
        $actor = auth()->user();

        if ($actor === null || $this->staffRank($actor) <= $this->staffRank($this->user)) {
            abort(403);
        }
    }

    private function ensureCanManageModeratorTarget(): void
    {
        $this->ensureAdministrator();

        if ($this->user->is_super_admin || $this->user->is_admin) {
            $this->addError(
                'staffRole',
                'Administrator and Super Administrator access can only be managed by the Super Administrator.'
            );

            abort(403);
        }
    }

    private function ensureCanManageAdministratorTarget(): void
    {
        $actor = auth()->user();

        if ($actor === null || ! $actor->is_super_admin || $this->user->is_super_admin) {
            abort(403);
        }
    }

    public function makeModerator(): void
    {
        $this->ensureCanManageModeratorTarget();

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

        DB::transaction(function (): void {
            $this->user->is_moderator = true;
            $this->user->save();

            StaffActivity::record(
                action: 'moderator_assigned',
                description: 'Assigned Moderator access to '.$this->user->name.'.',
                targetUserId: $this->user->id,
                subject: $this->user,
                metadata: [
                    'is_moderator' => true,
                ],
            );
        });

        $this->loadUser();

        session()->flash('status', 'Member has been made a Moderator.');
    }



    public function removeModerator(): void
    {
        $this->ensureCanManageModeratorTarget();

        if (! $this->user->is_moderator) {
            return;
        }

        DB::transaction(function (): void {
            $this->user->is_moderator = false;
            $this->user->save();

            StaffActivity::record(
                action: 'moderator_removed',
                description: 'Removed Moderator access from '.$this->user->name.'.',
                targetUserId: $this->user->id,
                subject: $this->user,
                metadata: [
                    'is_moderator' => false,
                ],
            );
        });

        $this->loadUser();

        session()->flash('status', 'Moderator access has been removed.');
    }

    public function makeAdministrator(): void
    {
        $this->ensureCanManageAdministratorTarget();

        if ($this->user->is_admin) {
            return;
        }

        DB::transaction(function (): void {
            $wasModerator = (bool) $this->user->is_moderator;

            $this->user->is_admin = true;
            $this->user->is_moderator = false;
            $this->user->save();

            StaffActivity::record(
                action: 'administrator_assigned',
                description: 'Assigned Administrator access to '.$this->user->name.'.',
                targetUserId: $this->user->id,
                subject: $this->user,
                metadata: [
                    'is_admin' => true,
                    'previously_moderator' => $wasModerator,
                    'is_moderator' => false,
                ],
            );
        });

        $this->loadUser();

        session()->flash('status', 'Member has been made an Administrator.');
    }

    public function removeAdministrator(): void
    {
        $this->ensureCanManageAdministratorTarget();

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

        DB::transaction(function (): void {
            $this->user->is_admin = false;
            $this->user->save();

            StaffActivity::record(
                action: 'administrator_removed',
                description: 'Removed Administrator access from '.$this->user->name.'.',
                targetUserId: $this->user->id,
                subject: $this->user,
                metadata: [
                    'is_admin' => false,
                ],
            );
        });

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
            'membershipEnforcements.resolver',
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
            <div role="status" aria-live="polite" aria-atomic="true" class="mb-6 rounded-xl border border-green-200 bg-green-50 px-5 py-4 text-sm font-medium text-green-800">
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
                @elseif ($user->membership_status === 'banned')

                    @php
                        $activeBan = $user->membershipEnforcements
                            ->where('action', 'banned')
                            ->where('status', 'active')
                            ->whereNull('resolved_at')
                            ->sortByDesc('id')
                            ->first();

                        $petitionDenied = $activeBan?->review_requested_at
                            ? $user->membershipEnforcements
                                ->where('action', 'reinstatement_denied')
                                ->filter(fn ($enforcement) => $enforcement->created_at->gte($activeBan->review_requested_at))
                                ->isNotEmpty()
                            : false;
                    @endphp

                    <div class="mt-6 rounded-lg border border-red-300 bg-red-50 p-5">
                        <div class="font-medium text-red-900">
                            This member is banned from IRDI.
                        </div>

                        <p class="mt-1 text-sm leading-6 text-red-700">
                            The account and enforcement history are preserved. Reinstatement is a Super Administrator decision and is not automatic.
                        </p>

                        @if ($activeBan)
                            <div class="mt-4 grid gap-3 text-sm text-red-800 sm:grid-cols-2">
                                <div>
                                    <span class="font-medium">Ban began:</span>
                                    {{ ($activeBan->starts_at ?? $activeBan->created_at)->format('F j, Y') }}
                                </div>

                                <div>
                                    <span class="font-medium">Petition eligibility:</span>
                                    @if ($activeBan->permanently_ineligible)
                                        Permanently ineligible
                                    @elseif ($activeBan->reinstatement_eligible_at)
                                        {{ $activeBan->reinstatement_eligible_at->format('F j, Y') }}
                                    @else
                                        Not established
                                    @endif
                                </div>
                            </div>

                            @if ($activeBan->review_requested_at)
                                <div class="mt-4 rounded-lg border {{ $petitionDenied ? 'border-zinc-300 bg-zinc-100' : 'border-blue-200 bg-blue-50' }} p-4 text-sm {{ $petitionDenied ? 'text-zinc-800' : 'text-blue-900' }}">
                                    <div class="font-semibold">
                                        {{ $petitionDenied ? 'Reinstatement petition denied' : 'Reinstatement petition pending' }}
                                    </div>
                                    <div class="mt-1">
                                        Requested {{ $activeBan->review_requested_at->format('F j, Y \a\t g:i A') }}.
                                    </div>

                                    @if ($activeBan->member_response)
                                        <div class="mt-3 whitespace-pre-line border-t border-blue-200 pt-3 text-blue-800">
                                            {{ $activeBan->member_response }}
                                        </div>
                                    @endif
                                </div>
                            @endif
                        @else
                            <div class="mt-4 rounded-lg border border-amber-200 bg-amber-50 p-4 text-sm text-amber-900">
                                No active ban enforcement record was found. Reinstatement controls are unavailable until the enforcement record is corrected.
                            </div>
                        @endif
                    </div>

                    @if ($canUnbanMember && $activeBan && $activeBan->review_requested_at && ! $petitionDenied)
                        <div class="mt-6 grid gap-6 xl:grid-cols-3">
                            <div class="rounded-xl border border-zinc-200 p-5">
                                <h3 class="font-semibold text-zinc-900">Deny Petition</h3>
                                <p class="mt-1 text-sm leading-6 text-zinc-600">
                                    Keep the ban active and record the Super Administrator's decision.
                                </p>

                                <div class="mt-4">
                                    <flux:textarea
                                        wire:model="reinstatementDenialReason"
                                        label="Reason for denial"
                                        rows="4"
                                    />
                                </div>

                                <div class="mt-4 flex justify-end">
                                    <flux:button
                                        type="button"
                                        variant="outline"
                                        wire:click="denyReinstatement"
                                        wire:confirm="Deny this reinstatement petition? The IRDI ban will remain active."
                                    >
                                        Deny Petition
                                    </flux:button>
                                </div>
                            </div>

                            @if (! $activeBan->permanently_ineligible)
                                <div class="rounded-xl border border-orange-200 bg-orange-50 p-5">
                                    <h3 class="font-semibold text-orange-900">Reinstate on Probation</h3>
                                    <p class="mt-1 text-sm leading-6 text-orange-800">
                                        This is the normal reinstatement path. The member returns to active membership but is not in Good Standing while probation remains active.
                                    </p>

                                    <div class="mt-4 space-y-4">
                                        <flux:textarea
                                            wire:model="reinstatementProbationReason"
                                            label="Reason for reinstatement on probation"
                                            rows="3"
                                        />

                                        <flux:textarea
                                            wire:model="reinstatementProbationRequirements"
                                            label="Probation requirements"
                                            rows="3"
                                        />

                                        <flux:select
                                            wire:model="reinstatementProbationReviewDays"
                                            label="Probation review eligibility"
                                        >
                                            <option value="90">After 90 days</option>
                                            <option value="180">After 180 days</option>
                                        </flux:select>
                                    </div>

                                    <div class="mt-4 flex justify-end">
                                        <flux:button
                                            type="button"
                                            variant="primary"
                                            wire:click="reinstateWithProbation"
                                            wire:confirm="Reinstate this member to IRDI on membership probation?"
                                        >
                                            Reinstate on Probation
                                        </flux:button>
                                    </div>
                                </div>

                                <div class="rounded-xl border border-green-200 bg-green-50 p-5">
                                    <h3 class="font-semibold text-green-900">Exceptional Direct Reinstatement</h3>
                                    <p class="mt-1 text-sm leading-6 text-green-800">
                                        Use only when the record supports bypassing probation and returning the member directly to active membership.
                                    </p>

                                    <div class="mt-4">
                                        <flux:textarea
                                            wire:model="unbanReason"
                                            label="Reason for exceptional direct reinstatement"
                                            rows="4"
                                        />
                                    </div>

                                    <div class="mt-4 flex justify-end">
                                        <flux:button
                                            type="button"
                                            variant="primary"
                                            wire:click="reinstateToGoodStanding"
                                            wire:confirm="Exceptionally reinstate this member directly to active IRDI membership?"
                                        >
                                            Reinstate Directly
                                        </flux:button>
                                    </div>
                                </div>
                            @endif
                        </div>
                    @elseif ($canUnbanMember && $activeBan && ! $activeBan->review_requested_at)
                        <div class="mt-6 rounded-lg border border-zinc-200 bg-zinc-50 p-5 text-sm text-zinc-700">
                            No reinstatement petition is currently pending. Reinstatement outcome controls become available after an eligible member submits a petition.
                        </div>
                    @endif

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
                            @if ($user->is_super_admin)
                                <flux:badge color="amber">
                                    Super Administrator
                                </flux:badge>
                            @elseif ($user->is_admin)
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
                                rel="noopener noreferrer"
                            >
                                View Public Profile
                                <span class="sr-only">({{ __('common.opens_in_new_tab') }})</span>
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

        @if (auth()->user()->isAdministrator())

            {{-- Staff Management --}}
            <div class="mb-8 rounded-xl border border-zinc-200 bg-white p-6 shadow-sm">

                <div>
                    <h2 class="text-lg font-semibold text-zinc-900">
                        Staff Management
                    </h2>

                    <p class="mt-1 text-sm text-zinc-500">
                        Administrators can manage Moderators. Only the Super Administrator can manage Administrator access.
                    </p>
                </div>

                @error('staffRole')
                <div role="alert" class="mt-5 rounded-lg border border-red-200 bg-red-50 p-4 text-sm text-red-800">
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
                            and investigate or recommend membership enforcement. Formal membership discipline
                            is reserved for Administrators and the Super Administrator.
                        </p>

                        <div class="mt-5">
                            @if ($user->is_super_admin)
                                <p class="text-sm text-zinc-500">
                                    Super Administrator access is protected.
                                </p>
                            @elseif ($user->is_admin)
                                <p class="text-sm text-zinc-500">
                                    Administrator access can only be managed by the Super Administrator.
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

                    @if (auth()->user()->is_super_admin)
                        <div class="rounded-xl border border-zinc-200 bg-zinc-50 p-5">
                            <h3 class="font-semibold text-zinc-900">
                                Administrator
                            </h3>

                            <p class="mt-2 text-sm leading-6 text-zinc-600">
                                Only the Super Administrator can assign or remove Administrator access.
                            </p>

                            <div class="mt-5">
                                @if ($user->is_super_admin)
                                    <p class="text-sm text-zinc-500">
                                        Super Administrator access is protected.
                                    </p>
                                @elseif ($user->is_admin)
                                    <flux:button
                                        type="button"
                                        variant="danger"
                                        wire:click="removeAdministrator"
                                        wire:confirm="Remove Administrator access from this member?"
                                    >
                                        Remove Administrator
                                    </flux:button>
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
                    @endif

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

        @php
            $membershipEnforcementActor = auth()->user();

            $hasActiveAdministrativeReview = $user->isUnderAdministrativeReview();

            $canIssueWarning = $membershipEnforcementActor !== null
                && MembershipEnforcementAuthorization::canIssueWarning(
                    $membershipEnforcementActor,
                    $user
                );

            $canManageProbation = $membershipEnforcementActor !== null
&& MembershipEnforcementAuthorization::canManageProbation(
$membershipEnforcementActor,
$user
);

$canManageAdministrativeHold = $membershipEnforcementActor !== null
    && MembershipEnforcementAuthorization::canManageAdministrativeHold(
        $membershipEnforcementActor,
        $user
    );

$canSuspendMembership = $membershipEnforcementActor !== null
                && MembershipEnforcementAuthorization::canSuspend(
                    $membershipEnforcementActor,
                    $user
                );

            $canRestoreMembership = $membershipEnforcementActor !== null
                && MembershipEnforcementAuthorization::canRestoreSuspension(
                    $membershipEnforcementActor,
                    $user
                );

            $canBanMember = $membershipEnforcementActor !== null
                && MembershipEnforcementAuthorization::canBan(
                    $membershipEnforcementActor,
                    $user
                );

            $canUnbanMember = $membershipEnforcementActor !== null
                && MembershipEnforcementAuthorization::canUnban(
                    $membershipEnforcementActor,
                    $user
                );
        @endphp

        {{-- Membership Enforcement --}}
        <div class="mb-8 rounded-xl border border-zinc-200 bg-white p-6 shadow-sm">

            <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">

                <div>
                    <h2 class="text-lg font-semibold text-zinc-900">
                        Membership Enforcement
                    </h2>

                    <p class="mt-1 text-sm text-zinc-500">
                        Administrative membership discipline and restoration controls for this member.
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

                        @php
                            $activeBan = $user->membershipEnforcements
                                ->where('action', 'banned')
                                ->where('status', 'active')
                                ->whereNull('resolved_at')
                                ->sortByDesc('id')
                                ->first();

                            $petitionDenied = $activeBan?->review_requested_at
                                ? $user->membershipEnforcements
                                    ->where('action', 'reinstatement_denied')
                                    ->filter(fn ($enforcement) => $enforcement->created_at->gte($activeBan->review_requested_at))
                                    ->isNotEmpty()
                                : false;
                        @endphp

                        <div class="mt-6 rounded-lg border border-red-300 bg-red-50 p-5">
                            <div class="font-medium text-red-900">
                                This member is banned from IRDI.
                            </div>

                            <p class="mt-1 text-sm leading-6 text-red-700">
                                The account and enforcement history are preserved. Reinstatement is a Super Administrator decision and is not automatic.
                            </p>

                            @if ($activeBan)
                                <div class="mt-4 grid gap-3 text-sm text-red-800 sm:grid-cols-2">
                                    <div>
                                        <span class="font-medium">Ban began:</span>
                                        {{ ($activeBan->starts_at ?? $activeBan->created_at)->format('F j, Y') }}
                                    </div>

                                    <div>
                                        <span class="font-medium">Petition eligibility:</span>
                                        @if ($activeBan->permanently_ineligible)
                                            Permanently ineligible
                                        @elseif ($activeBan->reinstatement_eligible_at)
                                            {{ $activeBan->reinstatement_eligible_at->format('F j, Y') }}
                                        @else
                                            Not established
                                        @endif
                                    </div>
                                </div>

                                @if ($activeBan->review_requested_at)
                                    <div class="mt-4 rounded-lg border {{ $petitionDenied ? 'border-zinc-300 bg-zinc-100' : 'border-blue-200 bg-blue-50' }} p-4 text-sm {{ $petitionDenied ? 'text-zinc-800' : 'text-blue-900' }}">
                                        <div class="font-semibold">
                                            {{ $petitionDenied ? 'Reinstatement petition denied' : 'Reinstatement petition pending' }}
                                        </div>

                                        <div class="mt-1">
                                            Requested {{ $activeBan->review_requested_at->format('F j, Y \a\t g:i A') }}.
                                        </div>

                                        @if ($activeBan->member_response)
                                            <div class="mt-3 whitespace-pre-line border-t pt-3">
                                                {{ $activeBan->member_response }}
                                            </div>
                                        @endif
                                    </div>
                                @endif
                            @else
                                <div class="mt-4 rounded-lg border border-amber-200 bg-amber-50 p-4 text-sm text-amber-900">
                                    No active ban enforcement record was found. Reinstatement controls are unavailable until the enforcement record is corrected.
                                </div>
                            @endif
                        </div>

                        @if ($canUnbanMember && $activeBan && $activeBan->review_requested_at && ! $petitionDenied)
                            <div class="mt-6 grid gap-6 xl:grid-cols-3">
                                <div class="rounded-xl border border-zinc-200 p-5">
                                    <h3 class="font-semibold text-zinc-900">Deny Petition</h3>
                                    <p class="mt-1 text-sm leading-6 text-zinc-600">
                                        Keep the ban active and record the Super Administrator's decision.
                                    </p>

                                    <div class="mt-4">
                                        <flux:textarea
                                            wire:model="reinstatementDenialReason"
                                            label="Reason for denial"
                                            rows="4"
                                        />
                                    </div>

                                    <div class="mt-4 flex justify-end">
                                        <flux:button
                                            type="button"
                                            variant="outline"
                                            wire:click="denyReinstatement"
                                            wire:confirm="Deny this reinstatement petition? The IRDI ban will remain active."
                                        >
                                            Deny Petition
                                        </flux:button>
                                    </div>
                                </div>

                                @if (! $activeBan->permanently_ineligible)
                                    <div class="rounded-xl border border-orange-200 bg-orange-50 p-5">
                                        <h3 class="font-semibold text-orange-900">Reinstate on Probation</h3>

                                        <p class="mt-1 text-sm leading-6 text-orange-800">
                                            This is the normal reinstatement path. The member returns to active membership but is not in Good Standing while probation remains active.
                                        </p>

                                        <div class="mt-4 space-y-4">
                                            <flux:textarea
                                                wire:model="reinstatementProbationReason"
                                                label="Reason for reinstatement on probation"
                                                rows="3"
                                            />

                                            <flux:textarea
                                                wire:model="reinstatementProbationRequirements"
                                                label="Probation requirements"
                                                rows="3"
                                            />

                                            <flux:select
                                                wire:model="reinstatementProbationReviewDays"
                                                label="Probation review eligibility"
                                            >
                                                <option value="90">After 90 days</option>
                                                <option value="180">After 180 days</option>
                                            </flux:select>
                                        </div>

                                        <div class="mt-4 flex justify-end">
                                            <flux:button
                                                type="button"
                                                variant="primary"
                                                wire:click="reinstateWithProbation"
                                                wire:confirm="Reinstate this member to IRDI on membership probation?"
                                            >
                                                Reinstate on Probation
                                            </flux:button>
                                        </div>
                                    </div>

                                    <div class="rounded-xl border border-green-200 bg-green-50 p-5">
                                        <h3 class="font-semibold text-green-900">Exceptional Direct Reinstatement</h3>

                                        <p class="mt-1 text-sm leading-6 text-green-800">
                                            Use only when the record supports bypassing probation and returning the member directly to active membership.
                                        </p>

                                        <div class="mt-4">
                                            <flux:textarea
                                                wire:model="unbanReason"
                                                label="Reason for exceptional direct reinstatement"
                                                rows="4"
                                            />
                                        </div>

                                        <div class="mt-4 flex justify-end">
                                            <flux:button
                                                type="button"
                                                variant="primary"
                                                wire:click="reinstateToGoodStanding"
                                                wire:confirm="Exceptionally reinstate this member directly to active IRDI membership?"
                                            >
                                                Reinstate Directly
                                            </flux:button>
                                        </div>
                                    </div>
                                @endif
                            </div>
                        @elseif ($canUnbanMember && $activeBan && ! $activeBan->review_requested_at)
                            <div class="mt-6 rounded-lg border border-zinc-200 bg-zinc-50 p-5 text-sm text-zinc-700">
                                No reinstatement petition is currently pending. Reinstatement outcome controls become available after an eligible member submits a petition.
                            </div>
                        @endif

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

            @if ($user->membership_status === 'active')
                <div class="mt-6 rounded-xl border border-amber-200 bg-amber-50 p-5">
                    <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
                        <div>
                            <h3 class="font-semibold text-amber-900">
                                Formal Warning
                            </h3>

                            <p class="mt-1 text-sm leading-6 text-amber-800">
                                A formal warning documents a corrective or educational concern. It does not remove
                                the member from Good Standing and does not restrict membership privileges.
                            </p>
                        </div>

                        @if ($user->hasActiveMembershipWarning())
                            <flux:badge color="amber">
                                Active Warning
                            </flux:badge>
                        @endif
                    </div>

                    @php
                        $activeWarnings = $user->membershipEnforcements
                            ->where('action', 'warning')
                            ->where('status', 'active')
                            ->whereNull('resolved_at')
                            ->sortByDesc('created_at');
                    @endphp

                    @if ($activeWarnings->isNotEmpty())
                        <div class="mt-5 space-y-4">
                            @foreach ($activeWarnings as $warning)
                                <div wire:key="active-warning-{{ $warning->id }}" class="rounded-lg border border-amber-200 bg-white p-4">
                                    <div class="font-medium text-zinc-900">
                                        {{ $warning->reason }}
                                    </div>

                                    <div class="mt-1 text-sm text-zinc-500">
                                        Issued {{ $warning->created_at->format('F j, Y \a\t g:i A') }}
                                        @if ($warning->admin)
                                            · {{ $warning->admin->name }}
                                        @endif
                                    </div>

                                    @if ($warning->details)
                                        <div class="mt-3 text-sm leading-6 text-zinc-700">
                                            {{ $warning->details }}
                                        </div>
                                    @endif

                                    @if ($warning->restoration_requirements)
                                        <div class="mt-3 rounded-lg bg-zinc-50 p-3 text-sm text-zinc-700">
                                            <span class="font-medium">Corrective requirements:</span>
                                            {{ $warning->restoration_requirements }}
                                        </div>
                                    @endif

                                    @if ($canIssueWarning)
                                        <div class="mt-4 flex flex-wrap justify-end gap-3">
                                            <flux:button
                                                type="button"
                                                variant="outline"
                                                wire:click="withdrawWarning({{ $warning->id }})"
                                                wire:confirm="Withdraw this formal warning? The warning will remain in internal history as withdrawn."
                                            >
                                                Withdraw Warning
                                            </flux:button>

                                            <flux:button
                                                type="button"
                                                variant="primary"
                                                wire:click="resolveWarning({{ $warning->id }})"
                                                wire:confirm="Mark this formal warning as resolved?"
                                            >
                                                Resolve Warning
                                            </flux:button>
                                        </div>
                                    @endif
                                </div>
                            @endforeach
                        </div>
                    @endif

                    @if ($canIssueWarning && ! $hasActiveAdministrativeReview)
                        <div class="mt-6 border-t border-amber-200 pt-6">
                            <div class="grid gap-5">
                                <flux:textarea
                                    wire:model="warningReason"
                                    label="Reason for formal warning"
                                    placeholder="Describe the concern that requires a formal warning..."
                                    rows="3"
                                />

                                <flux:textarea
                                    wire:model="warningDetails"
                                    label="Additional details (optional)"
                                    placeholder="Add relevant context or internal details..."
                                    rows="3"
                                />

                                <flux:textarea
                                    wire:model="warningRequirements"
                                    label="Corrective action or training requirements (optional)"
                                    placeholder="Describe any corrective action, education, or training expected from the member..."
                                    rows="3"
                                />
                            </div>

                            <div class="mt-4 flex justify-end">
                                <flux:button
                                    type="button"
                                    variant="primary"
                                    wire:click="issueWarning"
                                    wire:confirm="Issue this formal membership warning? The member will be notified by email."
                                >
                                    Issue Formal Warning
                                </flux:button>
                            </div>
                        </div>
                    @endif
                </div>
            @endif


            @php
                $activeProbations = $user->membershipEnforcements
                    ->where('action', 'probation')
                    ->where('status', 'active')
                    ->whereNull('resolved_at')
                    ->filter(fn ($probation) => $probation->starts_at === null || $probation->starts_at->isPast())
                    ->sortByDesc('created_at');
            @endphp

            <div class="mt-6 rounded-xl border border-orange-200 bg-orange-50 p-5">
                <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
                    <div>
                        <h3 class="font-semibold text-orange-900">Membership Probation</h3>
                        <p class="mt-1 text-sm leading-6 text-orange-800">
                            Probation keeps the membership active but removes Member in Good Standing status until
                            an Administrator or Super Administrator completes a formal review and restores the member.
                        </p>
                    </div>
                    @if ($user->isOnProbation())
                        <flux:badge color="amber">Probationary Standing</flux:badge>
                    @endif
                </div>

                @if ($activeProbations->isNotEmpty())
                    <div class="mt-5 space-y-4">
                        @foreach ($activeProbations as $probation)
                            <div wire:key="active-probation-{{ $probation->id }}" class="rounded-lg border border-orange-200 bg-white p-4">
                                <div class="font-medium text-zinc-900">{{ $probation->reason }}</div>
                                <div class="mt-1 text-sm text-zinc-500">
                                    Started {{ ($probation->starts_at ?? $probation->created_at)->format('F j, Y \a\t g:i A') }}
                                    @if ($probation->admin) · {{ $probation->admin->name }} @endif
                                </div>

                                @if ($probation->eligible_for_review_at)
                                    <div class="mt-3 text-sm text-zinc-700">
                                        <span class="font-medium">Eligible for review:</span>
                                        {{ $probation->eligible_for_review_at->format('F j, Y') }}
                                    </div>
                                @endif

                                @if ($probation->review_requested_at)
                                    <div class="mt-3 rounded-lg border border-blue-200 bg-blue-50 p-3 text-sm text-blue-900">
                                        <div class="font-medium">Formal review requested by member</div>
                                        <div class="mt-1 text-blue-800">
                                            Requested {{ $probation->review_requested_at->format('F j, Y \a\t g:i A') }}
                                        </div>
                                    </div>
                                @elseif ($probation->eligible_for_review_at && now()->gte($probation->eligible_for_review_at))
                                    <div class="mt-3 rounded-lg border border-amber-200 bg-amber-50 p-3 text-sm text-amber-900">
                                        This probation is eligible for formal review.
                                    </div>
                                @endif

                                @if ($probation->member_response)
                                    <div class="mt-3 rounded-lg border border-zinc-200 p-3 text-sm text-zinc-700">
                                        <div class="font-medium text-zinc-900">Member response</div>
                                        <div class="mt-1 whitespace-pre-line">{{ $probation->member_response }}</div>
                                        @if ($probation->member_responded_at)
                                            <div class="mt-2 text-xs text-zinc-500">
                                                Submitted {{ $probation->member_responded_at->format('F j, Y \a\t g:i A') }}
                                            </div>
                                        @endif
                                    </div>
                                @endif

                                @if ($probation->details)
                                    <div class="mt-3 text-sm leading-6 text-zinc-700">{{ $probation->details }}</div>
                                @endif

                                <div class="mt-3 rounded-lg bg-zinc-50 p-3 text-sm text-zinc-700">
                                    <span class="font-medium">Requirements for restoration:</span>
                                    {{ $probation->restoration_requirements }}
                                </div>

                                @if ($canManageProbation)
                                    <div class="mt-5 border-t border-zinc-200 pt-5">
                                        <div class="grid gap-5 lg:grid-cols-2">
                                            <div class="rounded-lg border border-zinc-200 bg-zinc-50 p-4">
                                                <h4 class="font-medium text-zinc-900">Continue Probation</h4>
                                                <p class="mt-1 text-sm leading-6 text-zinc-600">
                                                    Keep probation active and schedule the next formal review for 90 days from today.
                                                </p>
                                                <div class="mt-4">
                                                    <flux:textarea wire:model="probationContinueReason" label="Reason for continuing probation" placeholder="Document why probation should continue..." rows="3" />
                                                </div>
                                                <div class="mt-4 flex justify-end">
                                                    <flux:button type="button" variant="outline" wire:click="continueProbation({{ $probation->id }})" wire:confirm="Continue this probation and set the next formal review for 90 days from today?">
                                                        Continue Probation
                                                    </flux:button>
                                                </div>
                                            </div>

                                            <div class="rounded-lg border border-green-200 bg-green-50 p-4">
                                                <h4 class="font-medium text-green-900">Restore Good Standing</h4>
                                                <p class="mt-1 text-sm leading-6 text-green-800">
                                                    End probation after determining that the member has satisfied the requirements for restoration.
                                                </p>
                                                <div class="mt-4">
                                                    <flux:textarea wire:model="probationRestoreReason" label="Reason for restoring Good Standing" placeholder="Document why the member is ready to return to Good Standing..." rows="3" />
                                                </div>
                                                <div class="mt-4 flex justify-end">
                                                    <flux:button type="button" variant="primary" wire:click="restoreFromProbation({{ $probation->id }})" wire:confirm="Resolve this probation and restore the member to Good Standing?">
                                                        Restore Good Standing
                                                    </flux:button>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                @endif
                            </div>
                        @endforeach
                    </div>
                @elseif ($canManageProbation && ! $hasActiveAdministrativeReview)
                    <div class="mt-6 border-t border-orange-200 pt-6">
                        <div class="grid gap-5">
                            <flux:textarea wire:model="probationReason" label="Reason for probation" placeholder="Describe the concern requiring membership probation..." rows="3" />
                            <flux:textarea wire:model="probationDetails" label="Additional details (optional)" placeholder="Add relevant context or internal details..." rows="3" />
                            <flux:textarea wire:model="probationRequirements" label="Requirements for restoring Good Standing" placeholder="Describe the corrective action, education, training, or other requirements the member must satisfy..." rows="3" />
                            <flux:select wire:model="probationReviewDays" label="Eligible for formal review">
                                <option value="90">After 90 days</option>
                                <option value="180">After 180 days</option>
                            </flux:select>
                        </div>
                        <div class="mt-4 flex justify-end">
                            <flux:button type="button" variant="danger" wire:click="placeOnProbation" wire:confirm="Place this member on probation? Their membership remains active, but they will no longer be a Member in Good Standing.">
                                Place on Probation
                            </flux:button>
                        </div>
                    </div>
                @endif
            </div>

            {{-- Administrative Review --}}
            @php
                $activeAdministrativeHolds = $user->membershipEnforcements
                    ->where('action', 'administrative_hold')
                    ->where('status', 'active')
                    ->whereNull('resolved_at')
                    ->filter(fn ($hold) => $hold->starts_at === null || $hold->starts_at->isPast())
                    ->sortByDesc('created_at');
            @endphp

            @if ($user->membership_status === 'active')
                <div class="mt-6 rounded-xl border border-blue-200 bg-blue-50 p-5">
                    <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
                        <div>
                            <h3 class="font-semibold text-blue-900">
                                Administrative Review
                            </h3>

                            <p class="mt-1 text-sm leading-6 text-blue-800">
                                An Administrative Hold is a temporary, non-disciplinary status used while
                                IRDI investigates a serious concern. The membership remains active, but the
                                member is not considered a Member in Good Standing while the review is open.
                            </p>
                        </div>

                        @if ($user->isUnderAdministrativeReview())
                            <flux:badge color="blue">
                                Under Administrative Review
                            </flux:badge>
                        @endif
                    </div>

                    @if ($activeAdministrativeHolds->isNotEmpty())
                        <div class="mt-5 space-y-4">
                            @foreach ($activeAdministrativeHolds as $hold)
                                <div
                                    wire:key="active-administrative-hold-{{ $hold->id }}"
                                    class="rounded-lg border border-blue-200 bg-white p-4"
                                >
                                    <div class="font-medium text-zinc-900">
                                        {{ $hold->reason }}
                                    </div>

                                    <div class="mt-1 text-sm text-zinc-500">
                                        Started
                                        {{ ($hold->starts_at ?? $hold->created_at)->format('F j, Y \a\t g:i A') }}

                                        @if ($hold->admin)
                                            · {{ $hold->admin->name }}
                                        @endif
                                    </div>

                                    @if ($hold->details)
                                        <div class="mt-3 text-sm leading-6 text-zinc-700">
                                            {{ $hold->details }}
                                        </div>
                                    @endif

                                    @if ($canManageAdministrativeHold)
                                        <div class="mt-5 border-t border-zinc-200 pt-5">
                                            <div class="grid gap-5 lg:grid-cols-2 xl:grid-cols-3">

                                                <div class="rounded-lg border border-green-200 bg-green-50 p-4">
                                                    <h4 class="font-medium text-green-900">
                                                        Close Review — No Action
                                                    </h4>

                                                    <p class="mt-1 text-sm leading-6 text-green-800">
                                                        Close this investigation without disciplinary action.
                                                        The member will return to Member in Good Standing status
                                                        if no other active enforcement removes that status.
                                                    </p>

                                                    <div class="mt-4">
                                                        <flux:textarea
                                                            wire:model="administrativeHoldResolutionReason"
                                                            label="Reason for closing review"
                                                            placeholder="Document why the investigation is being closed with no further action..."
                                                            rows="3"
                                                        />
                                                    </div>

                                                    <div class="mt-4 flex justify-end">
                                                        <flux:button
                                                            type="button"
                                                            variant="primary"
                                                            wire:click="resolveAdministrativeHoldNoAction({{ $hold->id }})"
                                                            wire:confirm="Close this administrative review with no further action?"
                                                        >
                                                            Close Review — No Action
                                                        </flux:button>
                                                    </div>
                                                </div>

                                                @if ($canIssueWarning)
                                                    <div class="rounded-lg border border-amber-200 bg-amber-50 p-4">
                                                        <h4 class="font-medium text-amber-900">
                                                            Formal Warning
                                                        </h4>

                                                        <p class="mt-1 text-sm leading-6 text-amber-800">
                                                            Close the administrative review and issue a formal corrective warning.
                                                            The member remains active and returns to Member in Good Standing unless
                                                            another active enforcement removes that status.
                                                        </p>

                                                        <div class="mt-4 grid gap-4">
                                                            <flux:textarea
                                                                wire:model="warningReason"
                                                                label="Reason for formal warning"
                                                                placeholder="Describe the concern that warrants a formal warning..."
                                                                rows="3"
                                                            />

                                                            <flux:textarea
                                                                wire:model="warningDetails"
                                                                label="Additional details (optional)"
                                                                placeholder="Add relevant context or internal details..."
                                                                rows="3"
                                                            />

                                                            <flux:textarea
                                                                wire:model="warningRequirements"
                                                                label="Corrective action or training requirements (optional)"
                                                                placeholder="Describe any corrective action, education, or training expected from the member..."
                                                                rows="3"
                                                            />
                                                        </div>

                                                        <div class="mt-4 flex justify-end">
                                                            <flux:button
                                                                type="button"
                                                                variant="primary"
                                                                wire:click="resolveAdministrativeHoldWithWarning({{ $hold->id }})"
                                                                wire:confirm="Close this administrative review and issue a formal membership warning?"
                                                            >
                                                                Close Review with Warning
                                                            </flux:button>
                                                        </div>
                                                    </div>
                                                @endif

                                                @if ($canManageProbation)
                                                    <div class="rounded-lg border border-orange-200 bg-orange-50 p-4">
                                                        <h4 class="font-medium text-orange-900">
                                                            Membership Probation
                                                        </h4>

                                                        <p class="mt-1 text-sm leading-6 text-orange-800">
                                                            Close the administrative review and place the member on probation.
                                                            Their membership remains active, but they will not be a Member in Good
                                                            Standing while probation is active.
                                                        </p>

                                                        <div class="mt-4 grid gap-4">
                                                            <flux:textarea
                                                                wire:model="probationReason"
                                                                label="Reason for probation"
                                                                placeholder="Describe the concern requiring membership probation..."
                                                                rows="3"
                                                            />

                                                            <flux:textarea
                                                                wire:model="probationDetails"
                                                                label="Additional details (optional)"
                                                                placeholder="Add relevant context or internal details..."
                                                                rows="3"
                                                            />

                                                            <flux:textarea
                                                                wire:model="probationRequirements"
                                                                label="Requirements for restoring Good Standing"
                                                                placeholder="Describe the corrective action, education, training, or other requirements the member must satisfy..."
                                                                rows="3"
                                                            />

                                                            <flux:select
                                                                wire:model="probationReviewDays"
                                                                label="Eligible for formal review"
                                                            >
                                                                <option value="90">After 90 days</option>
                                                                <option value="180">After 180 days</option>
                                                            </flux:select>
                                                        </div>

                                                        <div class="mt-4 flex justify-end">
                                                            <flux:button
                                                                type="button"
                                                                variant="danger"
                                                                wire:click="resolveAdministrativeHoldWithProbation({{ $hold->id }})"
                                                                wire:confirm="Close this administrative review and place the member on probation? Their membership remains active, but they will no longer be a Member in Good Standing."
                                                            >
                                                                Close Review with Probation
                                                            </flux:button>
                                                        </div>
                                                    </div>
                                                @endif


                                                @if ($canSuspendMembership)
                                                    <div class="rounded-lg border border-red-200 bg-red-50 p-4">
                                                        <h4 class="font-medium text-red-900">
                                                            Membership Suspension
                                                        </h4>

                                                        <p class="mt-1 text-sm leading-6 text-red-800">
                                                            Close the administrative review and suspend the membership.
                                                            The member will lose active membership privileges until a later formal restoration.
                                                        </p>

                                                        <div class="mt-4 grid gap-4">
                                                            <flux:textarea
                                                                wire:model="suspensionReason"
                                                                label="Reason for suspension"
                                                                placeholder="Explain why the completed investigation warrants suspension..."
                                                                rows="3"
                                                            />

                                                            <flux:select
                                                                wire:model="suspensionReviewPeriod"
                                                                label="Formal review"
                                                            >
                                                                <option value="30">After 30 days</option>
                                                                <option value="90">After 90 days</option>
                                                                <option value="180">After 180 days</option>
                                                                <option value="indefinite">Indefinite — no review date</option>
                                                            </flux:select>
                                                        </div>

                                                        <div class="mt-4 flex justify-end">
                                                            <flux:button
                                                                type="button"
                                                                variant="danger"
                                                                wire:click="resolveAdministrativeHoldWithSuspension({{ $hold->id }})"
                                                                wire:confirm="Close this administrative review and suspend the member's IRDI membership?"
                                                            >
                                                                Close Review with Suspension
                                                            </flux:button>
                                                        </div>
                                                    </div>
                                                @endif

                                                @if ($canBanMember)
                                                    <div class="rounded-lg border border-red-300 bg-red-50 p-4">
                                                        <h4 class="font-medium text-red-950">
                                                            Ban from IRDI
                                                        </h4>

                                                        <p class="mt-1 text-sm leading-6 text-red-800">
                                                            Close the administrative review with the strongest membership action.
                                                            The account and history are preserved, while the member's email address and any
                                                            known non-loopback IP addresses are added to the ban list.
                                                        </p>

                                                        <div class="mt-4">
                                                            <flux:textarea
                                                                wire:model="banReason"
                                                                label="Reason for ban"
                                                                placeholder="Explain why the completed investigation warrants an IRDI ban..."
                                                                rows="3"
                                                            />
                                                        </div>

                                                        <div class="mt-4 flex justify-end">
                                                            <flux:button
                                                                type="button"
                                                                variant="danger"
                                                                wire:click="resolveAdministrativeHoldWithBan({{ $hold->id }})"
                                                                wire:confirm="Close this administrative review and ban this member from IRDI? Their email address and any known non-loopback IP addresses will be added to the ban list."
                                                            >
                                                                Close Review with Ban
                                                            </flux:button>
                                                        </div>
                                                    </div>
                                                @endif

                                            </div>
                                        </div>
                                    @endif
                                </div>
                            @endforeach
                        </div>

                    @elseif ($canManageAdministrativeHold)

                        <div class="mt-6 border-t border-blue-200 pt-6">
                            <div class="grid gap-5">
                                <flux:textarea
                                    wire:model="administrativeHoldReason"
                                    label="Reason for administrative review"
                                    placeholder="Describe the serious concern requiring investigation..."
                                    rows="3"
                                />

                                <flux:textarea
                                    wire:model="administrativeHoldDetails"
                                    label="Investigation details (optional)"
                                    placeholder="Add relevant internal context or information for staff..."
                                    rows="3"
                                />
                            </div>

                            <div class="mt-4 flex justify-end">
                                <flux:button
                                    type="button"
                                    variant="danger"
                                    wire:click="placeOnAdministrativeHold"
                                    wire:confirm="Place this member under administrative review? Their membership remains active, but they will temporarily lose Member in Good Standing status."
                                >
                                    Place Under Administrative Review
                                </flux:button>
                            </div>
                        </div>
                    @endif
                </div>
            @endif

            @if ($user->membership_status === 'suspended')
                @php
                    $activeSuspension = $user->membershipEnforcements
                        ->where('action', 'suspended')
                        ->where('status', 'active')
                        ->whereNull('resolved_at')
                        ->sortByDesc('created_at')
                        ->first();
                @endphp

                <div class="mt-6 rounded-xl border border-red-200 bg-red-50 p-5">
                    <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
                        <div>
                            <h3 class="font-semibold text-red-900">
                                Active Membership Suspension
                            </h3>

                            <p class="mt-1 text-sm leading-6 text-red-800">
                                This membership remains suspended until an Administrator or Super Administrator
                                completes a formal review. Reaching the review date does not automatically restore membership.
                            </p>
                        </div>

                        <flux:badge color="red">
                            Suspended
                        </flux:badge>
                    </div>

                    @if ($activeSuspension)
                        <div class="mt-5 rounded-lg border border-red-200 bg-white p-4">
                            <div class="font-medium text-zinc-900">
                                {{ $activeSuspension->reason }}
                            </div>

                            <div class="mt-1 text-sm text-zinc-500">
                                Started
                                {{ ($activeSuspension->starts_at ?? $activeSuspension->created_at)->format('F j, Y \a\t g:i A') }}

                                @if ($activeSuspension->admin)
                                    · {{ $activeSuspension->admin->name }}
                                @endif
                            </div>

                            <div class="mt-3 text-sm text-zinc-700">
                                <span class="font-medium">Formal review:</span>

                                @if ($activeSuspension->eligible_for_review_at)
                                    {{ $activeSuspension->eligible_for_review_at->format('F j, Y') }}
                                @else
                                    Indefinite — no review date
                                @endif
                            </div>

                            @if ($activeSuspension->review_requested_at)
                                <div class="mt-3 rounded-lg border border-blue-200 bg-blue-50 p-3 text-sm text-blue-900">
                                    <div class="font-medium">Formal review requested by member</div>
                                    <div class="mt-1 text-blue-800">
                                        Requested {{ $activeSuspension->review_requested_at->format('F j, Y \a\t g:i A') }}
                                    </div>
                                </div>
                            @elseif ($activeSuspension->eligible_for_review_at && now()->gte($activeSuspension->eligible_for_review_at))
                                <div class="mt-3 rounded-lg border border-amber-200 bg-amber-50 p-3 text-sm text-amber-900">
                                    This suspension is eligible for formal review.
                                </div>
                            @endif

                            @if ($activeSuspension->member_response)
                                <div class="mt-3 rounded-lg border border-zinc-200 p-3 text-sm text-zinc-700">
                                    <div class="font-medium text-zinc-900">Member response</div>
                                    <div class="mt-1 whitespace-pre-line">{{ $activeSuspension->member_response }}</div>

                                    @if ($activeSuspension->member_responded_at)
                                        <div class="mt-2 text-xs text-zinc-500">
                                            Submitted {{ $activeSuspension->member_responded_at->format('F j, Y \a\t g:i A') }}
                                        </div>
                                    @endif
                                </div>
                            @endif
                        </div>
                    @endif
                </div>

                @if ($activeSuspension && ($canRestoreMembership || $canManageProbation || $canBanMember))
                    <div class="mt-6 border-t border-zinc-200 pt-6">
                        <div class="mb-5">
                            <h3 class="font-semibold text-zinc-900">
                                Suspension Review Outcomes
                            </h3>

                            <p class="mt-1 text-sm leading-6 text-zinc-600">
                                Choose one documented outcome. Suspension never ends automatically.
                            </p>
                        </div>

                        <div class="grid gap-5 lg:grid-cols-2">
                            @if ($canRestoreMembership)
                                <div class="rounded-xl border border-green-200 bg-green-50 p-5">
                                    <h4 class="font-semibold text-green-900">
                                        Restore to Good Standing
                                    </h4>

                                    <p class="mt-1 text-sm leading-6 text-green-800">
                                        End the suspension and restore active membership. The member returns to Good Standing
                                        if no other active enforcement removes that status.
                                    </p>

                                    <div class="mt-4">
                                        <flux:textarea
                                            wire:model="membershipRestoreReason"
                                            label="Reason for restoration"
                                            placeholder="Document why the member is ready to return to active membership..."
                                            rows="3"
                                        />
                                    </div>

                                    <div class="mt-4 flex justify-end">
                                        <flux:button
                                            type="button"
                                            variant="primary"
                                            wire:click="restoreMembership"
                                            wire:confirm="Resolve this suspension and restore the member's active IRDI membership?"
                                        >
                                            Restore to Good Standing
                                        </flux:button>
                                    </div>
                                </div>

                                <div class="rounded-xl border border-zinc-200 bg-zinc-50 p-5">
                                    <h4 class="font-semibold text-zinc-900">
                                        Continue Suspension
                                    </h4>

                                    <p class="mt-1 text-sm leading-6 text-zinc-600">
                                        Keep the current suspension active and establish a new formal review period.
                                    </p>

                                    <div class="mt-4 grid gap-4">
                                        <flux:textarea
                                            wire:model="suspensionContinueReason"
                                            label="Reason for continuing suspension"
                                            placeholder="Document why the suspension should continue..."
                                            rows="3"
                                        />

                                        <flux:select
                                            wire:model="suspensionContinuePeriod"
                                            label="Next formal review"
                                        >
                                            <option value="30">After 30 days</option>
                                            <option value="90">After 90 days</option>
                                            <option value="180">After 180 days</option>
                                            <option value="indefinite">Indefinite — no review date</option>
                                        </flux:select>
                                    </div>

                                    <div class="mt-4 flex justify-end">
                                        <flux:button
                                            type="button"
                                            variant="outline"
                                            wire:click="continueSuspension({{ $activeSuspension->id }})"
                                            wire:confirm="Continue this suspension and replace the current formal review date?"
                                        >
                                            Continue Suspension
                                        </flux:button>
                                    </div>
                                </div>
                            @endif

                            @if ($canManageProbation)
                                <div class="rounded-xl border border-orange-200 bg-orange-50 p-5">
                                    <h4 class="font-semibold text-orange-900">
                                        Move to Probation
                                    </h4>

                                    <p class="mt-1 text-sm leading-6 text-orange-800">
                                        End the suspension, reactivate membership, and place the member on probation.
                                        The member will not be in Good Standing while probation remains active.
                                    </p>

                                    <div class="mt-4 grid gap-4">
                                        <flux:textarea
                                            wire:model="probationReason"
                                            label="Reason for probation"
                                            placeholder="Document why probation is the appropriate outcome..."
                                            rows="3"
                                        />

                                        <flux:textarea
                                            wire:model="probationDetails"
                                            label="Additional details (optional)"
                                            placeholder="Add relevant internal context..."
                                            rows="3"
                                        />

                                        <flux:textarea
                                            wire:model="probationRequirements"
                                            label="Requirements for restoring Good Standing"
                                            placeholder="Describe the corrective action, education, training, or other requirements..."
                                            rows="3"
                                        />

                                        <flux:select
                                            wire:model="probationReviewDays"
                                            label="Eligible for formal review"
                                        >
                                            <option value="90">After 90 days</option>
                                            <option value="180">After 180 days</option>
                                        </flux:select>
                                    </div>

                                    <div class="mt-4 flex justify-end">
                                        <flux:button
                                            type="button"
                                            variant="danger"
                                            wire:click="resolveSuspensionWithProbation({{ $activeSuspension->id }})"
                                            wire:confirm="Resolve this suspension and place the member on probation?"
                                        >
                                            Move to Probation
                                        </flux:button>
                                    </div>
                                </div>
                            @endif

                            @if ($canBanMember)
                                <div class="rounded-xl border border-red-300 bg-red-50 p-5">
                                    <h4 class="font-semibold text-red-950">
                                        Ban from IRDI
                                    </h4>

                                    <p class="mt-1 text-sm leading-6 text-red-800">
                                        End the suspension with the strongest membership action. Only the Super Administrator
                                        can ban a member. Ban identifiers and the complete enforcement history are preserved.
                                    </p>

                                    <div class="mt-4 grid gap-4">
                                        <flux:textarea
                                            wire:model="banReason"
                                            label="Reason for ban"
                                            placeholder="Document why the suspension review warrants an IRDI ban..."
                                            rows="3"
                                        />

                                        <div class="rounded-lg border border-red-200 bg-white p-4">
                                            <flux:switch
                                                wire:model="banPermanentlyIneligible"
                                                label="Permanently ineligible for reinstatement"
                                                description="Use only when this ban should never be eligible for a future reinstatement petition. Otherwise, petition eligibility begins after three years."
                                            />
                                        </div>
                                    </div>

                                    <div class="mt-4 flex justify-end">
                                        <flux:button
                                            type="button"
                                            variant="danger"
                                            wire:click="resolveSuspensionWithBan({{ $activeSuspension->id }})"
                                            wire:confirm="Resolve this suspension with an IRDI ban? The member's email address and any known non-loopback IP addresses will be added to the ban list."
                                        >
                                            Ban Member
                                        </flux:button>
                                    </div>
                                </div>
                            @endif
                        </div>
                    </div>
                @elseif (! $activeSuspension)
                    <div class="mt-6 rounded-lg border border-amber-200 bg-amber-50 p-5 text-sm text-amber-900">
                        This account is marked suspended, but no active suspension enforcement record was found.
                        Review the enforcement history before taking further action.
                    </div>
                @endif

            @elseif ($user->membership_status === 'active')

                @if ($hasActiveAdministrativeReview)

                    <div class="mt-6 rounded-lg border border-blue-200 bg-blue-50 p-5">
                        <div class="font-medium text-blue-900">
                            Administrative Review is currently active.
                        </div>

                        <p class="mt-1 text-sm leading-6 text-blue-800">
                            Ordinary Warning, Probation, Suspension, and Ban controls are unavailable while this review is open.
                            Complete the Administrative Review using one of the documented outcomes above.
                        </p>
                    </div>

                @elseif (! $canSuspendMembership)

                    <div class="mt-6 rounded-lg border border-zinc-200 bg-zinc-50 p-5">
                        <div class="font-medium text-zinc-900">
                            Membership enforcement is not available for this account.
                        </div>

                        <p class="mt-1 text-sm text-zinc-600">
                            Your staff role does not have authority to suspend this member.
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

                        <div class="grid gap-5">
                            <flux:textarea
                                wire:model="suspensionReason"
                                label="Reason for suspending membership"
                                placeholder="Explain why this member's IRDI membership is being suspended..."
                                rows="4"
                            />

                            <flux:select
                                wire:model="suspensionReviewPeriod"
                                label="Formal review"
                            >
                                <option value="30">After 30 days</option>
                                <option value="90">After 90 days</option>
                                <option value="180">After 180 days</option>
                                <option value="indefinite">Indefinite — no review date</option>
                            </flux:select>
                        </div>

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

                @if ($canBanMember && ! $hasActiveAdministrativeReview)
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

                            <div class="mt-4 rounded-lg border border-red-200 bg-white p-4">
                                <flux:switch
                                    wire:model="banPermanentlyIneligible"
                                    label="Permanently ineligible for reinstatement"
                                    description="Use only when this ban should never be eligible for a future reinstatement petition. Otherwise, petition eligibility begins after three years."
                                />
                            </div>
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
                            <caption class="sr-only">Message reports for this member</caption>

                            <thead class="bg-zinc-50">
                            <tr>
                                <th scope="col" class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wide text-zinc-600">
                                    Report
                                </th>

                                <th scope="col" class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wide text-zinc-600">
                                    Reporter
                                </th>

                                <th scope="col" class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wide text-zinc-600">
                                    Status
                                </th>

                                <th scope="col" class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wide text-zinc-600">
                                    Date
                                </th>

                                <th scope="col" class="px-5 py-3 text-right text-xs font-semibold uppercase tracking-wide text-zinc-600">
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
                                        @if ($enforcement->action === 'warning')
                                            Formal Membership Warning
                                        @elseif ($enforcement->action === 'probation')
                                            Membership Probation
                                        @elseif ($enforcement->action === 'administrative_hold')
                                            Administrative Review
                                        @else
                                            Membership {{ ucfirst($enforcement->action) }}
                                        @endif
                                    </div>

                                    <div class="mt-1 text-sm text-zinc-500">
                                        {{ $enforcement->created_at->format('F j, Y \a\t g:i A') }}

                                        @if ($enforcement->admin)
                                            · {{ $enforcement->admin->name }}
                                        @endif
                                    </div>
                                </div>

                                @if ($enforcement->action === 'warning')
                                    @if ($enforcement->status === 'active')
                                        <flux:badge color="amber">Active Warning</flux:badge>
                                    @elseif ($enforcement->status === 'resolved')
                                        <flux:badge color="green">Resolved Warning</flux:badge>
                                    @elseif ($enforcement->status === 'withdrawn')
                                        <flux:badge color="zinc">Withdrawn Warning</flux:badge>
                                    @endif
                                @elseif ($enforcement->action === 'probation')
                                    @if ($enforcement->status === 'active')
                                        <flux:badge color="amber">Probationary Standing</flux:badge>
                                    @elseif ($enforcement->status === 'resolved')
                                        <flux:badge color="green">Probation Resolved</flux:badge>
                                    @endif
                                @elseif ($enforcement->action === 'administrative_hold')
                                    @if ($enforcement->status === 'active')
                                        <flux:badge color="blue">Under Administrative Review</flux:badge>
                                    @elseif ($enforcement->status === 'resolved')
                                        <flux:badge color="green">Review Closed</flux:badge>
                                    @endif
                                @elseif ($enforcement->action === 'suspended')
                                    @if ($enforcement->status === 'active')
                                        <flux:badge color="red">Suspended</flux:badge>
                                    @elseif ($enforcement->status === 'resolved')
                                        <flux:badge color="green">Suspension Resolved</flux:badge>
                                    @else
                                        <flux:badge color="zinc">{{ ucfirst($enforcement->status) }}</flux:badge>
                                    @endif
                                @elseif ($enforcement->action === 'restored')
                                    <flux:badge color="green">
                                        Restored
                                    </flux:badge>
                                @elseif ($enforcement->action === 'banned')
                                    <flux:badge color="red">
                                        Banned
                                    </flux:badge>
                                @elseif (in_array($enforcement->action, ['unbanned', 'reinstated_good_standing'], true))
                                    <flux:badge color="green">
                                        Reinstated to Good Standing
                                    </flux:badge>
                                @endif

                            </div>

                            <div class="mt-4 text-sm text-zinc-700">
                                {{ $enforcement->reason }}
                            </div>

                            @if ($enforcement->action === 'warning' && $enforcement->details)
                                <div class="mt-3 text-sm text-zinc-600">
                                    {{ $enforcement->details }}
                                </div>
                            @endif

                            @if ($enforcement->action === 'warning' && $enforcement->restoration_requirements)
                                <div class="mt-3 rounded-lg bg-zinc-50 p-3 text-sm text-zinc-700">
                                    <span class="font-medium">Corrective requirements:</span>
                                    {{ $enforcement->restoration_requirements }}
                                </div>
                            @endif

                            @if ($enforcement->action === 'warning' && $enforcement->member_response)
                                <div class="mt-3 rounded-lg border border-zinc-200 p-3 text-sm text-zinc-700">
                                    <div class="font-medium text-zinc-900">Member response</div>
                                    <div class="mt-1">{{ $enforcement->member_response }}</div>
                                </div>
                            @endif

                            @if ($enforcement->action === 'warning' && $enforcement->resolved_at)
                                <div class="mt-3 text-xs text-zinc-500">
                                    {{ ucfirst($enforcement->status) }} {{ $enforcement->resolved_at->format('F j, Y \a\t g:i A') }}
                                    @if ($enforcement->resolver)
                                        · {{ $enforcement->resolver->name }}
                                    @endif
                                </div>
                            @endif

                            @if ($enforcement->action === 'probation')
                                @if ($enforcement->details)
                                    <div class="mt-3 text-sm text-zinc-600">{{ $enforcement->details }}</div>
                                @endif

                                @if ($enforcement->eligible_for_review_at)
                                    <div class="mt-3 text-sm text-zinc-700">
                                        <span class="font-medium">Eligible for review:</span>
                                        {{ $enforcement->eligible_for_review_at->format('F j, Y') }}
                                    </div>
                                @endif

                                @if ($enforcement->review_requested_at)
                                    <div class="mt-3 text-sm text-zinc-700">
                                        <span class="font-medium">Formal review requested:</span>
                                        {{ $enforcement->review_requested_at->format('F j, Y \a\t g:i A') }}
                                    </div>
                                @endif

                                @if ($enforcement->restoration_requirements)
                                    <div class="mt-3 rounded-lg bg-zinc-50 p-3 text-sm text-zinc-700">
                                        <span class="font-medium">Requirements for restoration:</span>
                                        {{ $enforcement->restoration_requirements }}
                                    </div>
                                @endif

                                @if ($enforcement->member_response)
                                    <div class="mt-3 rounded-lg border border-zinc-200 p-3 text-sm text-zinc-700">
                                        <div class="font-medium text-zinc-900">Member response</div>
                                        <div class="mt-1">{{ $enforcement->member_response }}</div>
                                    </div>
                                @endif

                                @if ($enforcement->resolved_at)
                                    <div class="mt-3 text-xs text-zinc-500">
                                        Resolved {{ $enforcement->resolved_at->format('F j, Y \a\t g:i A') }}
                                        @if ($enforcement->resolver) · {{ $enforcement->resolver->name }} @endif
                                    </div>
                                @endif
                            @endif

                            @if ($enforcement->action === 'suspended')
                                <div class="mt-3 text-sm text-zinc-700">
                                    <span class="font-medium">Formal review:</span>
                                    @if ($enforcement->eligible_for_review_at)
                                        {{ $enforcement->eligible_for_review_at->format('F j, Y') }}
                                    @else
                                        Indefinite — no review date
                                    @endif
                                </div>

                                @if ($enforcement->review_requested_at)
                                    <div class="mt-3 text-sm text-zinc-700">
                                        <span class="font-medium">Formal review requested:</span>
                                        {{ $enforcement->review_requested_at->format('F j, Y \a\t g:i A') }}
                                    </div>
                                @endif

                                @if ($enforcement->member_response)
                                    <div class="mt-3 rounded-lg border border-zinc-200 p-3 text-sm text-zinc-700">
                                        <div class="font-medium text-zinc-900">Member response</div>
                                        <div class="mt-1">{{ $enforcement->member_response }}</div>
                                    </div>
                                @endif

                                @if ($enforcement->resolved_at)
                                    <div class="mt-3 text-xs text-zinc-500">
                                        Suspension resolved {{ $enforcement->resolved_at->format('F j, Y \a\t g:i A') }}
                                        @if ($enforcement->resolver) · {{ $enforcement->resolver->name }} @endif
                                    </div>
                                @endif
                            @endif

                            @if ($enforcement->action === 'administrative_hold')
                                @if ($enforcement->details)
                                    <div class="mt-3 text-sm text-zinc-600">
                                        {{ $enforcement->details }}
                                    </div>
                                @endif

                                @if ($enforcement->resolved_at)
                                    <div class="mt-3 text-xs text-zinc-500">
                                        Review closed
                                        {{ $enforcement->resolved_at->format('F j, Y \a\t g:i A') }}

                                        @if ($enforcement->resolver)
                                            · {{ $enforcement->resolver->name }}
                                        @endif
                                    </div>
                                @endif
                            @endif

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
