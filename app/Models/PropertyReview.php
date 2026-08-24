<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PropertyReview extends Model
{
    protected $fillable = [
        'member_profile_id',
        'property_review_invitation_id',
        'reviewer_email',
        'respect_for_property',
        'communication_courtesy',
        'care_of_property',
        'would_allow_return',
        'comments',
    ];

    protected function casts(): array
    {
        return [
            'respect_for_property' => 'integer',
            'communication_courtesy' => 'integer',
            'care_of_property' => 'integer',
            'would_allow_return' => 'boolean',
            'hidden_at' => 'datetime',
        ];
    }

    public function memberProfile(): BelongsTo
    {
        return $this->belongsTo(MemberProfile::class);
    }

    public function invitation(): BelongsTo
    {
        return $this->belongsTo(
            PropertyReviewInvitation::class,
            'property_review_invitation_id'
        );
    }

    public function scopeVisible(Builder $query): Builder
    {
        return $query->whereNull('hidden_at');
    }

    public function moderations(): HasMany
    {
        return $this->hasMany(PropertyReviewModeration::class);
    }
}
