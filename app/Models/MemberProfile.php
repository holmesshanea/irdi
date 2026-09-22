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
            'personal_information_hidden' => 'boolean',
        ];
    }

    public function publicName(): string
    {
        return $this->personal_information_hidden ? '@'.$this->username : $this->profile_name;
    }

    public function publicDetail(string $field): string
    {
        if (! in_array($field, ['city', 'state_province', 'country', 'country_code', 'bio', 'website', 'profile_image'], true)) {
            throw new \InvalidArgumentException('Unsupported public profile field.');
        }

        return $this->personal_information_hidden ? '' : (string) $this->getAttribute($field);
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
            ->where(function (Builder $query): void {
                $query->where('directory_visible', true)->orWhere('personal_information_hidden', true);
            })
            ->whereHas('user', function (Builder $query): void {
                $query->where('membership_status', 'active');
            });
    }
}
