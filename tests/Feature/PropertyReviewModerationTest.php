<?php

use App\Models\MemberProfile;
use App\Models\PropertyReview;
use App\Models\PropertyReviewInvitation;
use App\Models\PropertyReviewModeration;
use App\Models\StaffActivityLog;
use App\Models\User;
use Illuminate\Support\Str;
use Livewire\Livewire;

function propertyReviewModerationUser(array $attributes = []): User
{
    return User::factory()->create(array_merge([
        'membership_status' => 'active',
        'member_since' => now()->toDateString(),
        'ethics_agreed_at' => now(),
        'best_practices_agreed_at' => now(),
        'is_admin' => false,
        'is_super_admin' => false,
        'is_moderator' => false,
    ], $attributes));
}

function propertyReviewModerationProfile(?User $user = null): MemberProfile
{
    $user ??= propertyReviewModerationUser();

    return MemberProfile::factory()->for($user)->create([
        'directory_visible' => true,
    ]);
}

function propertyReviewModerationReview(
    MemberProfile $profile,
    array $attributes = []
): PropertyReview {
    $invitation = PropertyReviewInvitation::create([
        'member_profile_id' => $profile->id,
        'token' => Str::random(64),
        'reviewer_email' => 'property-owner-'.Str::random(8).'@example.com',
        'email_verified_at' => now(),
        'expires_at' => now()->addDays(30),
        'used_at' => now(),
    ]);

    $review = PropertyReview::create([
        'member_profile_id' => $profile->id,
        'property_review_invitation_id' => $invitation->id,
        'reviewer_email' => $invitation->reviewer_email,
        'overall_experience' => $attributes['overall_experience'] ?? 4,
        'respect_for_property' => $attributes['respect_for_property'] ?? 5,
        'communication_courtesy' => $attributes['communication_courtesy'] ?? 4,
        'care_of_property' => $attributes['care_of_property'] ?? null,
        'property_left_satisfactorily' => $attributes['property_left_satisfactorily'] ?? true,
        'would_allow_return' => $attributes['would_allow_return'] ?? true,
        'comments' => $attributes['comments'] ?? 'Property owner moderation lifecycle test review.',
        'private_concern' => $attributes['private_concern'] ?? false,
        'private_concern_details' => $attributes['private_concern_details'] ?? null,
    ]);

    if (array_key_exists('hidden_at', $attributes)) {
        $review->hidden_at = $attributes['hidden_at'];
    }

    if (array_key_exists('moderation_note', $attributes)) {
        $review->moderation_note = $attributes['moderation_note'];
    }

    if ($review->isDirty()) {
        $review->save();
    }

    return $review;
}

test('administrator can hide visible property review', function () {
    $this->freezeTime();

    $administrator = propertyReviewModerationUser([
        'is_admin' => true,
    ]);

    $member = propertyReviewModerationUser();
    $profile = propertyReviewModerationProfile($member);
    $review = propertyReviewModerationReview($profile);

    $this->actingAs($administrator);

    Livewire::test('admin.reviews.show', ['review' => $review])
        ->set('moderationNote', 'This review requires moderation and should be hidden from public statistics.')
        ->call('hideReview')
        ->assertHasNoErrors();

    $review->refresh();

    expect($review->hidden_at?->toDateTimeString())
        ->toBe(now()->toDateTimeString())
        ->and($review->moderation_note)
        ->toBe('This review requires moderation and should be hidden from public statistics.');

    $moderation = PropertyReviewModeration::query()
        ->where('property_review_id', $review->id)
        ->where('user_id', $administrator->id)
        ->where('action', 'hidden')
        ->sole();

    expect($moderation->note)
        ->toBe('This review requires moderation and should be hidden from public statistics.');

    $activity = StaffActivityLog::query()
        ->where('actor_id', $administrator->id)
        ->where('target_user_id', $member->id)
        ->where('action', 'review_hidden')
        ->sole();

    expect($activity->subject_type)
        ->toBe($moderation->getMorphClass())
        ->and($activity->subject_id)
        ->toBe($moderation->id)
        ->and($activity->description)
        ->toBe('Hid property owner review #'.$review->id.'.')
        ->and($activity->metadata['property_review_id'])
        ->toBe($review->id)
        ->and($activity->metadata['reason'])
        ->toBe('This review requires moderation and should be hidden from public statistics.');
});

