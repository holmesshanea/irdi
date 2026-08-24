<?php

namespace App\Mail;

use App\Models\MemberProfile;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class PropertyReviewInvitationMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public MemberProfile $profile,
        public string $reviewUrl,
        public string $expiresAt,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: $this->profile->profile_name
            .' has invited you to provide IRDI Property Owner Feedback',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'mail.property-review-invitation',
            text: 'mail.property-review-invitation-text',
        );
    }
}
