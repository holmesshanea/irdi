<?php

use App\Models\MemberProfile;
use App\Models\PropertyReview;
use App\Models\PropertyReviewInvitation;
use App\Models\User;
use Illuminate\Support\Str;
use Livewire\Livewire;

function approvalReview(MemberProfile $profile): PropertyReview
{
    $invitation = PropertyReviewInvitation::create([
        'member_profile_id' => $profile->id,
        'token' => Str::random(64),
        'reviewer_email' => Str::random(10).'@example.com',
        'email_verified_at' => now(),
        'expires_at' => now()->addDays(30),
        'used_at' => now(),
    ]);

    return PropertyReview::create([
        'member_profile_id' => $profile->id,
        'property_review_invitation_id' => $invitation->id,
        'reviewer_email' => $invitation->reviewer_email,
        'overall_experience' => 5,
        'respect_for_property' => 5,
        'communication_courtesy' => 5,
        'care_of_property' => 5,
        'property_left_satisfactorily' => true,
        'would_allow_return' => true,
        'private_concern' => false,
    ]);
}

test('new landowner feedback remains excluded from public statistics until administrator approves', function () {
    $member = User::factory()->create(['membership_status' => 'active']);
    $profile = MemberProfile::factory()->for($member)->create(['directory_visible' => true]);
    $review = approvalReview($profile);

    expect($review->approved_at)->toBeNull()
        ->and($profile->propertyReviews()->visible()->count())->toBe(0);

    $admin = User::factory()->create(['is_admin' => true]);
    Livewire::actingAs($admin)->test('admin.reviews.index')
        ->call('markReviewed', $review->id)
        ->assertHasNoErrors();

    expect($review->fresh()->approved_at)->not->toBeNull()
        ->and($profile->propertyReviews()->visible()->count())->toBe(1);
});

test('hidden and unapproved feedback is never counted publicly', function () {
    $member = User::factory()->create(['membership_status' => 'active']);
    $profile = MemberProfile::factory()->for($member)->create(['directory_visible' => true]);
    $review = approvalReview($profile);
    $review->hidden_at = now();
    $review->save();

    expect($review->fresh()->hidden_at)->not->toBeNull();

    $admin = User::factory()->create(['is_admin' => true]);
    Livewire::actingAs($admin)->test('admin.reviews.index')
        ->call('markReviewed', $review->id)
        ->assertHasNoErrors();

    expect($review->fresh()->approved_at)->toBeNull()
        ->and($profile->propertyReviews()->visible()->count())->toBe(0);
});
