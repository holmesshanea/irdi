<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

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
    ];

    protected function casts(): array
    {
        return [
            'verification_expires_at' => 'datetime',
            'email_verified_at' => 'datetime',
            'expires_at' => 'datetime',
            'used_at' => 'datetime',
        ];
    }

    public function memberProfile(): BelongsTo
    {
        return $this->belongsTo(MemberProfile::class);
    }

    public function isExpired(): bool
    {
        return $this->expires_at->isPast();
    }

    public function isUsed(): bool
    {
        return $this->used_at !== null;
    }

    public function isAvailable(): bool
    {
        return ! $this->isExpired() && ! $this->isUsed();
    }
}
