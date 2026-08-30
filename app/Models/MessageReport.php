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

    /**
     * @return BelongsTo<Message, $this>
     */
    public function message(): BelongsTo
    {
        return $this->belongsTo(Message::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function reporter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reporter_id');
    }

    /**
     * @return HasMany<MessageReportModeration, $this>
     */
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