test('administrator can restore hidden property review', function () {
    $administrator = propertyReviewModerationUser([
        'is_admin' => true,
    ]);

    $member = propertyReviewModerationUser();
    $profile = propertyReviewModerationProfile($member);

    $review = propertyReviewModerationReview($profile, [
        'hidden_at' => now()->subDay(),
        'moderation_note' => 'Original reason this review was hidden.',
    ]);

    $this->actingAs($administrator);

    Livewire::test('admin.reviews.show', ['review' => $review])
        ->set('restoreNote', 'Administrative review determined this property review may be restored.')
        ->call('restoreReview')
        ->assertHasNoErrors();

    $review->refresh();

    expect($review->hidden_at)->toBeNull()
        ->and($review->moderation_note)
        ->toBe('Original reason this review was hidden.');

    $moderation = PropertyReviewModeration::query()
        ->where('property_review_id', $review->id)
        ->where('user_id', $administrator->id)
        ->where('action', 'restored')
        ->sole();

    expect($moderation->note)
        ->toBe('Administrative review determined this property review may be restored.');

    $activity = StaffActivityLog::query()
        ->where('actor_id', $administrator->id)
        ->where('target_user_id', $member->id)
        ->where('action', 'review_restored')
        ->sole();

    expect($activity->subject_type)
        ->toBe($moderation->getMorphClass())
        ->and($activity->subject_id)
        ->toBe($moderation->id)
        ->and($activity->metadata['property_review_id'])
        ->toBe($review->id);
});

test('stale component cannot hide property review that was already hidden elsewhere', function () {
    $administrator = propertyReviewModerationUser([
        'is_admin' => true,
    ]);

    $member = propertyReviewModerationUser();
    $profile = propertyReviewModerationProfile($member);
    $review = propertyReviewModerationReview($profile);

    $this->actingAs($administrator);

    $component = Livewire::test('admin.reviews.show', [
        'review' => $review,
    ]);

    // Simulate another moderation action changing the authoritative
    // database state after this Livewire component was mounted.
    $authoritativeReview = PropertyReview::query()->findOrFail($review->id);

    $authoritativeReview->forceFill([
        'hidden_at' => now(),
        'moderation_note' => 'This review was already hidden by another moderation action.',
    ])->save();

    $component
        ->set(
            'moderationNote',
            'This stale component must not create a second hide moderation action.'
        )
        ->call('hideReview');

    $review->refresh();

    expect($review->moderation_note)
        ->toBe('This review was already hidden by another moderation action.');

    expect(
        PropertyReviewModeration::query()
            ->where('property_review_id', $review->id)
            ->where('action', 'hidden')
            ->exists()
    )->toBeFalse();

    expect(
        StaffActivityLog::query()
            ->where('target_user_id', $member->id)
            ->where('action', 'review_hidden')
            ->exists()
    )->toBeFalse();
});

test('already hidden property review cannot be hidden again', function () {
    $administrator = propertyReviewModerationUser([
        'is_admin' => true,
    ]);

    $member = propertyReviewModerationUser();
    $profile = propertyReviewModerationProfile($member);

    $review = propertyReviewModerationReview($profile, [
        'hidden_at' => now()->subDay(),
        'moderation_note' => 'Original moderation reason.',
    ]);

    $originalHiddenAt = $review->hidden_at?->toDateTimeString();
    $originalNote = $review->moderation_note;

    $this->actingAs($administrator);

    Livewire::test('admin.reviews.show', ['review' => $review])
        ->set('moderationNote', 'This duplicate hide action must not create another moderation record.')
        ->call('hideReview')
        ->assertHasErrors(['reviewStatus']);

    $review->refresh();

    expect($review->hidden_at?->toDateTimeString())
        ->toBe($originalHiddenAt)
        ->and($review->moderation_note)
        ->toBe($originalNote);

    expect(
        PropertyReviewModeration::query()
            ->where('property_review_id', $review->id)
            ->where('action', 'hidden')
            ->exists()
    )->toBeFalse();

    expect(
        StaffActivityLog::query()
            ->where('target_user_id', $member->id)
            ->where('action', 'review_hidden')
            ->exists()
    )->toBeFalse();
});

