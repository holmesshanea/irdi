<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class PropertyReviewVerificationCodeMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public string $code,
    ) {
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Your IRDI verification code',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'mail.property-review-verification-code',
            text: 'mail.property-review-verification-code-text',
        );
    }
}
