<?php

use App\Mail\PropertyReviewVerificationCodeMail;
use App\Models\PropertyReview;
use App\Models\PropertyReviewInvitation;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Livewire\Attributes\Layout;
use Livewire\Component;

new
#[Layout('components.layouts.public')]
class extends Component
{
    public PropertyReviewInvitation $invitation;

    public string $verificationCode = '';

    public bool $codeSent = false;

    public bool $emailVerified = false;

    public string $respectForProperty = '';

    public string $communicationCourtesy = '';

    public string $careOfProperty = '';

    public string $wouldAllowReturn = '';

    public string $comments = '';

    public bool $reviewSubmitted = false;

    public function mount(string $token): void
    {
        $this->invitation = PropertyReviewInvitation::query()
            ->with('memberProfile')
            ->where('token', $token)
            ->firstOrFail();

        if (! $this->invitation->reviewer_email) {
            abort(404);
        }

        $this->emailVerified = filled($this->invitation->email_verified_at);

        $this->codeSent =
            ! $this->emailVerified
            && filled($this->invitation->verification_code_hash)
            && $this->invitation->verification_expires_at?->isFuture();
    }

    public function maskedEmail(): string
    {
        $email = $this->invitation->reviewer_email;

        [$local, $domain] = explode('@', $email, 2);

        $visibleCharacters = min(2, strlen($local));

        $visible = substr($local, 0, $visibleCharacters);

        $masked = str_repeat(
            '•',
            max(3, strlen($local) - $visibleCharacters)
        );

        return $visible.$masked.'@'.$domain;
    }

    public function sendVerificationCode(): void
    {
        $this->invitation->refresh();

        if ($this->invitation->isUsed()) {
            return;
        }

        if ($this->invitation->isExpired()) {
            return;
        }

        if (! $this->invitation->reviewer_email) {
            abort(404);
        }

        $code = (string) random_int(100000, 999999);

        $this->invitation->update([
            'verification_code_hash' => Hash::make($code),
            'verification_expires_at' => now()->addMinutes(10),
            'email_verified_at' => null,
        ]);

        $email = $this->invitation->reviewer_email;

        Mail::to($email)->send(
            (new PropertyReviewVerificationCodeMail(
                code: $code,
            ))->locale(app()->getLocale())
        );

        $this->verificationCode = '';
        $this->codeSent = true;
        $this->emailVerified = false;
    }

    public function verifyCode(): void
    {
        if ($this->invitation->isUsed()) {
            return;
        }

        if ($this->invitation->isExpired()) {
            return;
        }

        $validated = $this->validate([
            'verificationCode' => [
                'required',
                'digits:6',
            ],
        ]);

        $this->invitation->refresh();

        if (
            ! $this->invitation->verification_expires_at
            || $this->invitation->verification_expires_at->isPast()
        ) {
            $this->addError(
                'verificationCode',
                __('This verification code has expired. Please request a new code.')
            );

            return;
        }

        if (
            ! $this->invitation->verification_code_hash
            || ! Hash::check(
                $validated['verificationCode'],
                $this->invitation->verification_code_hash
            )
        ) {
            $this->addError(
                'verificationCode',
                __('The verification code is incorrect.')
            );

            return;
        }

        $this->invitation->update([
            'email_verified_at' => now(),
            'verification_code_hash' => null,
            'verification_expires_at' => null,
        ]);

        $this->verificationCode = '';
        $this->codeSent = false;
        $this->emailVerified = true;
    }

