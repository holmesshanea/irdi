<?php

namespace App\Models;

use Database\Factories\MemberProfileFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class MemberProfile extends Model
{
    /** @use HasFactory<MemberProfileFactory> */
    use HasFactory;

    protected $fillable = [
        'user_id',
        'username',
        'profile_name',
        'city',
        'state_province',
        'country',
        'country_code',
        'bio',
        'website',
        'profile_image',
        'directory_visible',
        'allow_member_messages',
    ];

    /**
     * @return HasMany<PropertyReviewInvitation, $this>
     */
    public function propertyReviewInvitations(): HasMany
    {
        return $this->hasMany(PropertyReviewInvitation::class);
    }

    /**
     * @return HasMany<PropertyReview, $this>
     */
    public function propertyReviews(): HasMany
    {
        return $this->hasMany(PropertyReview::class);
    }

    protected function casts(): array
    {
        return [
            'directory_visible' => 'boolean',
            'allow_member_messages' => 'boolean',
        ];
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @param  Builder<MemberProfile>  $query
     * @return Builder<MemberProfile>
     */
    public function scopePublicDirectory(Builder $query): Builder
    {
        return $query
            ->where('directory_visible', true)
            ->whereHas('user', function (Builder $query): void {
                $query->where('membership_status', 'active');
            });
    }
}
