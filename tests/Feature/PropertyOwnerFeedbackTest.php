<?php

use App\Models\MemberProfile;
use App\Models\PropertyReview;
use App\Models\PropertyReviewInvitation;
use App\Models\User;
use Illuminate\Support\Str;
use Livewire\Livewire;

function propertyFeedbackUser(array $attributes = []): User
{
    return User::factory()->create(array_merge([
        'membership_status' => 'active',
        'member_since' => now()->toDateString(),
        'ethics_agreed_at' => now(),
        'best_practices_agreed_at' => now(),
    ], $attributes));
}

function propertyFeedbackProfile(?User $user = null): MemberProfile
{
    $user ??= propertyFeedbackUser();

    return MemberProfile::factory()->for($user)->create([
        'directory_visible' => true,
    ]);
}

function propertyFeedbackInvitation(
    MemberProfile $profile,
    string $email,
    array $attributes = []
): PropertyReviewInvitation {
    return PropertyReviewInvitation::create(array_merge([
        'member_profile_id' => $profile->id,
        'token' => Str::random(64),
        'reviewer_email' => $email,
        'email_verified_at' => now(),
        'expires_at' => now()->addDays(30),
    ], $attributes));
}

function propertyFeedbackLegacyReview(
    MemberProfile $profile,
    string $email,
    int $respect,
    int $communication,
    int $care,
    bool $wouldAllowReturn = true
): PropertyReview {
    $invitation = propertyFeedbackInvitation($profile, $email, [
        'used_at' => now(),
    ]);

    $review = PropertyReview::create([
        'member_profile_id' => $profile->id,
        'property_review_invitation_id' => $invitation->id,
        'reviewer_email' => $email,
        'overall_experience' => null,
        'respect_for_property' => $respect,
        'communication_courtesy' => $communication,
        'care_of_property' => $care,
        'property_left_satisfactorily' => null,
        'would_allow_return' => $wouldAllowReturn,
        'comments' => 'Legacy review comment.',
        'private_concern' => null,
        'private_concern_details' => null,
    ]);

    // Historical fixture: this review was approved before public display.
    $review->approved_at = now();
    $review->save();

    return $review;
}

function propertyFeedbackNewReview(
    MemberProfile $profile,
    string $email,
    int $overall,
    array $attributes = []
): PropertyReview {
    $invitation = propertyFeedbackInvitation($profile, $email, [
        'used_at' => now(),
    ]);

    $review = PropertyReview::create(array_merge([
        'member_profile_id' => $profile->id,
        'property_review_invitation_id' => $invitation->id,
        'reviewer_email' => $email,
        'overall_experience' => $overall,
        'respect_for_property' => 5,
        'communication_courtesy' => 5,
        'care_of_property' => null,
        'property_left_satisfactorily' => true,
        'would_allow_return' => true,
        'comments' => 'New questionnaire review comment.',
        'private_concern' => false,
        'private_concern_details' => null,
    ], $attributes));

    // This helper represents a previously approved review, not a fresh submission.
    $review->approved_at = now();
    $review->save();

    return $review;
}

test('new property owner questionnaire stores new fields without fabricating legacy care rating', function () {
    $profile = propertyFeedbackProfile();

    $invitation = propertyFeedbackInvitation(
        $profile,
        'new-reviewer@example.com'
    );

    Livewire::test('reviews.show', ['token' => $invitation->token])
        ->set('overallExperience', '4')
        ->set('respectForProperty', '5')
        ->set('communicationCourtesy', '4')
        ->set('propertyLeftSatisfactorily', 'yes')
        ->set('wouldAllowReturn', 'yes')
        ->set('comments', '  The member was courteous and careful.  ')
        ->set('privateConcern', 'yes')
        ->set('privateConcernDetails', '  Please have IRDI staff contact me privately.  ')
        ->call('submitReview')
        ->assertHasNoErrors();

    $review = PropertyReview::query()->sole();

    expect($review->overall_experience)->toBe(4)
        ->and($review->respect_for_property)->toBe(5)
        ->and($review->communication_courtesy)->toBe(4)
        ->and($review->care_of_property)->toBeNull()
        ->and($review->property_left_satisfactorily)->toBeTrue()
        ->and($review->would_allow_return)->toBeTrue()
        ->and($review->comments)->toBe('The member was courteous and careful.')
        ->and($review->private_concern)->toBeTrue()
        ->and($review->private_concern_details)->toBe('Please have IRDI staff contact me privately.')
        ->and($invitation->fresh()->used_at)->not->toBeNull();
});

