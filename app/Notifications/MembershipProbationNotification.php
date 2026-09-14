<?php

namespace App\Notifications;

use App\Models\User;
use Carbon\CarbonInterface;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class MembershipProbationNotification extends Notification
{
    use Queueable;

    public function __construct(
        public string $reason,
        public string $requirements,
        public ?CarbonInterface $eligibleForReviewAt = null,
    ) {}

    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    /**
     * @param  User  $notifiable
     */
    public function toMail(object $notifiable): MailMessage
    {
        $message = (new MailMessage)
            ->subject(__('IRDI Membership Probation'))
            ->greeting(__('Hello :name,', ['name' => $notifiable->name]))
            ->line(__('Your IRDI membership has been placed on probation.'))
            ->line(__('Reason: :reason', ['reason' => $this->reason]))
            ->line(__('Requirements for restoring Good Standing: :requirements', [
                'requirements' => $this->requirements,
            ]));

        if ($this->eligibleForReviewAt !== null) {
            $message->line(__('Your probation becomes eligible for formal review on :date.', [
                'date' => $this->eligibleForReviewAt->format('F j, Y'),
            ]));
        }

        return $message
            ->line(__('Your membership remains active while you are on probation, but you are not considered an IRDI Member in Good Standing until probation is formally resolved.'))
            ->action(__('View Your Account'), route('account'))
            ->line(__('If you have questions about this action, please contact IRDI.'));
    }
}
