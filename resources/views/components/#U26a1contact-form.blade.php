<?php

use Livewire\Component;
use Livewire\Attributes\Validate;
use App\Mail\ContactMessage;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\RateLimiter;

new class extends Component
{
    #[Validate('required|min:2|max:100')]
    public string $name = '';

    #[Validate('required|email|max:255')]
    public string $email = '';

    #[Validate('required|min:3|max:150')]
    public string $subject = '';

    #[Validate('required|min:10|max:2000')]
    public string $message = '';

    public string $website = '';

    public function submit()
    {
        /*
         * Honeypot
         *
         * Real users should never fill the website field.
         * If it contains anything, pretend the message succeeded
         * but do not actually send an email.
         */
        if ($this->website !== '') {
            session()->flash(
                'success',
                'Thank you! Your message has been received.'
            );

            $this->reset(
                'name',
                'email',
                'subject',
                'message',
                'website'
            );

            return;
        }

        $this->validate();

        /*
         * Rate limit contact submissions by IP address.
         */
        $key = 'contact-form:'.request()->ip();

        $sent = RateLimiter::attempt(
            $key,
            5,
            function () {
                Mail::to(config('mail.contact_address'))
                    ->send(new ContactMessage(
                        name: $this->name,
                        email: $this->email,
                        contactSubject: $this->subject,
                        body: $this->message,
                    ));

                return true;
            },
            60,
        );

        if (! $sent) {
            $seconds = RateLimiter::availableIn($key);

            $this->addError(
                'form',
                "Too many messages have been sent. Please try again in {$seconds} seconds."
            );

            return;
        }

        session()->flash(
            'success',
            'Thank you! Your message has been received.'
        );

        $this->reset(
            'name',
            'email',
            'subject',
            'message',
            'website'
        );
    }
};
?>

<flux:card class="p-6 shadow-sm sm:p-8 lg:p-10">

    @if (session('success'))
        <flux:callout
            variant="success"
            icon="check-circle"
            class="mb-6"
        >
            <flux:callout.heading>
                Message Sent
            </flux:callout.heading>

            <flux:callout.text>
                {{ session('success') }}
            </flux:callout.text>
        </flux:callout>
    @endif

    {{--inserting the rate-limit error--}}

        @error('form')
        <flux:callout
            variant="danger"
            icon="x-circle"
            class="mb-6"
        >
            <flux:callout.heading>
                Unable to Send Message
            </flux:callout.heading>

            <flux:callout.text>
                {{ $message }}
            </flux:callout.text>
        </flux:callout>
        @enderror

    <form wire:submit="submit" class="space-y-6">

        {{-- Honeypot: real users should never see or fill this field --}}
        <div
            class="absolute left-[-9999px] h-px w-px overflow-hidden"
            aria-hidden="true"
        >
            <label for="website">
                Website
            </label>

            <input
                id="website"
                type="text"
                wire:model="website"
                tabindex="-1"
                autocomplete="off"
            >
        </div>

        <flux:input
            wire:model="name"
            label="Name"
            placeholder="Your name"
            autocomplete="name"
        />

        <flux:input
            wire:model="email"
            type="email"
            label="Email"
            placeholder="you@example.com"
            autocomplete="email"
        />

        <flux:input
            wire:model="subject"
            label="Subject"
            placeholder="How can we help?"
        />

        <flux:textarea
            wire:model="message"
            label="Message"
            placeholder="Write your message..."
            rows="6"
        />

        <flux:button
            type="submit"
            variant="primary"
            class="w-full sm:w-auto"
        >
    <span wire:loading.remove wire:target="submit">
        Send Message
    </span>

            <span wire:loading wire:target="submit">
        Sending...
    </span>
        </flux:button>

    </form>

</flux:card>