test('private concern details are required when property owner selects yes', function () {
    $profile = propertyFeedbackProfile();

    $invitation = propertyFeedbackInvitation(
        $profile,
        'concern-validation@example.com'
    );

    Livewire::test('reviews.show', ['token' => $invitation->token])
        ->set('overallExperience', '3')
        ->set('respectForProperty', '4')
        ->set('communicationCourtesy', '3')
        ->set('propertyLeftSatisfactorily', 'yes')
        ->set('wouldAllowReturn', 'no')
        ->set('privateConcern', 'yes')
        ->set('privateConcernDetails', '')
        ->call('submitReview')
        ->assertHasErrors(['privateConcernDetails']);

    expect(PropertyReview::count())->toBe(0)
        ->and($invitation->fresh()->used_at)->toBeNull();
});

test('public profile rating combines legacy calculated ratings with new overall experience ratings', function () {
    $profile = propertyFeedbackProfile();

    // Legacy rating = (5 + 4 + 3) / 3 = 4.0
    propertyFeedbackLegacyReview(
        $profile,
        'legacy-one@example.com',
        5,
        4,
        3
    );

    // Legacy rating = (3 + 3 + 3) / 3 = 3.0
    propertyFeedbackLegacyReview(
        $profile,
        'legacy-two@example.com',
        3,
        3,
        3,
        false
    );

    // New questionnaire uses explicit overall experience = 5.0.
    propertyFeedbackNewReview(
        $profile,
        'new-one@example.com',
        5
    );

    // Mixed average = (4 + 3 + 5) / 3 = 4.0.
    Livewire::test('member-profiles.show', ['profile' => $profile])
        ->assertSet('propertyReviewCount', 3)
        ->assertSet('wouldAllowReturnCount', 2)
        ->assertSet('propertyReviewRating', 4.0);
});

test('member review page supports legacy and new questionnaires without exposing confidential concerns', function () {
    $member = propertyFeedbackUser();
    $profile = propertyFeedbackProfile($member);

    propertyFeedbackLegacyReview(
        $profile,
        'legacy-member-view@example.com',
        4,
        4,
        5
    );

    propertyFeedbackNewReview(
        $profile,
        'new-member-view@example.com',
        2,
        [
            'property_left_satisfactorily' => false,
            'would_allow_return' => false,
            'private_concern' => true,
            'private_concern_details' => 'CONFIDENTIAL MEMBER MUST NEVER SEE THIS',
        ]
    );

    $this->actingAs($member)
        ->get(route('account.reviews'))
        ->assertOk()
        ->assertSeeText('Overall Experience')
        ->assertSeeText('Property Left Satisfactorily')
        ->assertSeeText('Care of Property')
        ->assertDontSeeText('Private Concern')
        ->assertDontSeeText('CONFIDENTIAL MEMBER MUST NEVER SEE THIS');
});

test('public member profile never exposes confidential property owner concern', function () {
    $profile = propertyFeedbackProfile();

    propertyFeedbackNewReview(
        $profile,
        'public-confidentiality@example.com',
        4,
        [
            'private_concern' => true,
            'private_concern_details' => 'CONFIDENTIAL PUBLIC MUST NEVER SEE THIS',
        ]
    );

    $this->get(route('member-profiles.show', $profile))
        ->assertOk()
        ->assertDontSeeText('Private Concern')
        ->assertDontSeeText('CONFIDENTIAL PUBLIC MUST NEVER SEE THIS');
});

