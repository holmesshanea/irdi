<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BanIdentifier extends Model
{
    protected $fillable = [
        'user_id',
        'membership_enforcement_id',
        'created_by',
        'type',
        'value',
        'reason',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function membershipEnforcement(): BelongsTo
    {
        return $this->belongsTo(MembershipEnforcement::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
