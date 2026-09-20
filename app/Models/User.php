<?php

namespace App\Models;

use App\Notifications\ResetPasswordNotification;
use App\Notifications\VerifyEmailNotification;
use Database\Factories\UserFactory;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Contracts\Translation\HasLocalePreference;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

/**
 * @property int $id
 * @property string $name
 * @property string $email
 * @property Carbon|null $email_verified_at
 * @property string $password
 * @property string|null $remember_token
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property string $membership_status
 * @property string $locale
 * @property Carbon|null $member_since
 * @property Carbon|null $ethics_agreed_at
 * @property Carbon|null $best_practices_agreed_at
 */
#[Fillable([
    'name',
    'email',
    'registration_ip',
    'last_login_ip',
    'password',
    'membership_status',
    'member_since',
    'ethics_agreed_at',
    'best_practices_agreed_at',
    'email_member_message_notifications',
    'locale',
])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable implements HasLocalePreference, MustVerifyEmail
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'member_since' => 'date',
            'ethics_agreed_at' => 'datetime',
            'best_practices_agreed_at' => 'datetime',
            'password' => 'hashed',
            'is_admin' => 'boolean',
            'is_super_admin' => 'boolean',
            'is_moderator' => 'boolean',
            'is_charter_member' => 'boolean',
            'email_member_message_notifications' => 'boolean',
            'messaging_disabled_at' => 'datetime',
            'messaging_disabled_until' => 'datetime',
        ];
    }

    /**
     * Get the user's initials.
     */
    public function initials(): string
    {
        $initials = Str::initials($this->name, true);

        return Str::length($initials) > 1
            ? Str::substr($initials, 0, 1).Str::substr($initials, -1)
            : $initials;
    }

    public function preferredLocale(): string
    {
        $supported = array_keys((array) config('locales.supported', []));

        return in_array($this->locale, $supported, true)
            ? $this->locale
            : (string) config('locales.default', 'en');
    }

    public function isModerator(): bool
    {
        return (bool) $this->is_moderator;
    }

    public function isAdministrator(): bool
    {
        return (bool) $this->is_super_admin || (bool) $this->is_admin;
    }

    public function isModeratorOrAdministrator(): bool
    {
        return $this->isAdministrator() || $this->isModerator();
    }

    public function messagingIsDisabled(): bool
    {
        if ($this->messaging_disabled_at === null) {
            return false;
        }

        if ($this->messaging_disabled_until === null) {
            return true;
        }

        return now()->lessThan($this->messaging_disabled_until);
    }

    public function messagingRestrictionIsTemporary(): bool
    {
        return $this->messaging_disabled_at !== null
            && $this->messaging_disabled_until !== null
            && now()->lessThan($this->messaging_disabled_until);
    }

    /**
     * @return HasOne<MemberProfile, $this>
     */
    public function memberProfile(): HasOne
    {
        return $this->hasOne(MemberProfile::class);
    }

    public function sendPasswordResetNotification($token): void
    {
        $this->notify(new ResetPasswordNotification($token));
    }

    public function sendEmailVerificationNotification(): void
    {
        $this->notify(new VerifyEmailNotification);
    }

    /**
     * @return HasMany<Message, $this>
     */
    public function sentMessages(): HasMany
    {
        return $this->hasMany(Message::class, 'sender_id');
    }

    /**
     * @return HasMany<Message, $this>
     */
    public function receivedMessages(): HasMany
    {
        return $this->hasMany(Message::class, 'recipient_id');
    }

    /**
     * @return HasMany<MemberBlock, $this>
     */
    public function blockedMembers(): HasMany
    {
        return $this->hasMany(MemberBlock::class, 'blocker_id');
    }

    /**
     * @return HasMany<MemberBlock, $this>
     */
    public function blockedByMembers(): HasMany
    {
        return $this->hasMany(MemberBlock::class, 'blocked_id');
    }

    /**
     * Messaging enforcement actions applied to this member.
     *
     * @return HasMany<MessagingEnforcement, $this>
     */
    public function messagingEnforcements(): HasMany
    {
        return $this->hasMany(MessagingEnforcement::class);
    }

    /**
     * Messaging enforcement actions performed by this administrator.
     *
     * @return HasMany<MessagingEnforcement, $this>
     */
    public function administeredMessagingEnforcements(): HasMany
    {
        return $this->hasMany(MessagingEnforcement::class, 'admin_id');
    }

    /**
     * Membership enforcement actions applied to this member.
     *
     * @return HasMany<MembershipEnforcement, $this>
     */
    public function membershipEnforcements(): HasMany
    {
        return $this->hasMany(MembershipEnforcement::class);
    }

    /**
     * Determine whether the member has an active formal membership warning.
     */
    public function hasActiveMembershipWarning(): bool
    {
        return $this->hasActiveMembershipEnforcement('warning');
    }

    /**
     * Determine whether the member is currently on probation.
     */
    public function isOnProbation(): bool
    {
        return $this->hasActiveMembershipEnforcement('probation');
    }

    /**
     * Determine whether the member is currently under administrative review.
     */
    public function isUnderAdministrativeReview(): bool
    {
        return $this->hasActiveMembershipEnforcement('administrative_hold');
    }

    /**
     * Determine whether the member is currently in good standing.
     */
    public function isInGoodStanding(): bool
    {
        if ($this->membership_status !== 'active') {
            return false;
        }

        if ($this->ethics_agreed_at === null || $this->best_practices_agreed_at === null) {
            return false;
        }

        return ! $this->isOnProbation()
            && ! $this->isUnderAdministrativeReview();
    }

    /**
     * Determine whether the member has an active enforcement action of the given type.
     */
    private function hasActiveMembershipEnforcement(string $action): bool
    {
        return $this->membershipEnforcements()
            ->where('action', $action)
            ->where('status', 'active')
            ->whereNull('resolved_at')
            ->where(function ($query): void {
                $query
                    ->whereNull('starts_at')
                    ->orWhere('starts_at', '<=', now());
            })
            ->exists();
    }

    /**
     * Membership enforcement actions performed by this administrator.
     *
     * @return HasMany<MembershipEnforcement, $this>
     */
    public function administeredMembershipEnforcements(): HasMany
    {
        return $this->hasMany(MembershipEnforcement::class, 'admin_id');
    }
}
