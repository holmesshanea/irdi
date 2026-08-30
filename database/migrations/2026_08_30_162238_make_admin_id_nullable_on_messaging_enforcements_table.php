<?php

namespace App\Console\Commands;

use App\Models\MessagingEnforcement;
use App\Models\User;
use App\Notifications\MessagingRestoredNotification;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Throwable;

class RestoreExpiredMessagingRestrictions extends Command
{
    protected $signature = 'messaging:restore-expired';

    protected $description = 'Restore messaging access for members whose temporary messaging restrictions have expired';

    public function handle(): int
    {
        $restoredCount = 0;
        $failedCount = 0;

        User::query()
            ->whereNotNull('messaging_disabled_at')
            ->whereNotNull('messaging_disabled_until')
            ->where('messaging_disabled_until', '<=', now())
            ->chunkById(100, function ($users) use (&$restoredCount, &$failedCount) {
                foreach ($users as $user) {
                    try {
                        DB::transaction(function () use ($user) {
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
                        });

                        $user->notify(
                            new MessagingRestoredNotification
                        );

                        $restoredCount++;
                    } catch (Throwable $exception) {
                        $failedCount++;

                        report($exception);

                        $this->error(
                            "Failed to restore messaging access for user ID {$user->id}."
                        );
                    }
                }
            });

        $this->info(
            "Restored messaging access for {$restoredCount} member(s)."
        );

        if ($failedCount > 0) {
            $this->warn(
                "Failed to restore messaging access for {$failedCount} member(s)."
            );
        }

        return $failedCount > 0
            ? self::FAILURE
            : self::SUCCESS;
    }
}