test('visible property review cannot be restored', function () {
    $administrator = propertyReviewModerationUser([
        'is_admin' => true,
    ]);

    $member = propertyReviewModerationUser();
    $profile = propertyReviewModerationProfile($member);
    $review = propertyReviewModerationReview($profile);

    $this->actingAs($administrator);

    Livewire::test('admin.reviews.show', ['review' => $review])
        ->set('restoreNote', 'This invalid restoration must not create moderation history.')
        ->call('restoreReview')
        ->assertHasErrors(['reviewStatus']);

    expect($review->fresh()->hidden_at)->toBeNull();

    expect(
        PropertyReviewModeration::query()
            ->where('property_review_id', $review->id)
            ->where('action', 'restored')
            ->exists()
    )->toBeFalse();

    expect(
        StaffActivityLog::query()
            ->where('target_user_id', $member->id)
            ->where('action', 'review_restored')
            ->exists()
    )->toBeFalse();
});

test('hide review requires valid moderation note', function () {
    $administrator = propertyReviewModerationUser([
        'is_admin' => true,
    ]);

    $profile = propertyReviewModerationProfile();
    $review = propertyReviewModerationReview($profile);

    $this->actingAs($administrator);

    Livewire::test('admin.reviews.show', ['review' => $review])
        ->set('moderationNote', 'short')
        ->call('hideReview')
        ->assertHasErrors(['moderationNote']);

    expect($review->fresh()->hidden_at)->toBeNull();

    expect(
        PropertyReviewModeration::query()
            ->where('property_review_id', $review->id)
            ->exists()
    )->toBeFalse();
});

test('restore review requires valid restore note', function () {
    $administrator = propertyReviewModerationUser([
        'is_admin' => true,
    ]);

    $profile = propertyReviewModerationProfile();

    $review = propertyReviewModerationReview($profile, [
        'hidden_at' => now()->subDay(),
        'moderation_note' => 'Original moderation reason.',
    ]);

    $this->actingAs($administrator);

    Livewire::test('admin.reviews.show', ['review' => $review])
        ->set('restoreNote', 'short')
        ->call('restoreReview')
        ->assertHasErrors(['restoreNote']);

    expect($review->fresh()->hidden_at)->not->toBeNull();

    expect(
        PropertyReviewModeration::query()
            ->where('property_review_id', $review->id)
            ->exists()
    )->toBeFalse();
});

test('property review can complete multiple hide and restore moderation cycles', function () {
    $administrator = propertyReviewModerationUser([
        'is_admin' => true,
    ]);

    $profile = propertyReviewModerationProfile();
    $review = propertyReviewModerationReview($profile);

    $this->actingAs($administrator);

    $component = Livewire::test('admin.reviews.show', ['review' => $review]);

    $component
        ->set('moderationNote', 'First moderation cycle requires hiding this property owner review.')
        ->call('hideReview')
        ->assertHasNoErrors()
        ->set('restoreNote', 'First moderation cycle is complete and the review may be restored.')
        ->call('restoreReview')
        ->assertHasNoErrors()
        ->set('moderationNote', 'Second moderation cycle requires hiding this property owner review again.')
        ->call('hideReview')
        ->assertHasNoErrors()
        ->set('restoreNote', 'Second moderation cycle is complete and the review may be restored again.')
        ->call('restoreReview')
        ->assertHasNoErrors();

    expect($review->fresh()->hidden_at)->toBeNull();

    expect(
        PropertyReviewModeration::query()
            ->where('property_review_id', $review->id)
            ->where('action', 'hidden')
            ->count()
    )->toBe(2);

    expect(
        PropertyReviewModeration::query()
            ->where('property_review_id', $review->id)
            ->where('action', 'restored')
            ->count()
    )->toBe(2);
});

test('moderator can hide and restore property review', function () {
    $moderator = propertyReviewModerationUser([
        'is_moderator' => true,
    ]);

    $profile = propertyReviewModerationProfile();
    $review = propertyReviewModerationReview($profile);

    $this->actingAs($moderator);

    $component = Livewire::test('admin.reviews.show', ['review' => $review]);

    $component
        ->set('moderationNote', 'Moderator determined this review should temporarily be hidden.')
        ->call('hideReview')
        ->assertHasNoErrors();

    expect($review->fresh()->hidden_at)->not->toBeNull();

    $component
        ->set('restoreNote', 'Moderator determined this review may now be restored publicly.')
        ->call('restoreReview')
        ->assertHasNoErrors();

    expect($review->fresh()->hidden_at)->toBeNull();

    $this->assertDatabaseHas('property_review_moderations', [
        'property_review_id' => $review->id,
        'user_id' => $moderator->id,
        'action' => 'hidden',
    ]);

    $this->assertDatabaseHas('property_review_moderations', [
        'property_review_id' => $review->id,
        'user_id' => $moderator->id,
        'action' => 'restored',
    ]);

});