    public function submitReview(): void
    {
        if (! $this->emailVerified) {
            abort(403);
        }

        $validated = $this->validate([
            'respectForProperty' => ['required', 'integer', 'between:1,5'],
            'communicationCourtesy' => ['required', 'integer', 'between:1,5'],
            'careOfProperty' => ['required', 'integer', 'between:1,5'],
            'wouldAllowReturn' => ['required', 'in:yes,no'],
            'comments' => ['nullable', 'string', 'max:2000'],
        ]);

        DB::transaction(function () use ($validated) {

            $invitation = PropertyReviewInvitation::query()
                ->with('memberProfile')
                ->lockForUpdate()
                ->findOrFail($this->invitation->id);

            if ($invitation->isUsed()) {
                $this->addError(
                    'reviewSubmission',
                    __('This review invitation has already been used.')
                );

                return;
            }

            if ($invitation->isExpired()) {
                $this->addError(
                    'reviewSubmission',
                    __('This review invitation has expired.')
                );

                return;
            }

            if (! $invitation->email_verified_at || ! $invitation->reviewer_email) {
                abort(403);
            }

            $alreadyReviewed = PropertyReview::query()
                ->where('member_profile_id', $invitation->member_profile_id)
                ->where('reviewer_email', $invitation->reviewer_email)
                ->exists();

            if ($alreadyReviewed) {
                $this->addError(
                    'reviewSubmission',
                    __('This email address has already submitted feedback for this IRDI member.')
                );

                return;
            }

            PropertyReview::create([
                'member_profile_id' => $invitation->member_profile_id,
                'property_review_invitation_id' => $invitation->id,
                'reviewer_email' => $invitation->reviewer_email,
                'respect_for_property' => $validated['respectForProperty'],
                'communication_courtesy' => $validated['communicationCourtesy'],
                'care_of_property' => $validated['careOfProperty'],
                'would_allow_return' => $validated['wouldAllowReturn'] === 'yes',
                'comments' => filled($validated['comments'])
                    ? trim($validated['comments'])
                    : null,
            ]);

            $invitation->update([
                'used_at' => now(),
            ]);

            $this->invitation = $invitation->fresh('memberProfile');

            $this->reviewSubmitted = true;
        });
    }
};
?>

