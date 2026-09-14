<?php

namespace App\Support;

use App\Models\User;

class MembershipEnforcementAuthorization
{
    public static function canIssueWarning(User $actor, User $target): bool
    {
        return self::canAdministerMembershipDiscipline($actor, $target);
    }

    public static function canManageProbation(User $actor, User $target): bool
    {
        return self::canAdministerMembershipDiscipline($actor, $target);
    }

    public static function canManageAdministrativeHold(User $actor, User $target): bool
    {
        return self::canAdministerMembershipDiscipline($actor, $target);
    }

    public static function canSuspend(User $actor, User $target): bool
    {
        return self::canAdministerMembershipDiscipline($actor, $target);
    }

    public static function canRestoreSuspension(User $actor, User $target): bool
    {
        return self::canAdministerMembershipDiscipline($actor, $target);
    }

    public static function canBan(User $actor, User $target): bool
    {
        return self::isSuperAdministrator($actor)
            && self::canTarget($actor, $target);
    }

    public static function canUnban(User $actor, User $target): bool
    {
        return self::canBan($actor, $target);
    }

    private static function canAdministerMembershipDiscipline(
        User $actor,
        User $target
    ): bool {
        if (! $actor->isAdministrator()) {
            return false;
        }

        if (! self::canTarget($actor, $target)) {
            return false;
        }

        return self::staffRank($actor) > self::staffRank($target);
    }

    private static function canTarget(User $actor, User $target): bool
    {
        if ($actor->is($target)) {
            return false;
        }

        return ! (bool) $target->is_super_admin;
    }

    private static function isSuperAdministrator(User $user): bool
    {
        return (bool) $user->is_super_admin;
    }

    private static function staffRank(User $user): int
    {
        return match (true) {
            (bool) $user->is_super_admin => 3,
            (bool) $user->is_admin => 2,
            (bool) $user->is_moderator => 1,
            default => 0,
        };
    }
}
