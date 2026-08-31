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

    /**
     * The user associated with this ban identifier.
     *
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * The membership enforcement action that created this ban identifier.
     *
     * @return BelongsTo<MembershipEnforcement, $this>
     */
    public function membershipEnforcement(): BelongsTo
    {
        return $this->belongsTo(MembershipEnforcement::class);
    }

    /**
     * The administrator or moderator who created this ban identifier.
     *
     * @return BelongsTo<User, $this>
     */
    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
