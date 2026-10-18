<?php

use App\Models\MemberProfile;
use App\Models\PropertyReview;
use App\Models\PropertyReviewInvitation;
use App\Models\User;
use Illuminate\Support\Str;
use Livewire\Livewire;

test('public introduction links to the same public profile a landowner can view', function () {
    $user = User::factory()->create(['membership_status' => 'active']);
    $profile = MemberProfile::factory()->for($user)->create([
        'directory_visible' => true,
        'personal_information_hidden' => false,
        'profile_name' => 'Permission Journey Detectorist',
    ]);

    $profileUrl = route('member-profiles.show', $profile);

    $this->get(route('member-profiles.introduction', $profile))
        ->assertOk()
        ->assertSee('Permission Journey Detectorist')
        ->assertSee($profileUrl)
        ->assertSee('Print or Save as PDF');

    $this->get($profileUrl)->assertOk();
});

test('private or suspended profiles cannot be introduced to landowners', function () {
    $user = User::factory()->create(['membership_status' => 'active']);
    $profile = MemberProfile::factory()->for($user)->create([
        'directory_visible' => false,
        'personal_information_hidden' => false,
    ]);

    $this->get(route('member-profiles.introduction', $profile))->assertNotFound();
    $this->get(route('member-profiles.show', $profile))->assertNotFound();

    $profile->update(['directory_visible' => true]);
    $user->update(['membership_status' => 'suspended']);

    $this->get(route('member-profiles.introduction', $profile))->assertNotFound();
    $this->get(route('member-profiles.show', $profile))->assertNotFound();
});

test('verified landowner can submit one review after viewing a public profile', function () {
    $user = User::factory()->create(['membership_status' => 'active']);
    $profile = MemberProfile::factory()->for($user)->create([
        'directory_visible' => true,
        'personal_information_hidden' => false,
    ]);

    $this->get(route('member-profiles.show', $profile))->assertOk();

    $invitation = PropertyReviewInvitation::create([
        'member_profile_id' => $profile->id,
        'token' => Str::random(64),
        'reviewer_email' => 'landowner-journey@example.com',
        'email_verified_at' => now(),
        'expires_at' => now()->addDays(30),
    ]);

    $this->get(route('property-reviews.show', ['token' => $invitation->token]))->assertOk();

    Livewire::test('reviews.show', ['token' => $invitation->token])
        ->set('overallExperience', '5')
        ->set('respectForProperty', '5')
        ->set('communicationCourtesy', '5')
        ->set('careOfProperty', '5')
        ->set('propertyLeftSatisfactorily', 'yes')
        ->set('wouldAllowReturn', 'yes')
        ->set('privateConcern', 'no')
        ->call('submitReview')
        ->assertHasNoErrors();

    $review = PropertyReview::query()->sole();
    expect($review->member_profile_id)->toBe($profile->id)
        ->and($review->care_of_property)->toBe(5)
        ->and($invitation->fresh()->used_at)->not->toBeNull();

    Livewire::test('reviews.show', ['token' => $invitation->token])
        ->call('submitReview');

    expect(PropertyReview::count())->toBe(1);
});
