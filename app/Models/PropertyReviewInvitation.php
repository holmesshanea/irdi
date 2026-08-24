<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Carbon;

/**
 * @property Carbon|null $verification_expires_at
 * @property Carbon|null $email_verified_at
 * @property Carbon|null $expires_at
 * @property Carbon|null $used_at
 * @property Carbon|null $cancelled_at
 */
class PropertyReviewInvitation extends Model
{
    protected $fillable = [
        'member_profile_id',
        'token',
        'reviewer_email',
        'verification_code_hash',
        'verification_expires_at',
        'email_verified_at',
        'expires_at',
        'used_at',
        'cancelled_at',
    ];

    protected function casts(): array
    {
        return [
            'verification_expires_at' => 'datetime',
            'email_verified_at' => 'datetime',
            'expires_at' => 'datetime',
            'used_at' => 'datetime',
            'cancelled_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<MemberProfile, $this>
     */
    public function memberProfile(): BelongsTo
    {
        return $this->belongsTo(MemberProfile::class);
    }

    /**
     * @return HasOne<PropertyReview, $this>
     */
    public function review(): HasOne
    {
        return $this->hasOne(
            PropertyReview::class,
            'property_review_invitation_id'
        );
    }

    public function isExpired(): bool
    {
        return $this->expires_at?->isPast() ?? true;
    }

    public function isCancelled(): bool
    {
        return $this->cancelled_at !== null;
    }

    public function isUsed(): bool
    {
        return $this->used_at !== null;
    }

    public function isAvailable(): bool
    {
        return ! $this->isExpired()
            && ! $this->isUsed()
            && ! $this->isCancelled();
    }
}