<section class="bg-zinc-50">
    <div class="mx-auto max-w-3xl px-6 py-16 lg:px-8 lg:py-20">

        <div class="text-center">

            <h1 class="text-3xl font-bold tracking-tight text-irdi-green sm:text-4xl">
                {{ __('Property Owner Feedback') }}
            </h1>

            <div class="mx-auto mt-4 h-1 w-20 bg-irdi-gold"></div>

        </div>

        <flux:card class="mt-10 p-6">

            @if ($reviewSubmitted)

                <div class="rounded-lg border border-green-200 bg-green-50 p-6 text-center">

                    <h2 class="text-lg font-semibold text-green-900">
                        {{ __('Thank You for Your Feedback') }}
                    </h2>

                    <p class="mt-2 text-sm leading-6 text-green-800">
                        {{ __('Your feedback has been submitted successfully.') }}
                    </p>

                    <p class="mt-2 text-sm text-green-700">
                        {{ __('This invitation has now been permanently used and cannot be submitted again.') }}
                    </p>

                </div>

            @elseif ($invitation->isUsed())

                <div class="rounded-lg bg-zinc-100 p-5 text-center">

                    <h2 class="text-lg font-semibold text-zinc-900">
                        {{ __('This invitation has already been used.') }}
                    </h2>

                    <p class="mt-2 text-sm text-zinc-600">
                        {{ __('Each IRDI property owner feedback invitation can only be used once.') }}
                    </p>

                </div>

            @elseif ($invitation->isExpired())

                <div class="rounded-lg bg-amber-50 p-5 text-center">

                    <h2 class="text-lg font-semibold text-amber-900">
                        {{ __('This invitation has expired.') }}
                    </h2>

                    <p class="mt-2 text-sm text-amber-800">
                        {{ __('Please ask the IRDI member to create a new property owner feedback invitation.') }}
                    </p>

                </div>

            @else

                <div class="text-center">

                    @if ($invitation->memberProfile->profile_image)

                        <img
                            src="{{ asset('storage/' . $invitation->memberProfile->profile_image) }}"
                            alt="{{ $invitation->memberProfile->profile_name }}"
                            class="mx-auto h-20 w-20 rounded-full object-cover"
                        >

                    @endif

                    <h2 class="mt-4 text-2xl font-semibold text-irdi-green">
                        {{ __('Rate your experience with :name', ['name' => $invitation->memberProfile->profile_name]) }}
                    </h2>

                    <p class="mt-3 text-zinc-600">
                        {{ __('You do not need an IRDI account to leave feedback.') }}
                    </p>

                    <p class="mt-2 text-sm text-zinc-500">
                        {{ __('This invitation is private, can only be used once, and expires :time.', ['time' => $invitation->expires_at->diffForHumans()]) }}
                    </p>

                </div>

                @if ($emailVerified)

                    <div class="mt-8 rounded-lg border border-green-200 bg-green-50 p-5">

                        <h3 class="font-semibold text-green-900">
                            {{ __('Email Verified') }}
                        </h3>

                        <p class="mt-2 text-sm text-green-800">
                            {{ __('Your email address has been verified. You may now leave feedback about your experience.') }}
                        </p>

                    </div>

                    <form
                        wire:submit="submitReview"
                        class="mt-8 space-y-8"
                    >

                        <div>

                            <label class="block text-sm font-semibold text-irdi-green">
                                {{ __('Respect for Property') }}
                            </label>

                            <p class="mt-1 text-sm text-zinc-500">
                                {{ __('How well did the IRDI member respect your property and any boundaries or conditions you established?') }}
                            </p>

                            <flux:select
                                wire:model="respectForProperty"
                                aria-label="{{ __('Respect for Property') }}"
                                required
                                class="mt-3"
                                placeholder="{{ __('Select a rating') }}"
                            >
                                <flux:select.option value="5">{{ __('5 — Excellent') }}</flux:select.option>
                                <flux:select.option value="4">{{ __('4 — Very Good') }}</flux:select.option>
                                <flux:select.option value="3">{{ __('3 — Good') }}</flux:select.option>
                                <flux:select.option value="2">{{ __('2 — Fair') }}</flux:select.option>
                                <flux:select.option value="1">{{ __('1 — Poor') }}</flux:select.option>
                            </flux:select>

                            @error('respectForProperty')
                            <p class="mt-2 text-sm text-red-600">
                                {{ $message }}
                            </p>
                            @enderror

                        </div>

                        <div>

                            <label class="block text-sm font-semibold text-irdi-green">
                                {{ __('Communication & Courtesy') }}
                            </label>

                            <p class="mt-1 text-sm text-zinc-500">
                                {{ __('How would you rate the IRDI member\'s communication, professionalism, and courtesy?') }}
                            </p>

                            <flux:select
                                wire:model="communicationCourtesy"
                                aria-label="{{ __('Communication & Courtesy') }}"
                                required
                                class="mt-3"
                                placeholder="{{ __('Select a rating') }}"
                            >
                                <flux:select.option value="5">{{ __('5 — Excellent') }}</flux:select.option>
                                <flux:select.option value="4">{{ __('4 — Very Good') }}</flux:select.option>
                                <flux:select.option value="3">{{ __('3 — Good') }}</flux:select.option>
                                <flux:select.option value="2">{{ __('2 — Fair') }}</flux:select.option>
                                <flux:select.option value="1">{{ __('1 — Poor') }}</flux:select.option>
                            </flux:select>

                            @error('communicationCourtesy')
                            <p class="mt-2 text-sm text-red-600">
                                {{ $message }}
                            </p>
                            @enderror

                        </div>

                        <div>

                            <label class="block text-sm font-semibold text-irdi-green">
                                {{ __('Care of the Property') }}
                            </label>

                            <p class="mt-1 text-sm text-zinc-500">
                                {{ __('How well did the IRDI member care for the land, including filling holes and leaving the property in good condition?') }}
                            </p>

                            <flux:select
                                wire:model="careOfProperty"
                                aria-label="{{ __('Care of the Property') }}"
                                required
                                class="mt-3"
                                placeholder="{{ __('Select a rating') }}"
                            >
                                <flux:select.option value="5">{{ __('5 — Excellent') }}</flux:select.option>
                                <flux:select.option value="4">{{ __('4 — Very Good') }}</flux:select.option>
                                <flux:select.option value="3">{{ __('3 — Good') }}</flux:select.option>
                                <flux:select.option value="2">{{ __('2 — Fair') }}</flux:select.option>
                                <flux:select.option value="1">{{ __('1 — Poor') }}</flux:select.option>
                            </flux:select>

                            @error('careOfProperty')
                            <p class="mt-2 text-sm text-red-600">
                                {{ $message }}
                            </p>
                            @enderror

                        </div>

                        <div>

                            <label class="block text-sm font-semibold text-irdi-green">
                                {{ __('Would you allow this IRDI member to return?') }}
                            </label>

                            <p class="mt-1 text-sm text-zinc-500">
                                {{ __('Based on this experience, would you give this IRDI member permission to detect on your property again?') }}
                            </p>

                            <flux:select
                                wire:model="wouldAllowReturn"
                                aria-label="{{ __('Would you allow this IRDI member to return?') }}"
                                required
                                class="mt-3"
                                placeholder="{{ __('Select an answer') }}"
                            >
                                <flux:select.option value="yes">
                                    {{ __('Yes') }}
                                </flux:select.option>

                                <flux:select.option value="no">
                                    {{ __('No') }}
                                </flux:select.option>
                            </flux:select>

                            @error('wouldAllowReturn')
                            <p class="mt-2 text-sm text-red-600">
                                {{ $message }}
                            </p>
                            @enderror

                        </div>

                        <div>

                            <flux:textarea
                                wire:model="comments"
                                label="{{ __('Comments About Your Experience') }}"
                                description="{{ __('Optional. Please keep your comments focused on your experience with this IRDI member.') }}"
                                rows="5"
                            />

                            <p class="mt-2 text-right text-sm text-zinc-500">
                                {{ __(':count / 2,000 characters', ['count' => strlen($comments)]) }}
                            </p>

                        </div>

                        <div class="rounded-lg border border-zinc-200 bg-zinc-50 p-4">

                            <p class="text-sm leading-6 text-zinc-600">
                                {{ __('Your email address is used to verify this submission and help prevent duplicate or fraudulent feedback. It will not be displayed publicly.') }}
                            </p>

                        </div>

                        @error('reviewSubmission')
                        <div class="rounded-lg border border-red-200 bg-red-50 p-4">

                            <p class="text-sm font-medium text-red-800">
                                {{ $message }}
                            </p>

                        </div>
                        @enderror

                        <flux:button
                            type="submit"
                            variant="primary"
                            wire:loading.attr="disabled"
                            wire:target="submitReview"
                        >
                            <span wire:loading.remove wire:target="submitReview">
                                {{ __('Submit Feedback') }}
                            </span>

                            <span wire:loading wire:target="submitReview">
                                {{ __('Submitting...') }}
                            </span>
                        </flux:button>

                    </form>

                @elseif (! $codeSent)

                    <div class="mt-8 border-t border-zinc-200 pt-8">

                        <h3 class="text-lg font-semibold text-irdi-green">
                            {{ __('Verify Your Email') }}
                        </h3>

                        <p class="mt-2 text-sm leading-6 text-zinc-600">
                            {{ __('This invitation was sent to') }}
                            <strong>{{ $this->maskedEmail() }}</strong>{{ __('. Send a six-digit verification code to confirm that you have access to this email address.') }}
                        </p>

                        <p class="mt-2 text-sm text-zinc-500">
                            {{ __('Your email address will remain private and will not be displayed publicly.') }}
                        </p>

                        <div class="mt-6">

                            <flux:button
                                type="button"
                                variant="primary"
                                wire:click="sendVerificationCode"
                                wire:loading.attr="disabled"
                                wire:target="sendVerificationCode"
                            >
                                <span wire:loading.remove wire:target="sendVerificationCode">
                                    {{ __('Send Verification Code') }}
                                </span>

                                <span wire:loading wire:target="sendVerificationCode">
                                    {{ __('Sending...') }}
                                </span>
                            </flux:button>

                        </div>

                    </div>

                @else

                    <div class="mt-8 rounded-lg border border-green-200 bg-green-50 p-5">

                        <h3 class="font-semibold text-green-900">
                            {{ __('Verification Code Sent') }}
                        </h3>

                        <p class="mt-2 text-sm leading-6 text-green-800">
                            {{ __('We sent a six-digit verification code to') }}
                            <strong>{{ $this->maskedEmail() }}</strong>.
                        </p>

                        <p class="mt-2 text-sm text-green-700">
                            {{ __('The code expires in 10 minutes.') }}
                        </p>

                    </div>

                    <form
                        wire:submit="verifyCode"
                        class="mt-6 space-y-4"
                    >

                        <flux:input
                            wire:model="verificationCode"
                            type="text"
                            inputmode="numeric"
                            maxlength="6"
                            label="{{ __('Verification Code') }}"
                            placeholder="123456"
                            description="{{ __('Enter the six-digit code sent to your email address.') }}"
                            required
                        />

                        <flux:button
                            type="submit"
                            variant="primary"
                            wire:loading.attr="disabled"
                            wire:target="verifyCode"
                        >
                            <span wire:loading.remove wire:target="verifyCode">
                                {{ __('Verify Code') }}
                            </span>

                            <span wire:loading wire:target="verifyCode">
                                {{ __('Verifying...') }}
                            </span>
                        </flux:button>

                    </form>

                @endif

            @endif

        </flux:card>

    </div>
</section>
