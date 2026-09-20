<?php

use App\Mail\PropertyReviewInvitationMail;
use App\Mail\PropertyReviewVerificationCodeMail;
use App\Models\MemberProfile;
use App\Models\PropertyReview;
use App\Models\PropertyReviewInvitation;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Livewire\Livewire;

function propertyReviewInvitationUser(array $attributes = []): User
{
    return User::factory()->create(array_merge([
        'membership_status' => 'active',
        'member_since' => now()->toDateString(),
        'ethics_agreed_at' => now(),
        'best_practices_agreed_at' => now(),
    ], $attributes));
}

function propertyReviewInvitationProfile(
    ?User $user = null
): MemberProfile {
    $user ??= propertyReviewInvitationUser();

    return MemberProfile::factory()->for($user)->create([
        'directory_visible' => true,
    ]);
}

function propertyReviewInvitation(
    MemberProfile $profile,
    array $attributes = []
): PropertyReviewInvitation {
    return PropertyReviewInvitation::create(array_merge([
        'member_profile_id' => $profile->id,
        'token' => Str::random(64),
        'reviewer_email' => 'property-owner-'.Str::random(8).'@example.com',
        'expires_at' => now()->addDays(30),
    ], $attributes));
}

test('member can create property owner review invitation', function () {
    Mail::fake();

    $user = propertyReviewInvitationUser();
    $profile = propertyReviewInvitationProfile($user);

    $this->actingAs($user)
        ->post(route('account.review-invitations.store', $profile), [
            'reviewer_email' => '  OWNER@EXAMPLE.COM  ',
        ])
        ->assertRedirect();

    $invitation = PropertyReviewInvitation::query()->sole();

    expect($invitation->member_profile_id)->toBe($profile->id)
        ->and($invitation->reviewer_email)->toBe('owner@example.com')
        ->and($invitation->token)->toHaveLength(64)
        ->and($invitation->expires_at)->not->toBeNull()
        ->and($invitation->expires_at->isFuture())->toBeTrue()
        ->and($invitation->used_at)->toBeNull()
        ->and($invitation->cancelled_at)->toBeNull();

    Mail::assertSent(
        PropertyReviewInvitationMail::class,
        fn ($mail) => $mail->hasTo('owner@example.com')
    );
});

test('member cannot create invitation for another members profile', function () {
    Mail::fake();

    $owner = propertyReviewInvitationUser();
    $otherMember = propertyReviewInvitationUser();

    $profile = propertyReviewInvitationProfile($owner);

    $this->actingAs($otherMember)
        ->post(route('account.review-invitations.store', $profile), [
            'reviewer_email' => 'owner@example.com',
        ])
        ->assertForbidden();

    expect(PropertyReviewInvitation::count())->toBe(0);

    Mail::assertNothingSent();
});

test('member not in good standing cannot create invitation', function () {
    Mail::fake();

    $user = propertyReviewInvitationUser([
        'membership_status' => 'suspended',
    ]);

    $profile = propertyReviewInvitationProfile($user);

    $this->actingAs($user)
        ->post(route('account.review-invitations.store', $profile), [
            'reviewer_email' => 'owner@example.com',
        ])
        ->assertForbidden();

    expect(PropertyReviewInvitation::count())->toBe(0);

    Mail::assertNothingSent();
});

test('reviewer who already reviewed profile cannot receive another invitation', function () {
    Mail::fake();

    $user = propertyReviewInvitationUser();
    $profile = propertyReviewInvitationProfile($user);

    $oldInvitation = propertyReviewInvitation($profile, [
        'reviewer_email' => 'owner@example.com',
        'email_verified_at' => now(),
        'used_at' => now(),
    ]);

    PropertyReview::create([
        'member_profile_id' => $profile->id,
        'property_review_invitation_id' => $oldInvitation->id,
        'reviewer_email' => 'owner@example.com',
        'overall_experience' => 5,
        'respect_for_property' => 5,
        'communication_courtesy' => 5,
        'care_of_property' => null,
        'property_left_satisfactorily' => true,
        'would_allow_return' => true,
        'comments' => null,
        'private_concern' => false,
        'private_concern_details' => null,
    ]);

    $this->actingAs($user)
        ->from('/account')
        ->post(route('account.review-invitations.store', $profile), [
            'reviewer_email' => 'OWNER@EXAMPLE.COM',
        ])
        ->assertRedirect('/account')
        ->assertSessionHasErrors([
            'reviewer_email_'.$profile->id,
        ]);

    expect(PropertyReviewInvitation::count())->toBe(1);

    Mail::assertNothingSent();
});

