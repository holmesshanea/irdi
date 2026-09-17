<?php

namespace App\Notifications;

use App\Models\User;
use Carbon\CarbonInterface;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class MembershipProbationContinuedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public CarbonInterface $eligibleForReviewAt,
    ) {}

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
            ->subject(__('IRDI Membership Probation Continued'))
            ->greeting(__('Hello :name,', ['name' => $notifiable->name]))
            ->line(__('Your IRDI Membership Probation has been reviewed and will remain active.'))
            ->line(__('Your next eligibility date for formal review is :date.', [
                'date' => $this->eligibleForReviewAt->translatedFormat('F j, Y'),
            ]))
            ->line(__('Until the probation is resolved, your membership is not considered to be in Good Standing.'))
            ->action(__('View Your Account'), url('/account'))
            ->line(__('You will be notified when your probation is reviewed again or if further membership action is taken.'));
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [];
    }
}
