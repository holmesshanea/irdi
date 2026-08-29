<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Message extends Model
{
    protected $fillable = [
        'sender_id',
        'recipient_id',
        'subject',
        'body',
        'read_at',
        'sender_deleted_at',
        'recipient_deleted_at',
    ];

    protected function casts(): array
    {
        return [
            'read_at' => 'datetime',
            'sender_deleted_at' => 'datetime',
            'recipient_deleted_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function sender(): BelongsTo
    {
        return $this->belongsTo(User::class, 'sender_id');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function recipient(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recipient_id');
    }

    public function isRead(): bool
    {
        return $this->read_at !== null;
    }

    public function markAsRead(): void
    {
        if ($this->read_at === null) {
            $this->update([
                'read_at' => now(),
            ]);
        }
    }

    /**
     * @return HasMany<MessageReport, $this>
     */
    public function reports(): HasMany
    {
        return $this->hasMany(MessageReport::class);
    }
}
