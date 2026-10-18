<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

class LandownerPermission extends Model
{
    protected $guarded = ['id', 'status', 'access_token_hash', 'responded_at', 'withdrawn_at'];

    protected function casts(): array
    {
        return [
            'starts_on' => 'date', 'ends_on' => 'date', 'responded_at' => 'datetime',
            'withdrawn_at' => 'datetime', 'last_reminded_at' => 'datetime',
            'response_link_expires_at' => 'datetime',
        ];
    }

    /** @return BelongsTo<MemberProfile, $this> */
    public function memberProfile(): BelongsTo
    {
        return $this->belongsTo(MemberProfile::class);
    }

    public function isResponseLinkExpired(): bool
    {
        return $this->response_link_expires_at !== null
            && Carbon::parse((string) $this->response_link_expires_at)->isPast();
    }

    public function isRequestDateExpired(): bool
    {
        return Carbon::parse((string) $this->ends_on)->startOfDay()->isBefore(today());
    }

    public function effectiveStatus(): string
    {
        if ($this->withdrawn_at) {
            return 'withdrawn';
        }
        if ($this->status === 'approved' && $this->isRequestDateExpired()) {
            return 'expired';
        }

        return $this->status;
    }
}
