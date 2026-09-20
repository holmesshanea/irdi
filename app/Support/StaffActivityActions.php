<?php

namespace App\Support;

final class StaffActivityActions
{
    /**
     * @return array<string, array<int, string>>
     */
    public static function categories(): array
    {
        return [
            'staff_access' => [
                'moderator_assigned',
                'moderator_removed',
                'administrator_assigned',
                'administrator_removed',
            ],

            'messaging' => [
                'messaging_disabled',
                'messaging_restored',
                'messaging_disabled_from_report',
                'messaging_restored_from_report',
            ],

            'membership' => [
                'membership_warning_issued',
                'membership_warning_resolved',
                'membership_warning_withdrawn',

                'membership_probation_started',
                'membership_probation_continued',
                'membership_probation_resolved',
                'membership_probation_restored',
                'membership_probation_escalated',

                'membership_administrative_hold_started',
                'membership_administrative_hold_resolved_no_action',
                'membership_administrative_hold_resolved_warning',
                'membership_administrative_hold_resolved_probation',
                'membership_administrative_hold_resolved_suspension',
                'membership_administrative_hold_resolved_ban',

                'membership_suspended',
                'membership_suspension_continued',
                'membership_suspension_resolved_good_standing',
                'membership_suspension_resolved_probation',
                'membership_suspension_resolved_ban',

                'member_banned',

                'membership_reinstatement_denied',
                'membership_reinstated_probation',
                'membership_reinstated_good_standing',

                'membership_restored',
            ],

            'reviews' => [
                'review_marked_reviewed',
                'review_hidden',
                'review_restored',
            ],

            'message_reports' => [
                'message_report_reviewed',
                'message_report_dismissed',
                'message_report_reopened',
            ],
        ];
    }

    /**
     * @return array<int, string>
     */
    public static function forCategory(string $category): array
    {
        return self::categories()[$category] ?? [];
    }

    /**
     * @return array<int, string>
     */
    public static function all(): array
    {
        return array_values(array_unique(array_merge(
            ...array_values(self::categories())
        )));
    }
}