test('member can resend active invitation and verification state is reset', function () {
    Mail::fake();

    $user = propertyReviewInvitationUser();
    $profile = propertyReviewInvitationProfile($user);

    $invitation = propertyReviewInvitation($profile, [
        'verification_code_hash' => Hash::make('123456'),
        'verification_expires_at' => now()->addMinutes(10),
        'email_verified_at' => now(),
    ]);

    $oldToken = $invitation->token;

    $this->actingAs($user)
        ->post(route('account.review-invitations.resend', $invitation))
        ->assertRedirect();

    $invitation->refresh();

    expect($invitation->token)->not->toBe($oldToken)
        ->and($invitation->token)->toHaveLength(64)
        ->and($invitation->verification_code_hash)->toBeNull()
        ->and($invitation->verification_expires_at)->toBeNull()
        ->and($invitation->email_verified_at)->toBeNull()
        ->and($invitation->expires_at->isFuture())->toBeTrue();

    Mail::assertSent(
        PropertyReviewInvitationMail::class,
        fn ($mail) => $mail->hasTo($invitation->reviewer_email)
    );
});

test('expired invitation can be resent and becomes active with new token', function () {
    Mail::fake();

    $user = propertyReviewInvitationUser();
    $profile = propertyReviewInvitationProfile($user);

    $invitation = propertyReviewInvitation($profile, [
        'expires_at' => now()->subDay(),
    ]);

    $oldToken = $invitation->token;

    expect($invitation->isExpired())->toBeTrue();

    $this->actingAs($user)
        ->post(route('account.review-invitations.resend', $invitation))
        ->assertRedirect();

    $invitation->refresh();

    expect($invitation->token)->not->toBe($oldToken)
        ->and($invitation->isExpired())->toBeFalse()
        ->and($invitation->isAvailable())->toBeTrue();

    Mail::assertSent(PropertyReviewInvitationMail::class);
});

test('used invitation cannot be resent', function () {
    Mail::fake();

    $user = propertyReviewInvitationUser();
    $profile = propertyReviewInvitationProfile($user);

    $invitation = propertyReviewInvitation($profile, [
        'used_at' => now(),
    ]);

    $oldToken = $invitation->token;
    $oldExpiration = $invitation->expires_at->copy();

    $this->actingAs($user)
        ->post(route('account.review-invitations.resend', $invitation))
        ->assertRedirect();

    $invitation->refresh();

    expect($invitation->token)->toBe($oldToken)
        ->and($invitation->expires_at->equalTo($oldExpiration))->toBeTrue();

    Mail::assertNothingSent();
});

test('cancelled invitation cannot be resent', function () {
    Mail::fake();

    $user = propertyReviewInvitationUser();
    $profile = propertyReviewInvitationProfile($user);

    $invitation = propertyReviewInvitation($profile, [
        'cancelled_at' => now(),
    ]);

    $oldToken = $invitation->token;

    $this->actingAs($user)
        ->post(route('account.review-invitations.resend', $invitation))
        ->assertRedirect();

    expect($invitation->fresh()->token)->toBe($oldToken);

    Mail::assertNothingSent();
});

test('member who loses good standing cannot resend existing invitation', function () {
    Mail::fake();

    $user = propertyReviewInvitationUser();
    $profile = propertyReviewInvitationProfile($user);
    $invitation = propertyReviewInvitation($profile);

    $oldToken = $invitation->token;

    $user->update([
        'membership_status' => 'suspended',
    ]);

    $this->actingAs($user->fresh())
        ->post(route('account.review-invitations.resend', $invitation))
        ->assertForbidden();

    expect($invitation->fresh()->token)->toBe($oldToken);

    Mail::assertNothingSent();
});

test('another member cannot resend invitation', function () {
    Mail::fake();

    $owner = propertyReviewInvitationUser();
    $otherMember = propertyReviewInvitationUser();

    $profile = propertyReviewInvitationProfile($owner);
    $invitation = propertyReviewInvitation($profile);

    $oldToken = $invitation->token;

    $this->actingAs($otherMember)
        ->post(route('account.review-invitations.resend', $invitation))
        ->assertForbidden();

    expect($invitation->fresh()->token)->toBe($oldToken);

    Mail::assertNothingSent();
});

test('member can cancel active invitation and verification state is cleared', function () {
    $user = propertyReviewInvitationUser();
    $profile = propertyReviewInvitationProfile($user);

    $invitation = propertyReviewInvitation($profile, [
        'verification_code_hash' => Hash::make('123456'),
        'verification_expires_at' => now()->addMinutes(10),
        'email_verified_at' => now(),
    ]);

    $this->actingAs($user)
        ->post(route('account.review-invitations.cancel', $invitation))
        ->assertRedirect();

    $invitation->refresh();

    expect($invitation->cancelled_at)->not->toBeNull()
        ->and($invitation->verification_code_hash)->toBeNull()
        ->and($invitation->verification_expires_at)->toBeNull()
        ->and($invitation->email_verified_at)->toBeNull()
        ->and($invitation->isAvailable())->toBeFalse();
});

