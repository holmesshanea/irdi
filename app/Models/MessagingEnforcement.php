<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MessagingEnforcement extends Model
{
    protected $fillable = [
        'user_id',
        'admin_id',
        'message_report_id',
        'action',
        'reason',
        'expires_at',
    ];

    protected function casts(): array
    {
        return [
            'expires_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function admin(): BelongsTo
    {
        return $this->belongsTo(User::class, 'admin_id');
    }

    public function messageReport(): BelongsTo
    {
        return $this->belongsTo(MessageReport::class);
    }
}