test('authorized staff can see confidential concern on admin review detail', function () {
    $administrator = propertyFeedbackUser([
        'is_admin' => true,
    ]);

    $profile = propertyFeedbackProfile();

    $review = propertyFeedbackNewReview(
        $profile,
        'admin-confidentiality@example.com',
        3,
        [
            'private_concern' => true,
            'private_concern_details' => 'CONFIDENTIAL STAFF REVIEW DETAILS',
        ]
    );

    $this->actingAs($administrator)
        ->get(route('admin.reviews.show', $review))
        ->assertOk()
        ->assertSeeText('Private Concern')
        ->assertSeeText('Confidential — Authorized IRDI Staff Only')
        ->assertSeeText('CONFIDENTIAL STAFF REVIEW DETAILS');
});

test('ordinary member cannot access admin review detail containing confidential concern', function () {
    $member = propertyFeedbackUser();
    $profile = propertyFeedbackProfile();

    $review = propertyFeedbackNewReview(
        $profile,
        'unauthorized-confidentiality@example.com',
        4,
        [
            'private_concern' => true,
            'private_concern_details' => 'CONFIDENTIAL ADMIN ONLY',
        ]
    );

    $this->actingAs($member)
        ->get(route('admin.reviews.show', $review))
        ->assertForbidden();
});

test('hidden reviews are excluded from public property owner statistics', function () {
    $profile = propertyFeedbackProfile();

    propertyFeedbackNewReview(
        $profile,
        'visible-one@example.com',
        5
    );

    propertyFeedbackNewReview(
        $profile,
        'visible-two@example.com',
        5
    );

    propertyFeedbackNewReview(
        $profile,
        'visible-three@example.com',
        5
    );

    $hiddenReview = propertyFeedbackNewReview(
        $profile,
        'hidden-low-rating@example.com',
        1,
        [
            'would_allow_return' => false,
        ]
    );

    $hiddenReview->hidden_at = now();
    $hiddenReview->save();

    Livewire::test('member-profiles.show', ['profile' => $profile])
        ->assertSet('propertyReviewCount', 3)
        ->assertSet('wouldAllowReturnCount', 3)
        ->assertSet('propertyReviewRating', 5.0);
});

test('landowner can rate observed property care against the IRDI ethics standard', function () {
    $profile = propertyFeedbackProfile();
    $invitation = propertyFeedbackInvitation($profile, 'property-care@example.com');

    Livewire::test('reviews.show', ['token' => $invitation->token])
        ->set('overallExperience', '4')
        ->set('respectForProperty', '5')
        ->set('communicationCourtesy', '4')
        ->set('careOfProperty', '5')
        ->set('propertyLeftSatisfactorily', 'yes')
        ->set('wouldAllowReturn', 'yes')
        ->set('privateConcern', 'no')
        ->call('submitReview')
        ->assertHasNoErrors();

    expect(PropertyReview::query()->sole()->care_of_property)->toBe(5);
});

test('landowner property care rating must be between one and five when provided', function () {
    $profile = propertyFeedbackProfile();
    $invitation = propertyFeedbackInvitation($profile, 'invalid-care@example.com');

    Livewire::test('reviews.show', ['token' => $invitation->token])
        ->set('overallExperience', '4')
        ->set('respectForProperty', '5')
        ->set('communicationCourtesy', '4')
        ->set('careOfProperty', '6')
        ->set('propertyLeftSatisfactorily', 'yes')
        ->set('wouldAllowReturn', 'yes')
        ->set('privateConcern', 'no')
        ->call('submitReview')
        ->assertHasErrors(['careOfProperty']);

    expect(PropertyReview::count())->toBe(0);
});