test('cancelling already cancelled invitation is harmless', function () {
    $user = propertyReviewInvitationUser();
    $profile = propertyReviewInvitationProfile($user);

    $cancelledAt = now()->subHour()->startOfSecond();

    $invitation = propertyReviewInvitation($profile, [
        'cancelled_at' => $cancelledAt,
    ]);

    $this->actingAs($user)
        ->post(route('account.review-invitations.cancel', $invitation))
        ->assertRedirect();

    expect(
        $invitation->fresh()->cancelled_at->equalTo($cancelledAt)
    )->toBeTrue();
});

test('used invitation cannot be cancelled', function () {
    $user = propertyReviewInvitationUser();
    $profile = propertyReviewInvitationProfile($user);

    $invitation = propertyReviewInvitation($profile, [
        'used_at' => now(),
    ]);

    $this->actingAs($user)
        ->post(route('account.review-invitations.cancel', $invitation))
        ->assertRedirect();

    expect($invitation->fresh()->cancelled_at)->toBeNull();
});

test('another member cannot cancel invitation', function () {
    $owner = propertyReviewInvitationUser();
    $otherMember = propertyReviewInvitationUser();

    $profile = propertyReviewInvitationProfile($owner);
    $invitation = propertyReviewInvitation($profile);

    $this->actingAs($otherMember)
        ->post(route('account.review-invitations.cancel', $invitation))
        ->assertForbidden();

    expect($invitation->fresh()->cancelled_at)->toBeNull();
});

test('member who loses good standing may still cancel outstanding invitation', function () {
    $user = propertyReviewInvitationUser();
    $profile = propertyReviewInvitationProfile($user);
    $invitation = propertyReviewInvitation($profile);

    $user->update([
        'membership_status' => 'suspended',
    ]);

    $this->actingAs($user->fresh())
        ->post(route('account.review-invitations.cancel', $invitation))
        ->assertRedirect();

    expect($invitation->fresh()->cancelled_at)->not->toBeNull();
});

test('cancelled invitation cannot send verification code', function () {
    Mail::fake();

    $profile = propertyReviewInvitationProfile();

    $invitation = propertyReviewInvitation($profile, [
        'cancelled_at' => now(),
    ]);

    Livewire::test('reviews.show', ['token' => $invitation->token])
        ->call('sendVerificationCode')
        ->assertSee('This invitation has been cancelled.');

    $invitation->refresh();

    expect($invitation->verification_code_hash)->toBeNull()
        ->and($invitation->verification_expires_at)->toBeNull()
        ->and($invitation->email_verified_at)->toBeNull();

    Mail::assertNotSent(PropertyReviewVerificationCodeMail::class);
});

test('cancelled invitation cannot verify previously issued verification code', function () {
    $profile = propertyReviewInvitationProfile();

    $invitation = propertyReviewInvitation($profile, [
        'verification_code_hash' => Hash::make('123456'),
        'verification_expires_at' => now()->addMinutes(10),
        'cancelled_at' => now(),
    ]);

    Livewire::test('reviews.show', ['token' => $invitation->token])
        ->set('verificationCode', '123456')
        ->call('verifyCode');

    expect($invitation->fresh()->email_verified_at)->toBeNull();
});

test('cancellation after page is opened and verified prevents review submission', function () {
    $profile = propertyReviewInvitationProfile();

    $invitation = propertyReviewInvitation($profile, [
        'email_verified_at' => now(),
    ]);

    $component = Livewire::test('reviews.show', [
        'token' => $invitation->token,
    ])
        ->set('overallExperience', '5')
        ->set('respectForProperty', '5')
        ->set('communicationCourtesy', '5')
        ->set('propertyLeftSatisfactorily', 'yes')
        ->set('wouldAllowReturn', 'yes')
        ->set('privateConcern', 'no');

    $invitation->update([
        'cancelled_at' => now(),
        'email_verified_at' => null,
    ]);

    $component
        ->call('submitReview')
        ->assertHasErrors(['reviewSubmission']);

    expect(PropertyReview::count())->toBe(0)
        ->and($invitation->fresh()->used_at)->toBeNull();
});

test('expired invitation cannot send verification code', function () {
    Mail::fake();

    $profile = propertyReviewInvitationProfile();

    $invitation = propertyReviewInvitation($profile, [
        'expires_at' => now()->subMinute(),
    ]);

    Livewire::test('reviews.show', ['token' => $invitation->token])
        ->call('sendVerificationCode')
        ->assertSee('This invitation has expired.');

    expect($invitation->fresh()->verification_code_hash)->toBeNull();

    Mail::assertNotSent(PropertyReviewVerificationCodeMail::class);
});

