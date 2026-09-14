<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MembershipEnforcement extends Model
{
    protected $fillable = [
        'user_id',
        'admin_id',
        'action',
        'status',
        'reason',
        'details',
        'starts_at',
        'eligible_for_review_at',
        'restoration_requirements',
        'member_response',
        'member_responded_at',
        'resolved_at',
        'resolved_by',
        'reinstatement_eligible_at',
        'permanently_ineligible',
        'review_requested_at',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'starts_at' => 'datetime',
            'eligible_for_review_at' => 'datetime',
            'member_responded_at' => 'datetime',
            'resolved_at' => 'datetime',
            'reinstatement_eligible_at' => 'datetime',
            'permanently_ineligible' => 'boolean',
            'review_requested_at' => 'datetime',
        ];
    }

    /**
     * The member affected by this enforcement action.
     *
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * The administrator who performed this enforcement action.
     *
     * @return BelongsTo<User, $this>
     */
    public function admin(): BelongsTo
    {
        return $this->belongsTo(User::class, 'admin_id');
    }

    /**
     * The administrator who resolved this enforcement action.
     *
     * @return BelongsTo<User, $this>
     */
    public function resolver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'resolved_by');
    }
}
