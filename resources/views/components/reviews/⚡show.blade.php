<?php

use App\Mail\PropertyReviewVerificationCodeMail;
use App\Models\PropertyReview;
use App\Models\PropertyReviewInvitation;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\RateLimiter;
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

    public string $overallExperience = '';

    public string $respectForProperty = '';

    public string $communicationCourtesy = '';

    public string $propertyLeftSatisfactorily = '';

    public string $wouldAllowReturn = '';

    public string $comments = '';

    public string $privateConcern = '';

    public string $privateConcernDetails = '';

    public bool $reviewSubmitted = false;

    public function mount(string $token): void
    {
        $this->invitation = PropertyReviewInvitation::query()
            ->with('memberProfile')
            ->where('token', $token)
            ->firstOrFail();

        abort_if($this->invitation->memberProfile->personal_information_hidden, 404);

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
        abort_if($this->invitation->memberProfile->personal_information_hidden, 404);

        if ($this->invitation->isUsed()) {
            return;
        }

        if ($this->invitation->isCancelled()) {
            return;
        }

        if ($this->invitation->isExpired()) {
            return;
        }

        if (! $this->invitation->reviewer_email) {
            abort(404);
        }

        $rateLimitKey = 'property-review-verification-send:'.$this->invitation->id;

        if (RateLimiter::tooManyAttempts($rateLimitKey, 3)) {
            $seconds = RateLimiter::availableIn($rateLimitKey);

            $this->addError(
                'verificationCode',
                'Too many verification codes have been requested. Please try again in '.$seconds.' seconds.'
            );

            return;
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

        RateLimiter::hit($rateLimitKey, 600);

        $this->verificationCode = '';
        $this->codeSent = true;
        $this->emailVerified = false;
    }

    public function verifyCode(): void
    {
        $this->invitation->refresh();
        abort_if($this->invitation->memberProfile->personal_information_hidden, 404);

        if ($this->invitation->isUsed()) {
            return;
        }

        if ($this->invitation->isCancelled()) {
            return;
        }

        if ($this->invitation->isExpired()) {
            return;
        }

        $rateLimitKey = 'property-review-verification-attempt:'.$this->invitation->id;

        if (RateLimiter::tooManyAttempts($rateLimitKey, 5)) {
            $seconds = RateLimiter::availableIn($rateLimitKey);

            $this->addError(
                'verificationCode',
                __('Too many incorrect verification attempts. Please try again in :seconds seconds.', [
                    'seconds' => $seconds,
                ])
            );

            return;
        }

        $validated = $this->validate([
            'verificationCode' => [
                'required',
                'digits:6',
            ],
        ]);


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
            RateLimiter::hit($rateLimitKey, 600);

            $this->addError(
                'verificationCode',
                __('The verification code is incorrect.')
            );

            return;
        }

        RateLimiter::clear($rateLimitKey);

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
            'overallExperience' => ['required', 'integer', 'between:1,5'],
            'respectForProperty' => ['required', 'integer', 'between:1,5'],
            'communicationCourtesy' => ['required', 'integer', 'between:1,5'],
            'propertyLeftSatisfactorily' => ['required', 'in:yes,no'],
            'wouldAllowReturn' => ['required', 'in:yes,no'],
            'comments' => ['nullable', 'string', 'max:2000'],
            'privateConcern' => ['required', 'in:yes,no'],
            'privateConcernDetails' => [
                'nullable',
                'required_if:privateConcern,yes',
                'string',
                'max:2000',
            ],
        ]);

        DB::transaction(function () use ($validated) {

            $invitation = PropertyReviewInvitation::query()
                ->with('memberProfile')
                ->lockForUpdate()
                ->findOrFail($this->invitation->id);

            abort_if($invitation->memberProfile->personal_information_hidden, 404);

            if ($invitation->isUsed()) {
                $this->addError(
                    'reviewSubmission',
                    __('This review invitation has already been used.')
                );

                return;
            }

            if ($invitation->isCancelled()) {
                $this->addError(
                    'reviewSubmission',
                    __('This review invitation has been cancelled.')
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
                'overall_experience' => $validated['overallExperience'],
                'respect_for_property' => $validated['respectForProperty'],
                'communication_courtesy' => $validated['communicationCourtesy'],
                'care_of_property' => null,
                'property_left_satisfactorily' => $validated['propertyLeftSatisfactorily'] === 'yes',
                'would_allow_return' => $validated['wouldAllowReturn'] === 'yes',
                'comments' => filled($validated['comments'])
                    ? trim($validated['comments'])
                    : null,
                'private_concern' => $validated['privateConcern'] === 'yes',
                'private_concern_details' => $validated['privateConcern'] === 'yes'
                && filled($validated['privateConcernDetails'])
                    ? trim($validated['privateConcernDetails'])
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
                        {{ __('Your feedback has been submitted successfully. Thank you for helping support responsible detecting and accountability within IRDI.') }}
                    </p>

                    <p class="mt-2 text-sm text-green-700">
                        {{ __('This invitation has now been permanently used and cannot be submitted again. If you included a Private Concern, it remains available only to authorized IRDI staff for review.') }}
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

            @elseif ($invitation->isCancelled())

                <div class="rounded-lg bg-zinc-100 p-5 text-center">

                    <h2 class="text-lg font-semibold text-zinc-900">
                        {{ __('This invitation has been cancelled.') }}
                    </h2>

                    <p class="mt-2 text-sm text-zinc-600">
                        {{ __('This property owner feedback invitation is no longer available.') }}
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

                    @if ($invitation->memberProfile->publicDetail('profile_image'))

                        <img
                            src="{{ asset('storage/' . $invitation->memberProfile->publicDetail('profile_image')) }}"
                            alt="{{ $invitation->memberProfile->publicName() }}"
                            class="mx-auto h-20 w-20 rounded-full object-cover"
                        >

                    @endif

                    <h2 class="mt-4 text-2xl font-semibold text-irdi-green">
                        {{ __('Rate your experience with :name', ['name' => $invitation->memberProfile->publicName()]) }}
                    </h2>

                    <p class="mt-3 text-zinc-600">
                        {{ __('You do not need an IRDI account to leave feedback.') }}
                    </p>

                    <p class="mt-2 text-sm text-zinc-500">
                        {{ __('This invitation is private, can only be used once, and expires :time.', ['time' => $invitation->expires_at->diffForHumans()]) }}
                    </p>

                </div>

                <div class="mt-8 rounded-xl border border-irdi-gold/40 bg-irdi-gold/10 p-5">
                    <h3 class="font-semibold text-irdi-green">
                        {{ __('Why IRDI asks for your feedback') }}
                    </h3>

                    <p class="mt-2 text-sm leading-6 text-zinc-700">
                        {{ __('IRDI membership is a commitment to responsible conduct. Your first-hand feedback helps add real-world context to a member’s public profile and supports IRDI’s accountability process.') }}
                    </p>

                    <p class="mt-2 text-sm leading-6 text-zinc-600">
                        {{ __('Your public feedback may contribute to the member’s Property Owner Feedback record. If you have a concern that should not be shared with the member or displayed publicly, you can send it privately to authorized IRDI staff later in this form.') }}
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
                                {{ __('Overall Experience') }}
                            </label>

                            <p class="mt-1 text-sm text-zinc-500">
                                {{ __('Overall, how would you rate your experience with this IRDI member?') }}
                            </p>

                            <flux:select
                                wire:model="overallExperience"
                                class="mt-3"
                                placeholder="{{ __('Select a rating') }}"
                            >
                                <flux:select.option value="5">{{ __('5 — Excellent') }}</flux:select.option>
                                <flux:select.option value="4">{{ __('4 — Very Good') }}</flux:select.option>
                                <flux:select.option value="3">{{ __('3 — Good') }}</flux:select.option>
                                <flux:select.option value="2">{{ __('2 — Fair') }}</flux:select.option>
                                <flux:select.option value="1">{{ __('1 — Poor') }}</flux:select.option>
                            </flux:select>

                            @error('overallExperience')
                            <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                            @enderror

                        </div>

                        <div>
                            <label class="block text-sm font-semibold text-irdi-green">{{ __('Respect for Property') }}</label>
                            <p class="mt-1 text-sm text-zinc-500">{{ __('How well did the IRDI member respect your property and any boundaries or conditions you established?') }}</p>
                            <flux:select wire:model="respectForProperty" class="mt-3" placeholder="{{ __('Select a rating') }}">
                                <flux:select.option value="5">{{ __('5 — Excellent') }}</flux:select.option>
                                <flux:select.option value="4">{{ __('4 — Very Good') }}</flux:select.option>
                                <flux:select.option value="3">{{ __('3 — Good') }}</flux:select.option>
                                <flux:select.option value="2">{{ __('2 — Fair') }}</flux:select.option>
                                <flux:select.option value="1">{{ __('1 — Poor') }}</flux:select.option>
                            </flux:select>
                            @error('respectForProperty')<p class="mt-2 text-sm text-red-600">{{ $message }}</p>@enderror
                        </div>

                        <div>
                            <label class="block text-sm font-semibold text-irdi-green">{{ __('Communication & Courtesy') }}</label>
                            <p class="mt-1 text-sm text-zinc-500">{{ __("How would you rate the IRDI member's communication, professionalism, and courtesy?") }}</p>
                            <flux:select wire:model="communicationCourtesy" class="mt-3" placeholder="{{ __('Select a rating') }}">
                                <flux:select.option value="5">{{ __('5 — Excellent') }}</flux:select.option>
                                <flux:select.option value="4">{{ __('4 — Very Good') }}</flux:select.option>
                                <flux:select.option value="3">{{ __('3 — Good') }}</flux:select.option>
                                <flux:select.option value="2">{{ __('2 — Fair') }}</flux:select.option>
                                <flux:select.option value="1">{{ __('1 — Poor') }}</flux:select.option>
                            </flux:select>
                            @error('communicationCourtesy')<p class="mt-2 text-sm text-red-600">{{ $message }}</p>@enderror
                        </div>

                        <div>
                            <label class="block text-sm font-semibold text-irdi-green">{{ __('Was the property left in satisfactory condition?') }}</label>
                            <p class="mt-1 text-sm text-zinc-500">{{ __('Consider whether holes were properly filled, the area was left clean, and the property was left in an acceptable condition.') }}</p>
                            <flux:select wire:model="propertyLeftSatisfactorily" class="mt-3" placeholder="{{ __('Select an answer') }}">
                                <flux:select.option value="yes">{{ __('Yes') }}</flux:select.option>
                                <flux:select.option value="no">{{ __('No') }}</flux:select.option>
                            </flux:select>
                            @error('propertyLeftSatisfactorily')<p class="mt-2 text-sm text-red-600">{{ $message }}</p>@enderror
                        </div>

                        <div>
                            <label class="block text-sm font-semibold text-irdi-green">{{ __('Would you allow this IRDI member to return?') }}</label>
                            <p class="mt-1 text-sm text-zinc-500">{{ __('Based on this experience, would you give this IRDI member permission to detect on your property again?') }}</p>
                            <flux:select wire:model="wouldAllowReturn" class="mt-3" placeholder="{{ __('Select an answer') }}">
                                <flux:select.option value="yes">{{ __('Yes') }}</flux:select.option>
                                <flux:select.option value="no">{{ __('No') }}</flux:select.option>
                            </flux:select>
                            @error('wouldAllowReturn')<p class="mt-2 text-sm text-red-600">{{ $message }}</p>@enderror
                        </div>

                        <div>
                            <flux:textarea wire:model="comments" label="{{ __('Comments About Your Experience') }}" description="{{ __('Optional. Please keep your comments focused on your experience with this IRDI member.') }}" rows="5" />
                            @error('comments')<p class="mt-2 text-sm text-red-600">{{ $message }}</p>@enderror
                            <p class="mt-2 text-right text-sm text-zinc-500">{{ __(':count / 2,000 characters', ['count' => strlen($comments)]) }}</p>
                        </div>

                        <div class="rounded-lg border border-amber-200 bg-amber-50 p-5">
                            <h3 class="font-semibold text-amber-900">{{ __('Private Concern') }}</h3>
                            <p class="mt-2 text-sm leading-6 text-amber-800">{{ __('Do you have a concern about this experience that you would like to share privately with IRDI staff?') }}</p>
                            <p class="mt-2 text-sm leading-6 text-amber-700">{{ __('Private concerns and their details are not shown to the IRDI member or displayed publicly. They are available only to authorized IRDI staff for review and may be considered as part of IRDI’s accountability process.') }}</p>
                            <div class="mt-4">
                                <flux:select wire:model.live="privateConcern" placeholder="{{ __('Select an answer') }}">
                                    <flux:select.option value="no">{{ __('No') }}</flux:select.option>
                                    <flux:select.option value="yes">{{ __('Yes') }}</flux:select.option>
                                </flux:select>
                                @error('privateConcern')<p class="mt-2 text-sm text-red-600">{{ $message }}</p>@enderror
                            </div>
                            @if ($privateConcern === 'yes')
                                <div class="mt-5">
                                    <flux:textarea wire:model="privateConcernDetails" label="{{ __('Private Concern Details') }}" description="{{ __('Please describe your concern for IRDI staff. This information will not be shown to the member or displayed publicly.') }}" rows="5" />
                                    @error('privateConcernDetails')<p class="mt-2 text-sm text-red-600">{{ $message }}</p>@enderror
                                    <p class="mt-2 text-right text-sm text-amber-700">{{ __(':count / 2,000 characters', ['count' => strlen($privateConcernDetails)]) }}</p>
                                </div>
                            @endif
                        </div>

                        <div class="rounded-lg border border-zinc-200 bg-zinc-50 p-4">
                            <h3 class="text-sm font-semibold text-zinc-800">{{ __('Before you submit') }}</h3>
                            <p class="mt-2 text-sm leading-6 text-zinc-600">{{ __('Your ratings, answers, and public comments may appear on or contribute to the member’s public Property Owner Feedback record. Any Private Concern and Private Concern Details are excluded from the member-facing and public review experience.') }}</p>
                            <p class="mt-2 text-sm leading-6 text-zinc-600">{{ __('Your email address is used to verify this submission and help prevent duplicate or fraudulent feedback. It will not be displayed publicly. IRDI verifies access to the email address, not property ownership or the truth of the feedback submitted.') }}</p>
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
                            {{ __('Your email address will remain private and will not be displayed publicly. Email verification confirms access to this email address; it does not mean IRDI has independently verified property ownership or the contents of your feedback.') }}
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