test('property review verification code sending is rate limited', function () {
    Mail::fake();

    $profile = propertyReviewInvitationProfile();

    $invitation = propertyReviewInvitation($profile);

    $component = Livewire::test('reviews.show', [
        'token' => $invitation->token,
    ]);

    for ($attempt = 1; $attempt <= 3; $attempt++) {
        $component->call('sendVerificationCode');
    }

    Mail::assertSent(
        PropertyReviewVerificationCodeMail::class,
        3
    );

    $component
        ->call('sendVerificationCode')
        ->assertHasErrors(['verificationCode']);

    Mail::assertSent(
        PropertyReviewVerificationCodeMail::class,
        3
    );
});

test('property review verification code guessing is rate limited', function () {
    $profile = propertyReviewInvitationProfile();

    $invitation = propertyReviewInvitation($profile, [
        'verification_code_hash' => Hash::make('123456'),
        'verification_expires_at' => now()->addMinutes(10),
    ]);

    $component = Livewire::test('reviews.show', [
        'token' => $invitation->token,
    ]);

    for ($attempt = 1; $attempt <= 5; $attempt++) {
        $component
            ->set('verificationCode', '654321')
            ->call('verifyCode')
            ->assertHasErrors(['verificationCode']);
    }

    expect($invitation->fresh()->email_verified_at)->toBeNull();

    $component
        ->set('verificationCode', '123456')
        ->call('verifyCode')
        ->assertHasErrors(['verificationCode']);

    expect($invitation->fresh()->email_verified_at)->toBeNull();
});

test('member property review invitation creation is rate limited', function () {
    Mail::fake();

    $user = propertyReviewInvitationUser();
    $profile = propertyReviewInvitationProfile($user);

    $this->actingAs($user);

    for ($attempt = 1; $attempt <= 5; $attempt++) {
        $this->post(
            route('account.review-invitations.store', $profile),
            [
                'reviewer_email' => "property-owner-{$attempt}@example.com",
            ]
        )->assertRedirect();
    }

    expect(PropertyReviewInvitation::count())->toBe(5);

    Mail::assertSent(
        PropertyReviewInvitationMail::class,
        5
    );

    $this->post(
        route('account.review-invitations.store', $profile),
        [
            'reviewer_email' => 'property-owner-6@example.com',
        ]
    )->assertSessionHasErrors(['reviewer_email']);

    expect(PropertyReviewInvitation::count())->toBe(5);

    Mail::assertSent(
        PropertyReviewInvitationMail::class,
        5
    );
});

test('property review invitation resending is rate limited', function () {
    Mail::fake();

    $user = propertyReviewInvitationUser();
    $profile = propertyReviewInvitationProfile($user);

    $invitation = propertyReviewInvitation($profile, [
        'reviewer_email' => 'property-owner@example.com',
    ]);

    $this->actingAs($user);

    for ($attempt = 1; $attempt <= 3; $attempt++) {
        $this->post(
            route('account.review-invitations.resend', $invitation)
        )->assertRedirect();
    }

    Mail::assertSent(
        PropertyReviewInvitationMail::class,
        3
    );

    $this->post(
        route('account.review-invitations.resend', $invitation)
    )->assertSessionHasErrors(['reviewer_email']);

    Mail::assertSent(
        PropertyReviewInvitationMail::class,
        3
    );
});

test('rate limited property review invitation resend does not mutate invitation', function () {
    Mail::fake();

    $user = propertyReviewInvitationUser();
    $profile = propertyReviewInvitationProfile($user);

    $invitation = propertyReviewInvitation($profile, [
        'reviewer_email' => 'property-owner@example.com',
    ]);

    $this->actingAs($user);

    for ($attempt = 1; $attempt <= 3; $attempt++) {
        $this->post(
            route('account.review-invitations.resend', $invitation)
        )->assertRedirect();
    }

    $invitation->refresh();

    $token = $invitation->token;
    $expiresAt = $invitation->expires_at?->toDateTimeString();
    $verificationCodeHash = $invitation->verification_code_hash;
    $verificationExpiresAt = $invitation->verification_expires_at?->toDateTimeString();
    $emailVerifiedAt = $invitation->email_verified_at?->toDateTimeString();

    $this->post(
        route('account.review-invitations.resend', $invitation)
    )->assertSessionHasErrors(['reviewer_email']);

    $invitation->refresh();

    expect($invitation->token)->toBe($token)
        ->and($invitation->expires_at?->toDateTimeString())->toBe($expiresAt)
        ->and($invitation->verification_code_hash)->toBe($verificationCodeHash)
        ->and($invitation->verification_expires_at?->toDateTimeString())
        ->toBe($verificationExpiresAt)
        ->and($invitation->email_verified_at?->toDateTimeString())
        ->toBe($emailVerifiedAt);

    Mail::assertSent(
        PropertyReviewInvitationMail::class,
        3
    );
});
