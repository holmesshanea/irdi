<?php

use App\Models\User;
use App\Support\MembershipEnforcementAuthorization;

function enforcementStaffUser(array $attributes = []): User
{
    return User::factory()->create(array_merge([
        'membership_status' => 'active',
        'member_since' => now()->toDateString(),
        'ethics_agreed_at' => now(),
        'best_practices_agreed_at' => now(),
    ], $attributes));
}

test('moderator cannot issue membership discipline', function () {
    $moderator = enforcementStaffUser([
        'is_moderator' => true,
    ]);

    $member = enforcementStaffUser();

    expect(MembershipEnforcementAuthorization::canIssueWarning($moderator, $member))->toBeFalse()
        ->and(MembershipEnforcementAuthorization::canManageProbation($moderator, $member))->toBeFalse()
        ->and(MembershipEnforcementAuthorization::canManageAdministrativeHold($moderator, $member))->toBeFalse()
        ->and(MembershipEnforcementAuthorization::canSuspend($moderator, $member))->toBeFalse()
        ->and(MembershipEnforcementAuthorization::canBan($moderator, $member))->toBeFalse();
});

test('administrator can discipline ordinary member', function () {
    $administrator = enforcementStaffUser([
        'is_admin' => true,
    ]);

    $member = enforcementStaffUser();

    expect(MembershipEnforcementAuthorization::canIssueWarning($administrator, $member))->toBeTrue()
        ->and(MembershipEnforcementAuthorization::canManageProbation($administrator, $member))->toBeTrue()
        ->and(MembershipEnforcementAuthorization::canManageAdministrativeHold($administrator, $member))->toBeTrue()
        ->and(MembershipEnforcementAuthorization::canSuspend($administrator, $member))->toBeTrue()
        ->and(MembershipEnforcementAuthorization::canRestoreSuspension($administrator, $member))->toBeTrue();
});

test('administrator can discipline moderator', function () {
    $administrator = enforcementStaffUser([
        'is_admin' => true,
    ]);

    $moderator = enforcementStaffUser([
        'is_moderator' => true,
    ]);

    expect(MembershipEnforcementAuthorization::canIssueWarning($administrator, $moderator))->toBeTrue()
        ->and(MembershipEnforcementAuthorization::canManageProbation($administrator, $moderator))->toBeTrue()
        ->and(MembershipEnforcementAuthorization::canSuspend($administrator, $moderator))->toBeTrue();
});

test('administrator cannot discipline another administrator', function () {
    $administrator = enforcementStaffUser([
        'is_admin' => true,
    ]);

    $otherAdministrator = enforcementStaffUser([
        'is_admin' => true,
    ]);

    expect(MembershipEnforcementAuthorization::canIssueWarning($administrator, $otherAdministrator))->toBeFalse()
        ->and(MembershipEnforcementAuthorization::canManageProbation($administrator, $otherAdministrator))->toBeFalse()
        ->and(MembershipEnforcementAuthorization::canSuspend($administrator, $otherAdministrator))->toBeFalse();
});

test('administrator cannot ban or unban member', function () {
    $administrator = enforcementStaffUser([
        'is_admin' => true,
    ]);

    $member = enforcementStaffUser();

    expect(MembershipEnforcementAuthorization::canBan($administrator, $member))->toBeFalse()
        ->and(MembershipEnforcementAuthorization::canUnban($administrator, $member))->toBeFalse();
});

test('super administrator can perform membership discipline', function () {
    $superAdministrator = enforcementStaffUser([
        'is_super_admin' => true,
        'is_admin' => false,
        'is_moderator' => false,
    ]);

    $administrator = enforcementStaffUser([
        'is_admin' => true,
    ]);

    expect(MembershipEnforcementAuthorization::canIssueWarning($superAdministrator, $administrator))->toBeTrue()
        ->and(MembershipEnforcementAuthorization::canManageProbation($superAdministrator, $administrator))->toBeTrue()
        ->and(MembershipEnforcementAuthorization::canManageAdministrativeHold($superAdministrator, $administrator))->toBeTrue()
        ->and(MembershipEnforcementAuthorization::canSuspend($superAdministrator, $administrator))->toBeTrue()
        ->and(MembershipEnforcementAuthorization::canBan($superAdministrator, $administrator))->toBeTrue();
});

test('super administrator cannot be targeted by ordinary enforcement controls', function () {
    $administrator = enforcementStaffUser([
        'is_admin' => true,
    ]);

    $superAdministrator = enforcementStaffUser([
        'is_super_admin' => true,
    ]);

    expect(MembershipEnforcementAuthorization::canIssueWarning($administrator, $superAdministrator))->toBeFalse()
        ->and(MembershipEnforcementAuthorization::canManageProbation($administrator, $superAdministrator))->toBeFalse()
        ->and(MembershipEnforcementAuthorization::canManageAdministrativeHold($administrator, $superAdministrator))->toBeFalse()
        ->and(MembershipEnforcementAuthorization::canSuspend($administrator, $superAdministrator))->toBeFalse()
        ->and(MembershipEnforcementAuthorization::canBan($administrator, $superAdministrator))->toBeFalse();
});

test('super administrator cannot enforce against themselves', function () {
    $superAdministrator = enforcementStaffUser([
        'is_super_admin' => true,
    ]);

    expect(MembershipEnforcementAuthorization::canIssueWarning(
        $superAdministrator,
        $superAdministrator
    ))->toBeFalse()
        ->and(MembershipEnforcementAuthorization::canSuspend(
            $superAdministrator,
            $superAdministrator
        ))->toBeFalse()
        ->and(MembershipEnforcementAuthorization::canBan(
            $superAdministrator,
            $superAdministrator
        ))->toBeFalse();
});
