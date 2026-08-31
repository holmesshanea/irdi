<?php

namespace App\Support;

use App\Models\StaffActivityLog;
use Illuminate\Database\Eloquent\Model;

class StaffActivity
{
    /**
     * @param  array<string, mixed>|null  $metadata
     */
    public static function record(
        string $action,
        string $description,
        ?int $targetUserId = null,
        ?Model $subject = null,
        ?array $metadata = null,
    ): StaffActivityLog {
        return StaffActivityLog::create([
            'actor_id' => auth()->id(),
            'target_user_id' => $targetUserId,
            'action' => $action,
            'subject_type' => $subject?->getMorphClass(),
            'subject_id' => $subject?->getKey(),
            'description' => $description,
            'metadata' => $metadata,
            'ip_address' => request()->ip(),
        ]);
    }
}
