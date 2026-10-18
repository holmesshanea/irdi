<?php

namespace App\Console\Commands;

use App\Mail\LandownerPermissionNotice;
use App\Models\LandownerPermission;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Mail;

class RemindExpiringLandownerPermissions extends Command
{
    protected $signature = 'permissions:remind-expiring';

    protected $description = 'Email members once when approved permission is approaching its end date';

    public function handle(): int
    {
        $sent = 0;
        LandownerPermission::query()->with('memberProfile.user')
            ->where('status', 'approved')->whereNull('withdrawn_at')
            ->whereNull('last_reminded_at')
            ->whereDate('ends_on', '>=', today())
            ->whereDate('ends_on', '<=', today()->addDays(3))
            ->chunkById(100, function ($permissions) use (&$sent): void {
                foreach ($permissions as $permission) {
                    if ($permission->effectiveStatus() !== 'approved') {
                        continue;
                    }
                    $email = $permission->memberProfile->user?->email;
                    if (! $email) {
                        continue;
                    }
                    try {
                        Mail::to($email)->send(new LandownerPermissionNotice(
                            'IRDI: Landowner permission ending soon',
                            'Your IRDI landowner permission record for '.$permission->property_label.' ends on '.Carbon::parse($permission->ends_on)->toDateString().'. Check your account for the full record. Do not detect after permission expires without renewed landowner consent.',
                        ));
                        $permission->last_reminded_at = now();
                        $permission->save();
                        $sent++;
                    } catch (\Throwable $exception) {
                        report($exception);
                        $this->error('Failed to send permission reminder for record #'.$permission->id);
                    }
                }
            });
        $this->info("Sent {$sent} permission expiration reminder(s).");

        return self::SUCCESS;
    }
}
