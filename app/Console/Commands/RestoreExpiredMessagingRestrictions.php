<?php

namespace App\Console\Commands;

use App\Models\MessagingEnforcement;
use App\Models\User;
use App\Notifications\MessagingRestoredNotification;
use Illuminate\Console\Command;

class RestoreExpiredMessagingRestrictions extends Command
{
    protected $signature = 'messaging:restore-expired';

    protected $description = 'Restore messaging access for members whose temporary messaging restrictions have expired';

    public function handle(): int
    {
        $restoredCount = 0;

        User::query()
            ->whereNotNull('messaging_disabled_at')
            ->whereNotNull('messaging_disabled_until')
            ->where('messaging_disabled_until', '<=', now())
            ->chunkById(100, function ($users) use (&$restoredCount) {
                foreach ($users as $user) {
                    $expiredAt = $user->messaging_disabled_until;

                    $user->messaging_disabled_at = null;
                    $user->messaging_disabled_until = null;
                    $user->save();

                    MessagingEnforcement::create([
                        'user_id' => $user->id,
                        'admin_id' => null,
                        'message_report_id' => null,
                        'action' => 'restored',
                        'reason' => 'Messaging access automatically restored after the temporary restriction expired.',
                        'expires_at' => null,
                    ]);

                    $user->notify(
                        new MessagingRestoredNotification
                    );

                    $restoredCount++;
                }
            });

        $this->info(
            "Restored messaging access for {$restoredCount} member(s)."
        );

        return self::SUCCESS;
    }
}
