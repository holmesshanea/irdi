<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;


class PropertyReviewModeration extends Model
{
    protected $fillable = [
        'property_review_id',
        'user_id',
        'action',
        'note',
    ];

    public function propertyReview(): BelongsTo
    {
        return $this->belongsTo(PropertyReview::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
