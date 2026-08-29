<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;


class MessageReport extends Model
{
    protected $fillable = [
        'message_id',
        'reporter_id',
        'reason',
        'details',
        'status',
    ];

    public function message(): BelongsTo
    {
        return $this->belongsTo(Message::class);
    }

    public function reporter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reporter_id');
    }

    public function moderations(): HasMany
    {
        return $this->hasMany(MessageReportModeration::class);
    }

    /**
     * @return HasMany<MessagingEnforcement, $this>
     */
    public function messagingEnforcements(): HasMany
    {
        return $this->hasMany(MessagingEnforcement::class);
    }
}
