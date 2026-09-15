<?php

use App\Mail\PropertyReviewInvitationMail;
use App\Models\MemberProfile;
use App\Models\MembershipEnforcement;
use App\Models\PropertyReviewInvitation;
use App\Models\User;
use Illuminate\Support\Facades\Mail;

function goodStandingUser(array $attributes = []): User
{
    return User::factory()->create(array_merge([
        'membership_status' => 'active',
        'member_since' => now()->toDateString(),
        'ethics_agreed_at' => now(),
        'best_practices_agreed_at' => now(),
    ], $attributes));
}

function goodStandingProfile(User $user): MemberProfile
{
    return MemberProfile::factory()->for($user)->create([
        'directory_visible' => true,
        'profile_name' => 'Trust System Test Member',
        'username' => 'trust-system-test-member',
    ]);
}

function goodStandingEnforcement(User $user, string $action): MembershipEnforcement
{
    return MembershipEnforcement::create([
        'user_id' => $user->id,
        'action' => $action,
        'status' => 'active',
        'reason' => 'Trust-system presentation test.',
        'starts_at' => now()->subMinute(),
    ]);
}

test('active member in good standing is presented as in good standing', function () {
    $member = goodStandingUser();
    $profile = goodStandingProfile($member);

    expect($member->isInGoodStanding())->toBeTrue();

    $this->get(route('member-profiles.show', $profile))
        ->assertOk()
        ->assertSeeText('Member in Good Standing')
        ->assertDontSeeText('Good Standing Not Current');

    $this->get(route('member-profiles.card', $profile))
        ->assertOk()
        ->assertSeeText('Member in Good Standing')
        ->assertDontSeeText('Good Standing Not Current');
});

test('probation member remains publicly visible but is never presented as in good standing', function () {
    $member = goodStandingUser();
    $profile = goodStandingProfile($member);
    goodStandingEnforcement($member, 'probation');

    expect($member->isInGoodStanding())->toBeFalse();

    $this->get(route('member-profiles.show', $profile))
        ->assertOk()
        ->assertSeeText('Good Standing Not Current')
        ->assertDontSeeText('This member currently meets IRDI’s membership and accountability requirements.');

    $this->get(route('member-profiles.card', $profile))
        ->assertOk()
        ->assertSeeText('Good Standing Not Current');

    $this->get(route('directory'))
        ->assertOk()
        ->assertSeeText('Trust System Test Member')
        ->assertSeeText('Good Standing Not Current');

    $this->actingAs($member)
        ->get(route('account'))
        ->assertOk()
        ->assertSeeText('Good Standing Not Current');
});

test('administrative review removes current good standing without hiding active public profile', function () {
    $member = goodStandingUser();
    $profile = goodStandingProfile($member);
    goodStandingEnforcement($member, 'administrative_hold');

    expect($member->isInGoodStanding())->toBeFalse();

    $this->get(route('member-profiles.show', $profile))
        ->assertOk()
        ->assertSeeText('Good Standing Not Current')
        ->assertDontSeeText('This member currently meets IRDI’s membership and accountability requirements.');

    $this->get(route('directory'))
        ->assertOk()
        ->assertSeeText('Trust System Test Member')
        ->assertSeeText('Good Standing Not Current');
});

test('member not in good standing cannot create a property owner feedback invitation', function (string $action) {
    Mail::fake();

    $member = goodStandingUser();
    $profile = goodStandingProfile($member);
    goodStandingEnforcement($member, $action);

    $this->actingAs($member)
        ->post(route('account.review-invitations.store', $profile), [
            'reviewer_email' => 'property-owner@example.com',
        ])
        ->assertForbidden();

    expect(PropertyReviewInvitation::query()->count())->toBe(0);
    Mail::assertNothingSent();
})->with([
    'probation' => 'probation',
    'administrative review' => 'administrative_hold',
]);

test('member not in good standing cannot resend a property owner feedback invitation', function () {
    Mail::fake();

    $member = goodStandingUser();
    $profile = goodStandingProfile($member);

    $invitation = PropertyReviewInvitation::create([
        'member_profile_id' => $profile->id,
        'token' => str_repeat('a', 64),
        'reviewer_email' => 'property-owner@example.com',
        'expires_at' => now()->addDays(30),
    ]);

    goodStandingEnforcement($member, 'probation');

    $originalToken = $invitation->token;

    $this->actingAs($member)
        ->post(route('account.review-invitations.resend', $invitation))
        ->assertForbidden();

    expect($invitation->fresh()->token)->toBe($originalToken);
    Mail::assertNothingSent();
});

test('member in good standing can create a property owner feedback invitation', function () {
    Mail::fake();

    $member = goodStandingUser();
    $profile = goodStandingProfile($member);

    $this->actingAs($member)
        ->from(route('account'))
        ->post(route('account.review-invitations.store', $profile), [
            'reviewer_email' => 'property-owner@example.com',
        ])
        ->assertRedirect(route('account'));

    $this->assertDatabaseHas('property_review_invitations', [
        'member_profile_id' => $profile->id,
        'reviewer_email' => 'property-owner@example.com',
    ]);

    Mail::assertSent(PropertyReviewInvitationMail::class);
});
