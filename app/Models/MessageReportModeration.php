<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MessageReportModeration extends Model
{
    protected $fillable = [
        'message_report_id',
        'user_id',
        'action',
    ];

    public function report(): BelongsTo
    {
        return $this->belongsTo(MessageReport::class, 'message_report_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