test('hiding and restoring review updates public property owner statistics', function () {
    $administrator = propertyReviewModerationUser([
        'is_admin' => true,
    ]);

    $profile = propertyReviewModerationProfile();

    propertyReviewModerationReview($profile, [
        'overall_experience' => 5,
        'would_allow_return' => true,
    ]);

    propertyReviewModerationReview($profile, [
        'overall_experience' => 4,
        'would_allow_return' => true,
    ]);

    $review = propertyReviewModerationReview($profile, [
        'overall_experience' => 3,
        'would_allow_return' => false,
    ]);

    Livewire::test('member-profiles.show', ['profile' => $profile])
        ->assertSet('propertyReviewCount', 3)
        ->assertSet('wouldAllowReturnCount', 2)
        ->assertSet('propertyReviewRating', 4.0);

    $this->actingAs($administrator);

    Livewire::test('admin.reviews.show', ['review' => $review])
        ->set(
            'moderationNote',
            'This review is being hidden while administrative moderation is completed.'
        )
        ->call('hideReview')
        ->assertHasNoErrors();

    Livewire::test('member-profiles.show', ['profile' => $profile])
        ->assertSet('propertyReviewCount', 2)
        ->assertSet('wouldAllowReturnCount', 2)
        ->assertSet('propertyReviewRating', null);

    Livewire::test('admin.reviews.show', ['review' => $review->fresh()])
        ->set(
            'restoreNote',
            'Administrative moderation is complete and this review may be restored.'
        )
        ->call('restoreReview')
        ->assertHasNoErrors();

    Livewire::test('member-profiles.show', ['profile' => $profile])
        ->assertSet('propertyReviewCount', 3)
        ->assertSet('wouldAllowReturnCount', 2)
        ->assertSet('propertyReviewRating', 4.0);
});

test('review index cannot hide an already hidden property review', function () {
    $administrator = propertyReviewModerationUser([
        'is_admin' => true,
    ]);

    $member = propertyReviewModerationUser();
    $profile = propertyReviewModerationProfile($member);

    $review = propertyReviewModerationReview($profile, [
        'hidden_at' => now()->subHour(),
        'moderation_note' => 'This review was already hidden.',
    ]);

    $originalHiddenAt = $review->hidden_at;

    $this->actingAs($administrator);

    Livewire::test('admin.reviews.index')
        ->call('startHidingReview', $review->id)
        ->set(
            'moderationNote',
            'This duplicate hide action must not create another moderation event.'
        )
        ->call('hideReview');

    $review->refresh();

    expect($review->hidden_at?->toDateTimeString())
        ->toBe($originalHiddenAt?->toDateTimeString());

    expect(
        PropertyReviewModeration::query()
            ->where('property_review_id', $review->id)
            ->where('action', 'hidden')
            ->exists()
    )->toBeFalse();

    expect(
        StaffActivityLog::query()
            ->where('target_user_id', $member->id)
            ->where('action', 'review_hidden')
            ->exists()
    )->toBeFalse();
});

test('review index cannot restore an already visible property review', function () {
    $administrator = propertyReviewModerationUser([
        'is_admin' => true,
    ]);

    $member = propertyReviewModerationUser();
    $profile = propertyReviewModerationProfile($member);
    $review = propertyReviewModerationReview($profile);

    expect($review->hidden_at)->toBeNull();

    $this->actingAs($administrator);

    Livewire::test('admin.reviews.index')
        ->call('restoreReview', $review->id);

    $review->refresh();

    expect($review->hidden_at)->toBeNull();

    expect(
        PropertyReviewModeration::query()
            ->where('property_review_id', $review->id)
            ->where('action', 'restored')
            ->exists()
    )->toBeFalse();

    expect(
        StaffActivityLog::query()
            ->where('target_user_id', $member->id)
            ->where('action', 'review_restored')
            ->exists()
    )->toBeFalse();
});
