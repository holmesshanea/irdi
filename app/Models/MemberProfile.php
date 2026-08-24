<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class MemberProfile extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'profile_type',
        'username',
        'profile_name',
        'city',
        'state_province',
        'country',
        'bio',
        'website',
        'profile_image',
        'directory_visible',
    ];

    public function propertyReviewInvitations(): HasMany
    {
        return $this->hasMany(PropertyReviewInvitation::class);
    }

    public function propertyReviews(): HasMany
    {
        return $this->hasMany(PropertyReview::class);
    }

    protected function casts(): array
    {
        return [
            'directory_visible' => 'boolean',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function scopePublicDirectory($query)
    {
        return $query
            ->where('directory_visible', true)
            ->whereHas('user', function ($query) {
                $query->where('membership_status', 'active');
            });
    }
}
