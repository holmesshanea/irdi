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
            ->subject('IRDI Reinstatement Petition Received')
            ->greeting('Hello '.$notifiable->name.',')
            ->line('IRDI has received your petition for reinstatement to membership.')
            ->line('Your petition is now pending review. Submission of a reinstatement petition does not itself restore your membership or end your current membership ban.')
            ->line('Your membership will remain banned unless and until IRDI approves reinstatement.')
            ->action('View Your Account', url('/account'))
            ->line('You will be notified when a decision is made regarding your reinstatement petition.');
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [];
    }
}
