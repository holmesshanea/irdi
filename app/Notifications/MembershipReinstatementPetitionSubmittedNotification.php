<?php

namespace App\Notifications;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class MembershipReinstatementPetitionSubmittedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct() {}

    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        /** @var User $notifiable */

        return (new MailMessage)
            ->subject(__('IRDI Reinstatement Petition Received'))
            ->greeting(__('Hello :name,', ['name' => $notifiable->name]))
            ->line(__('IRDI has received your petition for reinstatement to membership.'))
            ->line(__('Your petition is now pending review. Submission of a reinstatement petition does not itself restore your membership or end your current membership ban.'))
            ->line(__('Your membership will remain banned unless and until IRDI approves reinstatement.'))
            ->action(__('View Your Account'), url('/account'))
            ->line(__('You will be notified when a decision is made regarding your reinstatement petition.'));
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [];
    }
}
