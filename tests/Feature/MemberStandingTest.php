<?php

use App\Models\MembershipEnforcement;
use App\Models\User;

function trustSystemMember(array $attributes = []): User
{
    return User::factory()->create(array_merge([
        'membership_status' => 'active',
        'member_since' => now()->toDateString(),
        'ethics_agreed_at' => now(),
        'best_practices_agreed_at' => now(),
    ], $attributes));
}

test('active member who accepted ethics and best practices is in good standing', function () {
    $member = trustSystemMember();

    expect($member->isInGoodStanding())->toBeTrue();
});

test('member who has not accepted the code of ethics is not in good standing', function () {
    $member = trustSystemMember([
        'ethics_agreed_at' => null,
    ]);

    expect($member->isInGoodStanding())->toBeFalse();
});

test('member who has not accepted best practices is not in good standing', function () {
    $member = trustSystemMember([
        'best_practices_agreed_at' => null,
    ]);

    expect($member->isInGoodStanding())->toBeFalse();
});

test('non active membership statuses are not in good standing', function (string $status) {
    $member = trustSystemMember([
        'membership_status' => $status,
    ]);

    expect($member->isInGoodStanding())->toBeFalse();
})->with([
    'pending',
    'suspended',
    'banned',
]);

test('active warning does not remove good standing', function () {
    $member = trustSystemMember();

    MembershipEnforcement::create([
        'user_id' => $member->id,
        'action' => 'warning',
        'status' => 'active',
        'reason' => 'Corrective warning for testing.',
        'starts_at' => now(),
    ]);

    expect($member->hasActiveMembershipWarning())->toBeTrue()
        ->and($member->isInGoodStanding())->toBeTrue();
});

test('active probation removes good standing', function () {
    $member = trustSystemMember();

    MembershipEnforcement::create([
        'user_id' => $member->id,
        'action' => 'probation',
        'status' => 'active',
        'reason' => 'Probation for testing.',
        'starts_at' => now(),
    ]);

    expect($member->isOnProbation())->toBeTrue()
        ->and($member->isInGoodStanding())->toBeFalse();
});

test('administrative hold removes good standing', function () {
    $member = trustSystemMember();

    MembershipEnforcement::create([
        'user_id' => $member->id,
        'action' => 'administrative_hold',
        'status' => 'active',
        'reason' => 'Administrative review for testing.',
        'starts_at' => now(),
    ]);

    expect($member->isUnderAdministrativeReview())->toBeTrue()
        ->and($member->isInGoodStanding())->toBeFalse();
});

test('resolved probation does not remove good standing', function () {
    $member = trustSystemMember();

    MembershipEnforcement::create([
        'user_id' => $member->id,
        'action' => 'probation',
        'status' => 'resolved',
        'reason' => 'Resolved probation for testing.',
        'starts_at' => now()->subDays(100),
        'resolved_at' => now(),
    ]);

    expect($member->isOnProbation())->toBeFalse()
        ->and($member->isInGoodStanding())->toBeTrue();
});

test('future probation does not remove good standing before its start date', function () {
    $member = trustSystemMember();

    MembershipEnforcement::create([
        'user_id' => $member->id,
        'action' => 'probation',
        'status' => 'active',
        'reason' => 'Future probation for testing.',
        'starts_at' => now()->addDay(),
    ]);

    expect($member->isOnProbation())->toBeFalse()
        ->and($member->isInGoodStanding())->toBeTrue();
});
