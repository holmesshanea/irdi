{{ __('IRDI Property Owner Feedback') }}

{{ __(':name has invited you to provide feedback about your experience with them as an IRDI Detectorist.', ['name' => $profile->profile_name]) }}

{{ __('You do not need an IRDI account to participate.') }}

{{ __('Before submitting feedback, IRDI will ask you to verify this email address. Your email address will remain private and will not be displayed publicly.') }}

{{ __('Leave feedback here:') }}

{{ $reviewUrl }}

{{ __('This invitation can only be used once and expires :date.', ['date' => $expiresAt]) }}

{{ __('If you were not expecting this invitation, you may ignore this email.') }}

IRDI — International Responsible Detectorist Institute
